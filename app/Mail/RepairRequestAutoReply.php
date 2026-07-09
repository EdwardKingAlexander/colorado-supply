<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RepairRequestAutoReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your repair request',
            // If they reply to the auto-reply, it goes to your inbox:
            replyTo: [new Address('Edward@cogovsupply.com', 'Colorado Supply & Procurement')],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.repair-request-autoreply',
            with: ['data' => $this->data],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
