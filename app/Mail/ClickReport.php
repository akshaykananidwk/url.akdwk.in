<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ClickReport extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public array $totals,
        public Collection $topLinks,
        public int $days,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Your :days-day link report — :site', ['days' => $this->days, 'site' => site_name()]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.click-report');
    }
}
