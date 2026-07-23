<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtmTemplate extends Model
{
    protected $fillable = ['user_id', 'name', 'source', 'medium', 'campaign', 'term', 'content'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The UTM params as the link JSON column expects them. */
    public function toUtm(): array
    {
        return array_filter([
            'source' => $this->source, 'medium' => $this->medium, 'campaign' => $this->campaign,
            'term' => $this->term, 'content' => $this->content,
        ]);
    }
}
