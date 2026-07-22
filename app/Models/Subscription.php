<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'cycle', 'status', 'gateway', 'gateway_id',
        'auto_renew', 'starts_at', 'ends_at', 'cancelled_at', 'dunning_attempts',
    ];

    protected function casts(): array
    {
        return [
            'auto_renew' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->cycle === 'lifetime' || $this->ends_at === null || $this->ends_at->isFuture());
    }
}
