<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoicePaid extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Payment received — invoice :number', ['number' => $this->payment->invoice_number]));
    }

    public function content(): Content
    {
        return new Content(view: 'mail.invoice-paid', with: ['payment' => $this->payment]);
    }
}
