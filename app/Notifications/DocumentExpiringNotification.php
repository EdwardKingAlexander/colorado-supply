<?php

namespace App\Notifications;

use App\Models\BusinessDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BusinessDocument $document
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysUntil = $this->document->daysUntilExpiration();
        $isUrgent = $daysUntil <= 14;

        $message = (new MailMessage)
            ->subject("Document Expiring: {$this->document->name} - {$daysUntil} days remaining")
            ->greeting('Document Expiration Notice');

        if ($isUrgent) {
            $message->error()
                ->line('**URGENT: This document requires immediate attention.**');
        }

        $message->line("**{$this->document->name}**")
            ->line("Expiration Date: {$this->document->expiration_date->format('l, F j, Y')}")
            ->line("Days Remaining: {$daysUntil}");

        $message->line("Document Type: {$this->document->type->label()}");

        if ($this->document->issuing_authority) {
            $message->line("Issuing Authority: {$this->document->issuing_authority}");
        }

        if ($this->document->document_number) {
            $message->line("Document Number: {$this->document->document_number}");
        }

        $message->action('View Document', url('/admin/business-documents/' . $this->document->id));

        $message->line('Please renew this document before the expiration date to maintain compliance.');

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'name' => $this->document->name,
            'expiration_date' => $this->document->expiration_date->toIso8601String(),
            'days_until' => $this->document->daysUntilExpiration(),
        ];
    }
}
