<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderUpdated extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  list<string>  $lines  What changed, in customer-facing words.
     */
    public function __construct(public Order $order, public array $lines)
    {
        $this->order->loadMissing('items', 'shippingAddress');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'به‌روزرسانی سفارش '.$this->order->order_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-updated',
        );
    }
}
