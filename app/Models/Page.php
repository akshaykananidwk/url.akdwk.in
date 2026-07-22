<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'meta_title', 'meta_description', 'active', 'show_in_footer'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'show_in_footer' => 'boolean'];
    }
}
