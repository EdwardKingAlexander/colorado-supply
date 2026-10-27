<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts an operator when DIBBS RFQ ingestion fails (blocked, layout change, import error).
 *
 * Not sent for a day whose files are simply not posted yet: DLA publishes a
 * day's files the next morning, so "missing" is normal.
 */
class DibbsIngestionFailedNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, string>  $failures  One line per failed file or step
     */
    public function __construct(
        public string $reason,
        public array $failures = [],
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->error()
            ->subject('DIBBS RFQ download failed')
            ->line('The daily DIBBS RFQ download did not complete.')
            ->line('Reason: '.$this->reason);

        foreach (array_slice($this->failures, 0, 10) as $failure) {
            $mail->line('• '.$failure);
        }

        return $mail
            ->line('You can download the day\'s inYYMMDD.txt / bqYYMMDD.zip from DIBBS (Solicitations → RFQs → Recent) and upload them on the "Upload DIBBS File" page.')
            ->action('Open DIBBS upload', url('/admin/dibbs-upload'));
    }
}
