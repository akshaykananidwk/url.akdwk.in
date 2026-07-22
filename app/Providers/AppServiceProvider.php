<?php

namespace App\Providers;

use App\Services\AddonManager;
use App\Services\Support\SettingsRepository;
use App\Services\ThemeManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsRepository::class);
        $this->app->singleton(AddonManager::class);
        $this->app->singleton(ThemeManager::class);
    }

    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        $this->configureRateLimiters();

        if (! is_installed()) {
            return;
        }

        $this->applyDbSettings();

        // Extensibility: swappable themes + drop-in addons.
        $this->app->make(ThemeManager::class)->boot();
        $this->app->make(AddonManager::class)->bootEnabled();

        // Share commonly needed globals with all views.
        View::composer('*', function ($view) {
            $view->with('siteName', site_name());
        });
    }

    /** Push DB-stored settings into the runtime config (mail, services, etc.). */
    protected function applyDbSettings(): void
    {
        try {
            $s = $this->app->make(SettingsRepository::class);

            if ($host = $s->get('smtp_host')) {
                Config::set('mail.default', 'smtp');
                Config::set('mail.mailers.smtp.host', $host);
                Config::set('mail.mailers.smtp.port', (int) $s->get('smtp_port', 587));
                Config::set('mail.mailers.smtp.username', $s->get('smtp_username'));
                Config::set('mail.mailers.smtp.password', $s->get('smtp_password'));
                Config::set('mail.mailers.smtp.scheme', $s->get('smtp_encryption') === 'ssl' ? 'smtps' : null);
            }
            Config::set('mail.from.address', $s->get('mail_from_address', config('mail.from.address')));
            Config::set('mail.from.name', $s->get('mail_from_name', site_name()));

            Config::set('app.timezone', $s->get('timezone', config('app.timezone')));
            date_default_timezone_set(config('app.timezone'));

            if ($s->get('storage_disk') === 's3') {
                foreach (['key', 'secret', 'region', 'bucket', 'endpoint'] as $k) {
                    if ($v = $s->get('s3_' . $k)) {
                        Config::set('filesystems.disks.s3.' . $k, $v);
                    }
                }
                Config::set('filesystems.disks.s3.use_path_style_endpoint', (bool) $s->get('s3_path_style', false));
            }

            // Social login credentials (manual OAuth flows in SocialAuthController).
            foreach (['google', 'facebook', 'twitter', 'github'] as $provider) {
                Config::set('services.' . $provider . '.client_id', $s->get('oauth_' . $provider . '_id'));
                Config::set('services.' . $provider . '.client_secret', $s->get('oauth_' . $provider . '_secret'));
            }
        } catch (\Throwable) {
            // settings table missing mid-install — safe to ignore
        }
    }

    protected function configureRateLimiters(): void
    {
        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(8)->by($r->ip()));
        RateLimiter::for('register', fn (Request $r) => Limit::perMinute(5)->by($r->ip()));
        RateLimiter::for('shorten-guest', fn (Request $r) => Limit::perMinute((int) (setting('guest_shorten_rate', 10)))->by($r->ip()));
        RateLimiter::for('api', fn (Request $r) => Limit::perMinute(60)->by($r->user()?->id ?: $r->ip()));
    }
}
