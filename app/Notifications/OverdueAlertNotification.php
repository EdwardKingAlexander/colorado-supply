<?php

namespace App\Notifications;

use App\Models\BusinessDeadline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BusinessDeadline $deadline
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $daysOverdue = abs($this->deadline->daysUntilDue());

        $message = (new MailMessage)
            ->subject("OVERDUE: {$this->deadline->title} - {$daysOverdue} days past due")
            ->greeting('Overdue Deadline Alert')
            ->error()
            ->line('**ACTION REQUIRED: This deadline is overdue.**')
            ->line("**{$this->deadline->title}**")
            ->line("Original Due Date: {$this->deadline->due_date->format('l, F j, Y')}")
            ->line("Days Overdue: {$daysOverdue}");

        if ($this->deadline->description) {
            $message->line("Details: {$this->deadline->description}");
        }

        $message->line("Category: {$this->deadline->category->label()}");

        if ($this->deadline->external_url) {
            $message->action('Complete Now', $this->deadline->external_url);
        } else {
            $message->action('View in Business Hub', url('/admin/business-deadlines'));
        }

        $message->line('Please address this overdue deadline immediately to avoid penalties or compliance issues.');

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'deadline_id' => $this->deadline->id,
            'title' => $this->deadline->title,
            'due_date' => $this->deadline->due_date->toIso8601String(),
            'days_overdue' => abs($this->deadline->daysUntilDue()),
        ];
    }
}
