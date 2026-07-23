<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Gateways\GatewayManager;
use App\Services\Support\SettingsRepository;
use App\Services\ThemeManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * All platform settings, editable from the UI — nothing requires code edits.
 * Tabs: general, seo, email, social, security, registration, payments,
 * affiliate, ads, storage, advanced (cron/queue status), gdpr.
 *
 * Sensitive keys (secrets, passwords, API keys) are stored encrypted.
 */
class SettingsController extends Controller
{
    /** Setting keys per tab: [key => validation rule]. */
    protected function tabs(): array
    {
        return [
            'general' => [
                'site_name' => 'nullable|string|max:100',
                'tagline' => 'nullable|string|max:190',
                'site_url' => 'nullable|url',
                'timezone' => 'nullable|timezone',
                'date_format' => 'nullable|string|max:20',
                'currency' => 'nullable|string|size:3',
                'default_language' => 'nullable|string|max:10',
                'auto_detect_language' => 'nullable|boolean',
                'theme' => 'nullable|string|max:60',
                'announcement_text' => 'nullable|string|max:500',
                'announcement_enabled' => 'nullable|boolean',
                'maintenance_mode' => 'nullable|boolean',
                'cookie_consent_enabled' => 'nullable|boolean',
                'cookie_consent_text' => 'nullable|string|max:1000',
            ],
            'seo' => [
                'meta_title' => 'nullable|string|max:190',
                'meta_description' => 'nullable|string|max:500',
                'og_image' => 'nullable|url|max:500',
                'robots_txt' => 'nullable|string|max:5000',
            ],
            'email' => [
                'smtp_host' => 'nullable|string|max:190',
                'smtp_port' => 'nullable|integer',
                'smtp_username' => 'nullable|string|max:190',
                'smtp_password' => 'nullable|string|max:190',
                'smtp_encryption' => 'nullable|in:tls,ssl,none',
                'mail_from_address' => 'nullable|email',
                'mail_from_name' => 'nullable|string|max:100',
                'contact_email' => 'nullable|email',
            ],
            'social' => [
                'oauth_google_id' => 'nullable|string|max:255', 'oauth_google_secret' => 'nullable|string|max:255',
                'oauth_facebook_id' => 'nullable|string|max:255', 'oauth_facebook_secret' => 'nullable|string|max:255',
                'oauth_twitter_id' => 'nullable|string|max:255', 'oauth_twitter_secret' => 'nullable|string|max:255',
                'oauth_github_id' => 'nullable|string|max:255', 'oauth_github_secret' => 'nullable|string|max:255',
                'oauth_apple_id' => 'nullable|string|max:255', 'oauth_apple_secret' => 'nullable|string|max:2000',
            ],
            'security' => [
                'captcha_provider' => 'nullable|in:recaptcha2,recaptcha3,hcaptcha,none',
                'captcha_site_key' => 'nullable|string|max:190',
                'captcha_secret' => 'nullable|string|max:190',
                'honeypot_enabled' => 'nullable|boolean',
                'safe_browsing_key' => 'nullable|string|max:190',
                'sentry_dsn' => 'nullable|url|max:300',
            ],
            'registration' => [
                'registration_enabled' => 'nullable|boolean',
                'require_email_verification' => 'nullable|boolean',
                'guest_shorten_enabled' => 'nullable|boolean',
                'guest_link_days' => 'nullable|integer|min:1|max:3650',
                'guest_shorten_rate' => 'nullable|integer|min:1|max:100',
            ],
            'affiliate' => [
                'affiliate_enabled' => 'nullable|boolean',
                'affiliate_commission_percent' => 'nullable|numeric|min:0|max:100',
                'affiliate_min_payout' => 'nullable|numeric|min:0',
            ],
            'ads' => [
                'interstitial_enabled' => 'nullable|boolean',
                'interstitial_seconds' => 'nullable|integer|min:1|max:60',
                'interstitial_ad_code' => 'nullable|string|max:10000',
            ],
            'storage' => [
                'storage_disk' => 'nullable|in:public,s3',
                's3_key' => 'nullable|string|max:190', 's3_secret' => 'nullable|string|max:190',
                's3_region' => 'nullable|string|max:60', 's3_bucket' => 'nullable|string|max:190',
                's3_endpoint' => 'nullable|url|max:300', 's3_path_style' => 'nullable|boolean',
            ],
            'gdpr' => [
                'anonymize_ips' => 'nullable|boolean',
                'no_personal_data' => 'nullable|boolean',
                'geo_http_lookup' => 'nullable|boolean',
                'invoice_prefix' => 'nullable|string|max:10',
                'invoice_company_details' => 'nullable|string|max:2000',
            ],
            'ai' => [
                'ai_provider' => 'nullable|in:anthropic,openai',
                'ai_key' => 'nullable|string|max:255',
                'ai_model' => 'nullable|string|max:100',
                'ai_spam_check' => 'nullable|boolean',
            ],
        ];
    }

