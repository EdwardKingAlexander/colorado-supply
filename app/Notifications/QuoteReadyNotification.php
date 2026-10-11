<?php

namespace App\Notifications;

use App\Models\Quote;

/** Sent to the buyer's account when staff mark their quote as sent. */
class QuoteReadyNotification extends AppNotification
{
    public function __construct(public Quote $quote) {}

    public function type(): NotificationType
    {
        return NotificationType::QuoteReady;
    }

    public function title(object $notifiable): string
    {
        return "Your quote {$this->quote->quote_number} is ready";
    }

    public function body(object $notifiable): string
    {
        $total = (float) $this->quote->grand_total > 0
            ? 'Total $'.number_format((float) $this->quote->grand_total, 2).'. '
            : '';

        return $total.'Reply to this email or call us to place the order.';
    }

    protected function extraData(object $notifiable): array
    {
        return ['quote_id' => $this->quote->id, 'quote_number' => $this->quote->quote_number];
    }
}
