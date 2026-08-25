<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts an operator when a SAM.gov fetch fails.
 *
 * The fetch broke on 2026-07-09 and was not noticed until 2026-08-24 because
 * nothing reported it: the job caught its own errors, logged them, and reported
 * "completed". This notification is the signal that was missing.
 *
 * It deliberately does NOT fire on a successful fetch that returns zero
 * opportunities — that is a normal outcome for a narrow query, and alerting on
 * it would train the recipient to ignore these emails.
 */
class SamFetchFailedNotification extends Notification
{
    use Queueable;

    /**
     * @param  string  $reason  Primary error message
     * @param  array  $failedNaics  Per-NAICS error details
     * @param  string|null  $endpoint  Endpoint that was queried
     */
    public function __construct(
        public string $reason,
        public array $failedNaics = [],
        public ?string $endpoint = null,
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
            ->subject('SAM.gov opportunity fetch failed')
            ->line('The scheduled SAM.gov opportunity fetch did not complete successfully.')
            ->line('Reason: '.$this->reason);

        if ($this->endpoint) {
            $mail->line('Endpoint: '.$this->endpoint);
        }

        $distinct = collect($this->failedNaics)
            ->map(fn (array $e) => trim(($e['naics'] ?? 'unknown').' — '.($e['message'] ?? 'Unknown error')))
            ->unique()
            ->take(10);

        if ($distinct->isNotEmpty()) {
            $mail->line('Failed NAICS codes:');

            foreach ($distinct as $line) {
                $mail->line('• '.$line);
            }
        }

        return $mail
            ->line('Run `php artisan sam:diagnose` to identify the cause.')
            ->line('No new opportunities were recorded for this run.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reason' => $this->reason,
            'endpoint' => $this->endpoint,
            'failed_naics_count' => count($this->failedNaics),
        ];
    }
}
