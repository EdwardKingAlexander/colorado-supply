<?php

namespace App\Notifications;

use App\Services\Dibbs\DibbsDigestBuilder;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Morning DIBBS digest (ai/modules/dibbs-rfq-tracking, Phase 5).
 *
 * @see DibbsDigestBuilder for the data shape.
 */
class DibbsDailyDigestNotification extends Notification
{
    use Queueable;

    public function __construct(public array $digest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->markdown('emails.dibbs-digest', [
                'digest' => $this->digest,
                'listUrl' => route('filament.admin.resources.dibbs-rfqs.index'),
            ]);
    }

    public function subject(): string
    {
        $counts = collect($this->digest['sections'])
            ->except('closing')
            ->sum('total');
        $closing = $this->digest['sections']['closing']['total'] ?? 0;

        $parts = array_filter([
            $counts ? "{$counts} new" : null,
            $closing ? "{$closing} closing soon" : null,
        ]);

        $day = $this->digest['posting_date'] ? ' ('.Carbon::parse($this->digest['posting_date'])->format('M j').')' : '';

        return 'DIBBS RFQs: '.(implode(', ', $parts) ?: 'nothing new').$day;
    }
}
