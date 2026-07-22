<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Click extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'link_id', 'user_id', 'ip_hash', 'country', 'region', 'city', 'language',
        'os', 'browser', 'device', 'referer_host', 'referer_url', 'isp',
        'is_unique', 'is_qr', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_unique' => 'boolean',
            'is_qr' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
