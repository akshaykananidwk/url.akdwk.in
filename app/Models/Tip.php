<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tip extends Model
{
    protected $fillable = [
        'bio_page_id', 'user_id', 'supporter_name', 'supporter_email', 'message',
        'amount', 'currency', 'gateway', 'gateway_reference', 'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function bioPage(): BelongsTo
    {
        return $this->belongsTo(BioPage::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
