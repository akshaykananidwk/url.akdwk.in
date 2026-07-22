<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Link extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'space_id', 'domain_id', 'alias', 'destination', 'title', 'type',
        'password', 'expires_at', 'max_clicks', 'expired_redirect', 'disabled',
        'cloaking', 'deep_link', 'og', 'utm', 'targeting', 'meta', 'notes', 'tags',
        'public_stats', 'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'archived_at' => 'datetime',
            'last_click_at' => 'datetime',
            'disabled' => 'boolean',
            'cloaking' => 'boolean',
            'public_stats' => 'boolean',
            'deep_link' => 'array',
            'og' => 'array',
            'utm' => 'array',
            'targeting' => 'array',
            'meta' => 'array',
            'tags' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (Link $link) => $link->flushCache());
        static::deleted(fn (Link $link) => $link->flushCache());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function pixels(): BelongsToMany
    {
        return $this->belongsToMany(Pixel::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    public function rollups(): HasMany
    {
        return $this->hasMany(ClickRollup::class);
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QrCode::class);
    }

    /* ------------------------------------------------------------------ */

    /** Cache key for the hot redirect path. */
    public static function cacheKey(?int $domainId, string $alias): string
    {
        return 'link:' . ($domainId ?: 0) . ':' . mb_strtolower($alias);
    }

    public function flushCache(): void
    {
        Cache::forget(static::cacheKey($this->domain_id, $this->alias));
    }

    /** Full short URL for display/sharing. */
    public function shortUrl(): string
    {
        $host = $this->domain?->domain
            ? (($this->domain->ssl ? 'https://' : 'http://') . $this->domain->domain)
            : rtrim(config('app.url'), '/');

        return $host . '/' . $this->alias;
    }

    public function isExpired(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) {
            return true;
        }

        return $this->max_clicks !== null && $this->clicks_count >= $this->max_clicks;
    }

    /** Destination with UTM parameters applied. */
    public function destinationWithUtm(?string $url = null): string
    {
        $url = $url ?? $this->destination;
        $utm = array_filter($this->utm ?? []);
        if (! $utm) {
            return $url;
        }

        $params = [];
        foreach (['source', 'medium', 'campaign', 'term', 'content'] as $key) {
            if (! empty($utm[$key])) {
                $params['utm_' . $key] = $utm[$key];
            }
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('disabled', false)->whereNull('archived_at');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('alias', 'like', "%{$term}%")
                ->orWhere('destination', 'like', "%{$term}%")
                ->orWhere('title', 'like', "%{$term}%")
                ->orWhere('notes', 'like', "%{$term}%");
        });
    }
}
