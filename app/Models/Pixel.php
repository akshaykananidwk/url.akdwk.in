<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pixel extends Model
{
    /** Supported pixel providers with human labels. */
    public const TYPES = [
        'gads' => 'Google Ads', 'ga4' => 'Google Analytics 4', 'gtm' => 'Google Tag Manager',
        'meta' => 'Meta / Facebook', 'bing' => 'Bing', 'twitter' => 'X (Twitter)',
        'pinterest' => 'Pinterest', 'linkedin' => 'LinkedIn', 'quora' => 'Quora',
        'snapchat' => 'Snapchat', 'tiktok' => 'TikTok', 'reddit' => 'Reddit',
        'adroll' => 'AdRoll', 'custom' => 'Custom HTML/JS',
    ];

    protected $fillable = ['user_id', 'name', 'type', 'value'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function links(): BelongsToMany
    {
        return $this->belongsToMany(Link::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
