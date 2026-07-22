<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    protected $fillable = ['slug', 'name', 'version', 'enabled', 'meta'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'meta' => 'array'];
    }
}
