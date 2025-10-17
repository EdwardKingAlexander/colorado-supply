<?php

namespace App\Mail;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order Confirmation – {$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmed',
        );
    }

    public function attachments(): array
    {
        // Generate PDF invoice and attach it
        $pdf = Pdf::loadView('pdf.invoice', ['order' => $this->order]);

        return [
            Attachment::fromData(fn() => $pdf->output(), "invoice-{$this->order->order_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
