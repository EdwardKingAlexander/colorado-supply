<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public, signed-URL order status tracker. Reached from the link in order
 * emails — deliberately requires no login and no email verification, so
 * unverified users and guest purchasers can always see their order status.
 *
 * Only buyer-safe fields leave this controller: signed links get forwarded,
 * so no internal notes, margins, or full addresses.
 */
class OrderTrackingController extends Controller
{
    public function __invoke(Order $order): Response
    {
        $order->load(['items', 'shipments']);

        return Inertia::render('Store/OrderTracker', [
            'order' => [
                'order_number' => $order->order_number,
                'placed_at' => $order->created_at?->format('F j, Y'),
                'status' => [
                    'value' => $order->status->value,
                    'label' => $order->status->label(),
                ],
                'payment_status' => [
                    'value' => $order->payment_status->value,
                    'label' => ucwords(str_replace('_', ' ', $order->payment_status->value)),
                ],
                'fulfillment_status' => [
                    'value' => $order->fulfillment_status->value,
                    'label' => ucwords(str_replace('_', ' ', $order->fulfillment_status->value)),
                ],
                'items' => $order->items->map(fn ($item) => [
                    'name' => $item->name,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'line_total' => (float) $item->line_total,
                ])->values(),
                'subtotal' => (float) $order->subtotal,
                'shipping_total' => (float) $order->shipping_total,
                'tax_total' => (float) $order->tax_total,
                'grand_total' => (float) $order->grand_total,
                'shipments' => $order->shipments->map(fn ($shipment) => [
                    'carrier' => $shipment->carrier,
                    'tracking_number' => $shipment->tracking_number,
                    'shipped_at' => $shipment->created_at?->format('F j, Y'),
                ])->values(),
            ],
        ]);
    }
}
