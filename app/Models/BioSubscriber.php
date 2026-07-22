<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BioSubscriber extends Model
{
    protected $fillable = ['bio_page_id', 'bio_block_id', 'email'];
}
