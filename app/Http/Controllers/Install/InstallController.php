<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\User;
use App\Services\Support\EnvWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;

/**
 * Browser-based installation wizard.
 *
 * Steps: requirements → license (optional) → database → admin account → finish.
 * State between steps lives in the (file-driven) session; the .env file is
 * written automatically — the user never edits config by hand. Once
 * storage/installed.lock exists the /install routes 404 forever
 * (see EnsureInstalled middleware).
 */
class InstallController extends Controller
{
    /* -------------------------------------------------- Step 1: requirements */

    public function requirements()
    {
        $checks = $this->checks();

        return view('install.requirements', [
            'checks' => $checks,
            'pass' => collect($checks['extensions'])->every(fn ($ok) => $ok)
                && collect($checks['writable'])->every(fn ($ok) => $ok)
                && $checks['php']['ok'],
        ]);
    }

    protected function checks(): array
    {
        $extensions = [
            'bcmath', 'ctype', 'curl', 'dom', 'fileinfo', 'json', 'mbstring',
            'openssl', 'pdo', 'tokenizer', 'xml', 'zip',
        ];
        $ext = [];
        foreach ($extensions as $e) {
            $ext[$e] = extension_loaded($e);
        }
        $ext['gd or imagick'] = extension_loaded('gd') || extension_loaded('imagick');
        $ext['pdo_mysql or pdo_sqlite'] = extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite');

        $writable = [
            'storage/' => is_writable(storage_path()),
            'storage/framework/' => is_writable(storage_path('framework')),
            'storage/logs/' => is_writable(storage_path('logs')),
            'bootstrap/cache/' => is_writable(base_path('bootstrap/cache')),
            '.env' => is_writable(base_path()) || (file_exists(base_path('.env')) && is_writable(base_path('.env'))),
        ];

        return [
            'php' => ['version' => PHP_VERSION, 'ok' => version_compare(PHP_VERSION, '8.2.0', '>=')],
            'extensions' => $ext,
            'writable' => $writable,
        ];
    }

    /* ---------------------------------------------------- Step 2: license */

    public function license()
    {
        if (! config('install.require_license')) {
            return redirect()->route('install.database');
        }

        return view('install.license');
    }

    public function storeLicense(Request $request)
    {
        if (config('install.require_license')) {
            $request->validate(['purchase_code' => 'required|string|min:8']);
            session(['install.purchase_code' => $request->input('purchase_code')]);
        }

        return redirect()->route('install.database');
    }

    /* --------------------------------------------------- Step 3: database */

    public function database()
    {
        return view('install.database');
    }

    /** AJAX "Test Connection" endpoint. */
    public function testDatabase(Request $request)
    {
        $result = $this->tryConnect($request->all());

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function storeDatabase(Request $request)
    {
        $data = $request->validate([
            'driver' => 'required|in:mysql,sqlite',
            'host' => 'required_if:driver,mysql|nullable|string',
            'port' => 'nullable|integer',
            'database' => 'required_if:driver,mysql|nullable|string',
            'username' => 'required_if:driver,mysql|nullable|string',
            'password' => 'nullable|string',
            'prefix' => 'nullable|string|max:10|regex:/^[a-zA-Z0-9_]*$/',
        ]);

        $check = $this->tryConnect($data);
        if (! $check['ok']) {
            return back()->withErrors(['database' => $check['message']])->withInput();
        }

        // Persist connection details to .env (APP_KEY too, so encryption works
        // before the wizard finishes).
        $env = [
            'APP_NAME' => 'Shortl',
            'APP_ENV' => 'production',
            'APP_DEBUG' => false,
            'APP_URL' => rtrim(url('/'), '/'),
            'DB_CONNECTION' => $data['driver'],
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'database',
        ];
        if ($data['driver'] === 'mysql') {
            $env += [
                'DB_HOST' => $data['host'],
                'DB_PORT' => $data['port'] ?: 3306,
                'DB_DATABASE' => $data['database'],
                'DB_USERNAME' => $data['username'],
                'DB_PASSWORD' => $data['password'] ?? '',
                'DB_PREFIX' => $data['prefix'] ?? '',
            ];
        } else {
            $sqlitePath = database_path('database.sqlite');
            if (! file_exists($sqlitePath)) {
                touch($sqlitePath);
            }
            $env += ['DB_DATABASE' => $sqlitePath, 'DB_PREFIX' => $data['prefix'] ?? ''];
        }
        if (! env('APP_KEY')) {
            $env['APP_KEY'] = 'base64:' . base64_encode(random_bytes(32));
        }
        EnvWriter::write($env);

        // Reconfigure the current process to use the new connection and migrate.
        $this->applyRuntimeConnection($data);

        try {
            Artisan::call('migrate:fresh', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true, '--class' => 'Database\\Seeders\\DatabaseSeeder']);
        } catch (\Throwable $e) {
            return back()->withErrors(['database' => __('Migration failed: ') . $e->getMessage()])->withInput();
        }

        session(['install.db_done' => true, 'install.demo' => $request->boolean('demo_data')]);

        return redirect()->route('install.admin');
    }

