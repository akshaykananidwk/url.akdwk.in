<?php

use App\Services\Support\SettingsRepository;
use App\Support\Hook;

if (! function_exists('is_installed')) {
    /** Whether the application has been installed via the web installer. */
    function is_installed(): bool
    {
        static $installed = null;

        return $installed ??= file_exists(storage_path('installed.lock'));
    }
}

if (! function_exists('setting')) {
    /** Get a setting value from the DB-backed settings store. */
    function setting(string $key, $default = null)
    {
        if (! is_installed()) {
            return $default;
        }

        return app(SettingsRepository::class)->get($key, $default);
    }
}

if (! function_exists('setting_set')) {
    /** Persist a setting value. */
    function setting_set(string $key, $value, bool $encrypted = false): void
    {
        app(SettingsRepository::class)->set($key, $value, $encrypted);
    }
}

if (! function_exists('hook_action')) {
    /** Fire an action hook (addons can listen). */
    function hook_action(string $name, ...$args): void
    {
        Hook::action($name, ...$args);
    }
}

if (! function_exists('hook_filter')) {
    /** Pass a value through registered filters. */
    function hook_filter(string $name, $value, ...$args)
    {
        return Hook::filter($name, $value, ...$args);
    }
}

if (! function_exists('format_money')) {
    /** Format an amount in the site currency (or a given one). */
    function format_money($amount, ?string $currency = null): string
    {
        $currency = $currency ?: setting('currency', 'USD');
        $symbols = [
            'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'INR' => '₹', 'JPY' => '¥',
            'CNY' => '¥', 'BRL' => 'R$', 'IDR' => 'Rp', 'NGN' => '₦', 'MXN' => 'MX$',
            'CAD' => 'C$', 'AUD' => 'A$', 'AED' => 'د.إ', 'SGD' => 'S$', 'ZAR' => 'R',
        ];
        $symbol = $symbols[$currency] ?? ($currency . ' ');

        return $symbol . number_format((float) $amount, fmod((float) $amount, 1) == 0.0 ? 0 : 2);
    }
}

if (! function_exists('format_number')) {
    /** Compact number formatting: 12.4K, 1.2M. */
    function format_number($n): string
    {
        $n = (float) $n;
        if ($n >= 1_000_000_000) {
            return round($n / 1_000_000_000, 1) . 'B';
        }
        if ($n >= 1_000_000) {
            return round($n / 1_000_000, 1) . 'M';
        }
        if ($n >= 10_000) {
            return round($n / 1_000, 1) . 'K';
        }

        return number_format($n);
    }
}

if (! function_exists('site_name')) {
    function site_name(): string
    {
        return setting('site_name', config('app.name', 'Shortl'));
    }
}

if (! function_exists('active_languages')) {
    /** Active languages for the switcher, cached per request. */
    function active_languages()
    {
        static $langs = null;
        if ($langs === null) {
            try {
                $langs = \App\Models\Language::where('active', true)->orderBy('name')->get();
            } catch (\Throwable) {
                $langs = collect();
            }
        }

        return $langs;
    }
}

if (! function_exists('current_language')) {
    function current_language(): ?\App\Models\Language
    {
        return active_languages()->firstWhere('code', app()->getLocale());
    }
}

if (! function_exists('storage_url')) {
    /** Public URL for a stored file on the configured disk. */
    function storage_url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk(setting('storage_disk', 'public'))->url($path);
    }
}
