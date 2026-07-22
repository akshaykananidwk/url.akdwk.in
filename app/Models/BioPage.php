<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BioPage extends Model
{
    protected $fillable = [
        'user_id', 'domain_id', 'username', 'title', 'bio', 'avatar', 'cover',
        'theme', 'colors', 'font', 'seo', 'socials', 'active',
    ];

    protected function casts(): array
    {
        return [
            'colors' => 'array',
            'seo' => 'array',
            'socials' => 'array',
            'active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(BioBlock::class)->orderBy('sort_order');
    }

    public function subscribers(): HasMany
    {
        return $this->hasMany(BioSubscriber::class);
    }

    public function url(): string
    {
        $host = $this->domain?->domain
            ? $this->domain->url()
            : rtrim(config('app.url'), '/');

        return $host . '/@' . $this->username;
    }

    /** Blocks currently visible (active + within schedule window). */
    public function visibleBlocks()
    {
        $now = now();

        return $this->blocks->filter(fn (BioBlock $b) => $b->active
            && (! $b->starts_at || $b->starts_at->lte($now))
            && (! $b->ends_at || $b->ends_at->gte($now)));
    }
}