    protected function tryConnect(array $data): array
    {
        try {
            if (($data['driver'] ?? 'mysql') === 'sqlite') {
                $path = database_path('database.sqlite');
                if (! file_exists($path)) {
                    touch($path);
                }
                new PDO('sqlite:' . $path);

                return ['ok' => true, 'message' => __('SQLite database ready.')];
            }

            $dsn = sprintf('mysql:host=%s;port=%d', $data['host'] ?? '127.0.0.1', (int) ($data['port'] ?? 3306));
            $pdo = new PDO($dsn, $data['username'] ?? '', $data['password'] ?? '', [PDO::ATTR_TIMEOUT => 5, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $db = $data['database'] ?? '';
            $quoted = str_replace('`', '``', $db);
            $exists = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote($db))->fetchColumn();
            if (! $exists) {
                // try to create it for the user
                $pdo->exec("CREATE DATABASE `{$quoted}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
            $pdo->exec("USE `{$quoted}`");

            return ['ok' => true, 'message' => __('Connection successful. Database ready.')];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    protected function applyRuntimeConnection(array $data): void
    {
        $driver = $data['driver'];
        Config::set('database.default', $driver);
        if ($driver === 'mysql') {
            Config::set('database.connections.mysql.host', $data['host']);
            Config::set('database.connections.mysql.port', (int) ($data['port'] ?: 3306));
            Config::set('database.connections.mysql.database', $data['database']);
            Config::set('database.connections.mysql.username', $data['username']);
            Config::set('database.connections.mysql.password', $data['password'] ?? '');
            Config::set('database.connections.mysql.prefix', $data['prefix'] ?? '');
        } else {
            Config::set('database.connections.sqlite.database', database_path('database.sqlite'));
            Config::set('database.connections.sqlite.prefix', $data['prefix'] ?? '');
        }
        DB::purge();
        DB::reconnect();
    }

    /* ------------------------------------------------ Step 4: admin account */

    public function admin()
    {
        if (! session('install.db_done') && ! $this->schemaReady()) {
            return redirect()->route('install.database');
        }

        return view('install.admin', [
            'timezones' => \DateTimeZone::listIdentifiers(),
            'currencies' => ['USD', 'EUR', 'GBP', 'INR', 'JPY', 'CNY', 'BRL', 'IDR', 'NGN', 'MXN', 'CAD', 'AUD', 'AED', 'SGD', 'ZAR'],
        ]);
    }

    public function storeAdmin(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:190',
            'password' => 'required|string|min:8|confirmed',
            'site_name' => 'required|string|max:100',
            'site_url' => 'required|url',
            'default_language' => 'required|in:en,gu,hi',
            'timezone' => 'required|timezone',
            'currency' => 'required|string|size:3',
        ]);

        $user = User::firstOrNew(['email' => $data['email']]);
        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'admin',
            'email_verified_at' => now(),
            'locale' => $data['default_language'],
            'timezone' => $data['timezone'],
        ])->save();

        EnvWriter::write(['APP_NAME' => $data['site_name'], 'APP_URL' => rtrim($data['site_url'], '/')]);

        // Settings must be written after the lock-independent repository is
        // usable; write directly since is_installed() is still false here.
        $repo = app(\App\Services\Support\SettingsRepository::class);
        $repo->setMany([
            'site_name' => $data['site_name'],
            'site_url' => rtrim($data['site_url'], '/'),
            'default_language' => $data['default_language'],
            'timezone' => $data['timezone'],
            'currency' => strtoupper($data['currency']),
            'registration_enabled' => true,
            'require_email_verification' => false,
            'installed_version' => config('install.version', '1.0.0'),
        ]);

        Language::where('code', $data['default_language'])->update(['is_default' => true]);

        if (session('install.demo')) {
            try {
                Artisan::call('db:seed', ['--force' => true, '--class' => 'Database\\Seeders\\DemoSeeder']);
            } catch (\Throwable) {
                // demo data is optional — never block installation on it
            }
        }

        session(['install.admin_done' => true]);

        return redirect()->route('install.finish');
    }

    protected function schemaReady(): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable('users');
        } catch (\Throwable) {
            return false;
        }
    }

    /* ----------------------------------------------------- Step 5: finish */

    public function finish()
    {
        if (! session('install.admin_done')) {
            return redirect()->route('install.admin');
        }

        // Permanently lock the installer.
        file_put_contents(storage_path('installed.lock'), now()->toIso8601String() . "\n" . Str::random(32));

        try {
            Artisan::call('storage:link');
        } catch (\Throwable) {
            // symlink may fail on some shared hosts; storage files fall back to route serving
        }
        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
        } catch (\Throwable) {
        }

        session()->forget('install');

        return view('install.finish', ['loginUrl' => url('/login')]);
    }
}
