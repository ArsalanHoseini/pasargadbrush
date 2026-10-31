<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing('customer', 'items', 'shippingAddress');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'سفارش جدید '.$this->order->order_number.' — '.$this->order->customer?->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-received',
        );
    }
}
