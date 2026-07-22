<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'price_monthly', 'price_yearly', 'price_lifetime',
        'trial_days', 'limits', 'features', 'is_free', 'is_default', 'is_featured',
        'active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'limits' => 'array',
            'features' => 'array',
            'is_free' => 'boolean',
            'is_default' => 'boolean',
            'is_featured' => 'boolean',
            'active' => 'boolean',
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'price_lifetime' => 'decimal:2',
        ];
    }

    /** All limit keys with human labels — single source of truth for admin UI. */
    public const LIMIT_KEYS = [
        'links' => 'Links', 'clicks_per_month' => 'Tracked clicks / month', 'spaces' => 'Spaces',
        'domains' => 'Custom domains', 'pixels' => 'Pixels', 'team_members' => 'Team members',
        'qr_codes' => 'QR codes', 'bio_pages' => 'Bio pages', 'api_rate' => 'API requests / min',
        'retention_days' => 'Stats retention (days)',
    ];

    /** All feature flags with human labels. */
    public const FEATURE_KEYS = [
        'custom_alias' => 'Custom aliases', 'custom_domains' => 'Custom domains',
        'password' => 'Password protection', 'expiration' => 'Link expiration',
        'targeting' => 'Targeting rules', 'rotator' => 'A/B rotator', 'deep_links' => 'Deep links',
        'cloaking' => 'Link cloaking', 'og' => 'Custom OG preview', 'utm' => 'UTM builder',
        'pixels' => 'Retargeting pixels', 'api' => 'API access', 'export' => 'CSV/PDF export',
        'team' => 'Team members', 'bio' => 'Bio pages', 'qr' => 'QR codes',
        'qr_logo' => 'QR logo & styling', 'file_links' => 'File-to-link',
        'public_stats' => 'Public stats pages', 'webhooks' => 'Webhooks',
        'bulk' => 'Bulk shortening', 'no_ads' => 'No interstitial ads',
        'remove_branding' => 'Remove branding',
    ];

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class);
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('default_plan'));
        static::deleted(fn () => Cache::forget('default_plan'));
    }

    public static function defaultPlan(): Plan
    {
        return Cache::remember('default_plan', 3600, function () {
            return static::where('is_default', true)->first()
                ?? static::where('is_free', true)->orderBy('sort_order')->first()
                ?? static::firstOrCreate(
                    ['slug' => 'free'],
                    ['name' => 'Free', 'is_free' => true, 'is_default' => true,
                     'limits' => ['links' => 50, 'clicks_per_month' => 5000, 'spaces' => 2, 'domains' => 0,
                                  'pixels' => 1, 'team_members' => 0, 'qr_codes' => 5, 'bio_pages' => 1,
                                  'api_rate' => 30, 'retention_days' => 30],
                     'features' => ['custom_alias' => true, 'qr' => true, 'bio' => true, 'utm' => true]]
                );
        });
    }

    /** -1 means unlimited. */
    public function limit(string $key): int
    {
        return (int) ($this->limits[$key] ?? 0);
    }

    public function hasFeature(string $key): bool
    {
        return (bool) ($this->features[$key] ?? false);
    }

    public function price(string $cycle): float
    {
        return (float) match ($cycle) {
            'yearly' => $this->price_yearly,
            'lifetime' => $this->price_lifetime,
            default => $this->price_monthly,
        };
    }

    /** Percent saved by paying yearly vs 12 months of monthly. */
    public function yearlyDiscountPercent(): int
    {
        $monthly = (float) $this->price_monthly * 12;
        if ($monthly <= 0 || (float) $this->price_yearly <= 0) {
            return 0;
        }

        return (int) round((1 - ((float) $this->price_yearly / $monthly)) * 100);
    }
}
