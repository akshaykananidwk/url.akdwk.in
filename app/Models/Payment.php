<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'subscription_id', 'cycle', 'gateway', 'gateway_reference',
        'invoice_number', 'amount', 'tax_amount', 'discount_amount', 'total', 'currency',
        'status', 'coupon_id', 'tax_details', 'meta', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'tax_details' => 'array',
            'meta' => 'array',
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
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

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /** Assign the next sequential invoice number (INV-000123). */
    public function assignInvoiceNumber(): void
    {
        if ($this->invoice_number) {
            return;
        }
        $next = (int) setting('invoice_counter', 0) + 1;
        setting_set('invoice_counter', $next);
        $this->forceFill(['invoice_number' => sprintf('%s%06d', setting('invoice_prefix', 'INV-'), $next)])->save();
    }
}
