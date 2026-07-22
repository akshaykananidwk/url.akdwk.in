<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $fillable = ['name', 'country', 'rate', 'tax_id_label', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'rate' => 'decimal:2'];
    }

    /** Best matching active tax rate for a country (specific country beats global). */
    public static function forCountry(?string $country): ?TaxRate
    {
        return static::where('active', true)
            ->where(function ($q) use ($country) {
                $q->whereNull('country');
                if ($country) {
                    $q->orWhere('country', strtoupper($country));
                }
            })
            ->orderByRaw('country is null')
            ->first();
    }
}
