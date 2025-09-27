<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Address;

class ContactAutoReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your message',
            // If they reply to the auto-reply, it goes to your inbox:
            replyTo: [new Address('Edward@cogovsupply.com', 'Colorado Supply & Procurement')],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-autoreply',
            with: ['data' => $this->data],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
