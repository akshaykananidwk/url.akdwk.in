<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Webhook extends Model
{
    public const EVENTS = [
        'click.created' => 'Click recorded',
        'link.created' => 'Link created',
        'link.updated' => 'Link updated',
        'link.deleted' => 'Link deleted',
    ];

    protected $fillable = ['user_id', 'url', 'events', 'secret', 'active', 'failures'];

    protected function casts(): array
    {
        return ['events' => 'array', 'active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
