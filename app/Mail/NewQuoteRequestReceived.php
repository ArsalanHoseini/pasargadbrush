<?php

namespace App\Mail;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewQuoteRequestReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public QuoteRequest $quoteRequest)
    {
        $this->quoteRequest->loadMissing('product');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'درخواست استعلام قیمت جدید — '.$this->quoteRequest->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quote-request-received',
        );
    }
}
