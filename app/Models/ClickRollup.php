<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClickRollup extends Model
{
    protected $fillable = ['link_id', 'user_id', 'date', 'clicks', 'uniques', 'qr_scans', 'breakdown'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'breakdown' => 'array',
        ];
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