    /** Keys encrypted at rest. */
    protected array $encrypted = [
        'smtp_password', 'captcha_secret', 'safe_browsing_key',
        'oauth_google_secret', 'oauth_facebook_secret', 'oauth_twitter_secret',
        'oauth_github_secret', 'oauth_apple_secret', 's3_secret', 'ai_key',
    ];

    public function index(Request $request, GatewayManager $gateways, ThemeManager $themes, string $tab = 'general')
    {
        $tabs = array_keys($this->tabs());
        $tabs[] = 'payments';
        $tabs[] = 'advanced';
        abort_unless(in_array($tab, $tabs, true), 404);

        return view('admin.settings.' . $tab, [
            'tab' => $tab,
            'tabs' => $tabs,
            'gateways' => $gateways->all(),
            'themes' => $themes->available(),
            'cron' => [
                'last_run' => setting('cron_last_run'),
                'healthy' => setting('cron_last_run') && now()->parse(setting('cron_last_run'))->gt(now()->subMinutes(10)),
                'command' => '* * * * * cd ' . base_path() . ' && php artisan schedule:run >> /dev/null 2>&1',
                'queue_command' => 'php ' . base_path('artisan') . ' queue:work --tries=3 --timeout=90',
                'pending_jobs' => $this->safeCount('jobs'),
                'failed_jobs' => $this->safeCount('failed_jobs'),
            ],
        ]);
    }

    public function update(Request $request, string $tab)
    {
        $rules = $this->tabs()[$tab] ?? abort(404);
        $data = $request->validate($rules);

        // Checkbox tabs: unchecked boxes arrive absent — coerce to false.
        foreach ($rules as $key => $rule) {
            if (str_contains($rule, 'boolean')) {
                $data[$key] = $request->boolean($key);
            }
        }

        // Don't overwrite stored secrets with the masked placeholder.
        foreach ($this->encrypted as $key) {
            if (($data[$key] ?? null) === '••••••••') {
                unset($data[$key]);
            }
        }

        app(SettingsRepository::class)->setMany($data, $this->encrypted);
        AuditLog::record('settings.updated', null, ['tab' => $tab]);

        return back()->with('status', __('Settings saved.'));
    }

    /** Payment gateway credentials (separate tab, dynamic per gateway). */
    public function updatePayments(Request $request, GatewayManager $gateways)
    {
        $values = [];
        $encrypted = [];
        foreach ($gateways->all() as $gateway) {
            $key = $gateway->key();
            $values[$key . '_enabled'] = $request->boolean($key . '_enabled');
            foreach ((array) $request->input($key, []) as $field => $value) {
                if (! preg_match('/^[a-z0-9_]+$/', $field)) {
                    continue;
                }
                $settingKey = $key . '_' . $field;
                if ($value === '••••••••') {
                    continue;
                }
                $values[$settingKey] = is_string($value) ? trim($value) : $value;
                if (preg_match('/secret|key|token/', $field)) {
                    $encrypted[] = $settingKey;
                }
            }
        }

        app(SettingsRepository::class)->setMany($values, $encrypted);
        AuditLog::record('settings.updated', null, ['tab' => 'payments']);

        return back()->with('status', __('Payment settings saved.'));
    }

    public function sendTestEmail(Request $request)
    {
        $request->validate(['to' => 'required|email']);

        try {
            Mail::raw(
                __('This is a test email from :site. Your SMTP settings are working correctly!', ['site' => site_name()]),
                fn ($m) => $m->to($request->input('to'))->subject('[' . site_name() . '] ' . __('Test email'))
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['to' => __('Sending failed: ') . $e->getMessage()]);
        }

        return back()->with('status', __('Test email sent to :email.', ['email' => $request->input('to')]));
    }

    public function uploadBranding(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|max:1024',
            'favicon' => 'nullable|file|mimes:png,ico,svg|max:512',
        ]);

        $repo = app(SettingsRepository::class);
        foreach (['logo', 'favicon'] as $file) {
            if ($request->hasFile($file)) {
                $repo->set('site_' . $file, $request->file($file)->store('branding', setting('storage_disk', 'public')));
            }
        }

        return back()->with('status', __('Branding updated.'));
    }

    protected function safeCount(string $table): int
    {
        try {
            return (int) \Illuminate\Support\Facades\DB::table($table)->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
