<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversion extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['link_id', 'user_id', 'value', 'label', 'ip_hash', 'created_at'];

    protected function casts(): array
    {
        return ['value' => 'decimal:2', 'created_at' => 'datetime'];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
