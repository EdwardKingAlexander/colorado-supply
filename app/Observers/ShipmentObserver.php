<?php

namespace App\Observers;

use App\Models\Shipment;
use App\Notifications\OrderStatusUpdated;
use App\Support\OrderNotifier;

class ShipmentObserver
{
    public function created(Shipment $shipment): void
    {
        $transition = $this->transitionFor($shipment, true);

        if ($transition === null) {
            return;
        }

        $this->notify($shipment, $transition);
    }

    public function updated(Shipment $shipment): void
    {
        $transition = $this->transitionFor($shipment);

        if ($transition === null) {
            return;
        }

        $this->notify($shipment, $transition);
    }

    protected function transitionFor(Shipment $shipment, bool $created = false): ?string
    {
        if (($created || $shipment->wasChanged(['status', 'delivered_at']))
            && ($shipment->status === 'delivered' || $shipment->delivered_at !== null)) {
            return 'delivered';
        }

        if (($created || $shipment->wasChanged(['status', 'shipped_at']))
            && ($shipment->status === 'shipped' || $shipment->shipped_at !== null)) {
            return 'shipped';
        }

        return null;
    }

    protected function notify(Shipment $shipment, string $transition): void
    {
        if (! $shipment->order) {
            return;
        }

        OrderNotifier::send($shipment->order, new OrderStatusUpdated($shipment->order, $transition, [
            'carrier' => $shipment->carrier,
            'tracking_number' => $shipment->tracking_number,
        ]));
    }
}
