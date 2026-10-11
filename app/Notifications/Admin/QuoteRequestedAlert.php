<?php

namespace App\Notifications\Admin;

use App\Filament\Resources\CRM\QuoteResource;
use App\Models\Quote;
use App\Notifications\AppNotification;
use App\Notifications\NotificationType;

/** Staff alert: a buyer asked for a quote from a product page. */
class QuoteRequestedAlert extends AppNotification
{
    public function __construct(public Quote $quote) {}

    public function type(): NotificationType
    {
        return NotificationType::AdminQuoteRequested;
    }

    public function title(object $notifiable): string
    {
        return "Quote requested: {$this->quote->quote_number}";
    }

    public function body(object $notifiable): string
    {
        $this->quote->loadMissing('items');
        $who = $this->quote->walk_in_org ?: $this->quote->walk_in_contact_name ?: 'A buyer';
        $lines = $this->quote->items
            ->map(fn ($item) => rtrim(rtrim(number_format((float) $item->qty, 2), '0'), '.')." × {$item->name}")
            ->implode(', ');

        return trim("{$who} asked for a quote".($lines !== '' ? " on {$lines}" : '').'.'
            .(filled($this->quote->notes) ? ' Note: '.str($this->quote->notes)->limit(140) : ''));
    }

    public function url(object $notifiable): ?string
    {
        return QuoteResource::getUrl('view', ['record' => $this->quote], panel: 'admin');
    }

    protected function actionLabel(): string
    {
        return 'Open quote';
    }

    protected function extraData(object $notifiable): array
    {
        return ['quote_id' => $this->quote->id, 'quote_number' => $this->quote->quote_number];
    }
}
