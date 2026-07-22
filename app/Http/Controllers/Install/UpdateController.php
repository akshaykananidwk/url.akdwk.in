<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Update wizard: after uploading a new release over the old files, visiting
 * /update shows pending migrations and runs them with one click, then clears
 * caches and bumps the stored version.
 */
class UpdateController extends Controller
{
    public function index()
    {
        return view('install.update', [
            'currentVersion' => setting('installed_version', '1.0.0'),
            'newVersion' => config('install.version', '1.0.0'),
            'pending' => $this->pendingMigrations(),
        ]);
    }

    public function run()
    {
        Artisan::call('migrate', ['--force' => true]);
        $output = Artisan::output();

        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');

        setting_set('installed_version', config('install.version', '1.0.0'));

        return view('install.update-done', ['output' => $output]);
    }

    protected function pendingMigrations(): array
    {
        try {
            $ran = DB::table('migrations')->pluck('migration')->all();
        } catch (\Throwable) {
            $ran = [];
        }

        $all = collect(glob(database_path('migrations/*.php')))
            ->map(fn ($f) => basename($f, '.php'));

        return $all->diff($ran)->values()->all();
    }
}
