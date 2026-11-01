<?php

namespace App\Mail;

use App\Models\ConversationMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewStaffReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ConversationMessage $conversationMessage)
    {
        $this->conversationMessage->loadMissing('conversation.customer', 'attachments');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'پاسخ جدید کارشناس — '.$this->conversationMessage->conversation->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.conversation-staff-reply',
        );
    }
}
