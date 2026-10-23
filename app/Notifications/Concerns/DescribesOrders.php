<?php

namespace App\Notifications\Concerns;

use App\Filament\Resources\CRM\OrderResource;
use App\Models\Order;
use Illuminate\Support\Facades\URL;

/**
 * Shared wording and links for order notifications
 * (ai/modules/will-call-notifications-complete, phase 4).
 */
trait DescribesOrders
{
    /** The buyer's no-login order tracker. */
    protected function trackerUrl(Order $order): string
    {
        return URL::signedRoute('orders.track', ['order' => $order]);
    }

    /** The order in the admin panel. */
    protected function adminOrderUrl(Order $order): string
    {
        return OrderResource::getUrl('view', ['record' => $order], panel: 'admin');
    }

    protected function money(mixed $amount): string
    {
        return '$'.number_format((float) $amount, 2);
    }

    protected function pickupName(Order $order): string
    {
        return $order->pickupLocation?->name ?? 'our will call counter';
    }

    /**
     * Address, hours, phone and instructions for the pickup counter, each
     * only when set.
     *
     * @return list<string>
     */
    protected function pickupLines(Order $order): array
    {
        $location = $order->pickupLocation;

        return array_values(array_filter([
            $location?->formattedAddress() ? 'Address: '.$location->formattedAddress() : null,
            filled($location?->hours) ? 'Hours: '.str_replace("\n", '; ', trim($location->hours)) : null,
            filled($location?->phone) ? 'Phone: '.$location->phone : null,
            filled($location?->pickup_instructions) ? $location->pickup_instructions : null,
        ]));
    }

    protected function buyerName(Order $order): string
    {
        return $order->company_name ?: $order->contact_name ?: 'A buyer';
    }

    protected function orderExtraData(Order $order): array
    {
        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'tracker_url' => $this->trackerUrl($order),
        ];
    }
}
