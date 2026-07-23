<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BioBlock extends Model
{
    public const TYPES = [
        'link' => 'Link button', 'heading' => 'Heading', 'text' => 'Text', 'image' => 'Image',
        'video' => 'Video embed', 'email_form' => 'Email signup', 'whatsapp' => 'WhatsApp button',
        'phone' => 'Phone button', 'vcard' => 'vCard download', 'socials' => 'Social icons',
        'music' => 'Music / podcast',
        'tip' => 'Tip jar / donations',
        'app' => 'App download (smart)',
        'divider' => 'Divider',
    ];

    protected $fillable = ['bio_page_id', 'type', 'content', 'sort_order', 'active', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(BioPage::class, 'bio_page_id');
    }
}
