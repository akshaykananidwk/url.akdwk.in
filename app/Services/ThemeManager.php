<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Swappable frontend themes.
 *
 * A theme is a folder in resources/themes/<name> containing Blade views that
 * mirror resources/views. The active theme (Admin → Settings → General) is
 * prepended to the view finder, so any view it provides overrides the default,
 * and anything it omits falls back to the stock views. A theme may include a
 * theme.json manifest ({"name": "...", "author": "...", "screenshot": "..."}).
 */
class ThemeManager
{
    public function path(): string
    {
        return resource_path('themes');
    }

    public function available(): array
    {
        $themes = [['slug' => 'default', 'name' => 'Default', 'author' => 'Built-in']];
        if (! File::isDirectory($this->path())) {
            return $themes;
        }

        foreach (File::directories($this->path()) as $dir) {
            $slug = basename($dir);
            $meta = [];
            if (is_file($dir . '/theme.json')) {
                $meta = json_decode((string) file_get_contents($dir . '/theme.json'), true) ?: [];
            }
            $themes[] = ['slug' => $slug, 'name' => $meta['name'] ?? ucfirst($slug), 'author' => $meta['author'] ?? ''];
        }

        return $themes;
    }

    public function active(): string
    {
        return (string) setting('theme', 'default');
    }

    /** Prepend the active theme's view folder so its views win. */
    public function boot(): void
    {
        $theme = $this->active();
        if ($theme === 'default') {
            return;
        }
        $dir = $this->path() . '/' . $theme;
        if (File::isDirectory($dir)) {
            view()->getFinder()->prependLocation($dir);
        }
    }
}
