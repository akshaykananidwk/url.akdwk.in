<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertChannel extends Model
{
    public const TYPES = ['slack' => 'Slack', 'discord' => 'Discord', 'telegram' => 'Telegram'];

    protected $fillable = ['user_id', 'type', 'target', 'instant', 'milestone', 'active'];

    protected function casts(): array
    {
        return ['instant' => 'boolean', 'active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
