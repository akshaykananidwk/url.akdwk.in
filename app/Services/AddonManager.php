<?php

namespace App\Services;

use App\Models\Addon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Drop-in addon system.
 *
 * An addon is a folder inside /addons containing an addon.json manifest:
 *   {
 *     "slug": "example",
 *     "name": "Example Addon",
 *     "version": "1.0.0",
 *     "description": "...",
 *     "provider": "Addons\\Example\\ExampleServiceProvider"
 *   }
 *
 * Optional conventional sub-folders, auto-registered when the addon is enabled:
 *   src/         PSR-4 classes under the Addons\<StudlyName> namespace
 *   routes/web.php, routes/api.php
 *   migrations/  run on enable via the update wizard or artisan
 *   views/       registered under the addon slug view namespace
 *   lang/        translations under the addon slug namespace
 *
 * Addons integrate with the app through the Hook system (see App\Support\Hook)
 * and can add menu items via the 'user_menu' / 'admin_menu' filters and
 * settings pages via the 'addon_settings_pages' filter.
 */
class AddonManager
{
    protected array $discovered = [];

    public function path(): string
    {
        return base_path('addons');
    }

    /** Scan /addons for manifests. */
    public function discover(): array
    {
        if ($this->discovered) {
            return $this->discovered;
        }

        $addons = [];
        if (! File::isDirectory($this->path())) {
            return $addons;
        }

        foreach (File::directories($this->path()) as $dir) {
            $manifest = $dir . '/addon.json';
            if (! is_file($manifest)) {
                continue;
            }
            $meta = json_decode((string) file_get_contents($manifest), true);
            if (! is_array($meta) || empty($meta['slug'])) {
                continue;
            }
            $meta['dir'] = $dir;
            $addons[$meta['slug']] = $meta;
        }

        return $this->discovered = $addons;
    }

    /** Sync discovered addons into the DB and return merged rows. */
    public function all(): array
    {
        $discovered = $this->discover();
        $rows = Addon::all()->keyBy('slug');
        $out = [];

        foreach ($discovered as $slug => $meta) {
            $row = $rows->get($slug);
            if (! $row) {
                $row = Addon::create([
                    'slug' => $slug,
                    'name' => $meta['name'] ?? $slug,
                    'version' => $meta['version'] ?? '1.0.0',
                    'enabled' => false,
                    'meta' => ['description' => $meta['description'] ?? ''],
                ]);
            } elseif ($row->version !== ($meta['version'] ?? $row->version)) {
                $row->update(['version' => $meta['version']]);
            }
            $out[] = ['model' => $row, 'manifest' => $meta];
        }

        return $out;
    }

    /** Boot all enabled addons: autoload, provider, routes, views, lang. */
    public function bootEnabled(): void
    {
        if (! is_installed()) {
            return;
        }

        try {
            $enabled = Addon::where('enabled', true)->pluck('slug')->all();
        } catch (\Throwable) {
            return; // during install/migration the table may not exist yet
        }

        $discovered = $this->discover();
        foreach ($enabled as $slug) {
            $meta = $discovered[$slug] ?? null;
            if (! $meta) {
                continue;
            }

            try {
                $this->bootAddon($meta);
            } catch (\Throwable $e) {
                Log::error("Addon [$slug] failed to boot: " . $e->getMessage());
            }
        }
    }

    protected function bootAddon(array $meta): void
    {
        $dir = $meta['dir'];
        $slug = $meta['slug'];

        // PSR-4 autoload for the addon's src/ folder.
        if (is_dir($dir . '/src')) {
            $namespace = 'Addons\\' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $slug))) . '\\';
            spl_autoload_register(function ($class) use ($namespace, $dir) {
                if (str_starts_with($class, $namespace)) {
                    $file = $dir . '/src/' . str_replace('\\', '/', substr($class, strlen($namespace))) . '.php';
                    if (is_file($file)) {
                        require_once $file;
                    }
                }
            });
        }

        if (! empty($meta['provider']) && class_exists($meta['provider'])) {
            app()->register($meta['provider']);
        }

        foreach (['web' => 'web', 'api' => 'api'] as $file => $middleware) {
            $routes = $dir . '/routes/' . $file . '.php';
            if (is_file($routes)) {
                \Illuminate\Support\Facades\Route::middleware($middleware)->group($routes);
            }
        }

        if (is_dir($dir . '/views')) {
            view()->addNamespace($slug, $dir . '/views');
        }
        if (is_dir($dir . '/lang')) {
            app('translator')->addNamespace($slug, $dir . '/lang');
        }
        if (is_dir($dir . '/migrations')) {
            app()->afterResolving('migrator', fn ($migrator) => $migrator->path($dir . '/migrations'));
            try {
                app('migrator')->path($dir . '/migrations');
            } catch (\Throwable) {
                // migrator not yet resolvable — afterResolving covers it
            }
        }
    }

    public function enable(string $slug): void
    {
        $meta = $this->discover()[$slug] ?? null;
        Addon::where('slug', $slug)->update(['enabled' => true]);

        // Run the addon's migrations immediately on enable.
        if ($meta && is_dir($meta['dir'] . '/migrations')) {
            Artisan::call('migrate', [
                '--path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $meta['dir'] . '/migrations'),
                '--force' => true,
            ]);
        }
    }

    public function disable(string $slug): void
    {
        Addon::where('slug', $slug)->update(['enabled' => false]);
    }
}
