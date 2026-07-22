<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = ['code', 'name', 'rtl', 'active', 'is_default'];

    protected function casts(): array
    {
        return ['rtl' => 'boolean', 'active' => 'boolean', 'is_default' => 'boolean'];
    }
}
