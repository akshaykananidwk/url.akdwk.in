<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBadge extends Model
{
    protected $fillable = ['user_id', 'badge_key', 'meta', 'awarded_at'];

    // The table tracks `awarded_at` only — no created_at/updated_at columns.
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'awarded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
