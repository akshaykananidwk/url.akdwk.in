<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Domain extends Model
{
    protected $fillable = [
        'user_id', 'domain', 'index_redirect', 'not_found_redirect',
        'is_default', 'verified_at', 'verification_token', 'ssl',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'ssl' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Domain $domain) {
            $domain->domain = strtolower(trim($domain->domain));
            $domain->verification_token = $domain->verification_token ?: Str::random(32);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isGlobal(): bool
    {
        return $this->user_id === null;
    }

    public function url(): string
    {
        return ($this->ssl ? 'https://' : 'http://') . $this->domain;
    }
}
