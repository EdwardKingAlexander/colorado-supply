<?php

namespace App\Notifications;

use App\Models\BusinessDeadline;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeadlineApproachingNotification extends Notification implements ShouldQueue
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
        $daysUntil = $this->deadline->daysUntilDue();
        $urgency = $daysUntil <= 7 ? 'urgent' : 'upcoming';

        $message = (new MailMessage)
            ->subject("Deadline Reminder: {$this->deadline->title} - Due in {$daysUntil} days")
            ->greeting('Deadline Reminder');

        if ($daysUntil <= 7) {
            $message->error();
        } elseif ($daysUntil <= 14) {
            $message->line('**This deadline is approaching soon.**');
        }

        $message->line("**{$this->deadline->title}**")
            ->line("Due Date: {$this->deadline->due_date->format('l, F j, Y')}")
            ->line("Days Remaining: {$daysUntil}");

        if ($this->deadline->description) {
            $message->line("Details: {$this->deadline->description}");
        }

        $message->line("Category: {$this->deadline->category->label()}");

        if ($this->deadline->recurrence->value !== 'once') {
            $message->line("Recurrence: {$this->deadline->recurrence->label()}");
        }

        if ($this->deadline->external_url) {
            $message->action('Open Filing Portal', $this->deadline->external_url);
        } else {
            $message->action('View in Business Hub', url('/admin/business-deadlines'));
        }

        $message->line('Please ensure this deadline is addressed promptly.');

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'deadline_id' => $this->deadline->id,
            'title' => $this->deadline->title,
            'due_date' => $this->deadline->due_date->toIso8601String(),
            'days_until' => $this->deadline->daysUntilDue(),
        ];
    }
}
