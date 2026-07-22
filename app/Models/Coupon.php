<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'type', 'value', 'max_uses', 'used_count', 'plan_ids', 'expires_at', 'active'];

    protected function casts(): array
    {
        return [
            'plan_ids' => 'array',
            'expires_at' => 'datetime',
            'active' => 'boolean',
            'value' => 'decimal:2',
        ];
    }

    public function isValidFor(?int $planId = null): bool
    {
        if (! $this->active) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }
        if ($planId && $this->plan_ids && ! in_array($planId, array_map('intval', $this->plan_ids), true)) {
            return false;
        }

        return true;
    }

    public function discountOn(float $amount): float
    {
        return round($this->type === 'percent'
            ? $amount * ((float) $this->value / 100)
            : min((float) $this->value, $amount), 2);
    }
}
