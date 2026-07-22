<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DunningNotice extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscription $subscription, public int $attempt)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Action needed — your :site subscription payment', ['site' => site_name()]));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.dunning', with: [
            'subscription' => $this->subscription,
            'attempt' => $this->attempt,
        ]);
    }
}
