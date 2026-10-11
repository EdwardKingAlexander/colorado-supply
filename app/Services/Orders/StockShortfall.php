<?php

namespace App\Services\Orders;

use App\Models\LocationProduct;
use App\Models\Order;
use App\Models\Product;
use App\Models\Scopes\CompanyScope;

/**
 * Order lines that current stock can't cover (orders may exceed stock and
 * become backorders). Stock is read at $locationId when given, else the
 * line's location, else the will-call pickup location; with no location
 * stock record it falls back to the product's own stock count. Lines with
 * no stock figure at all are not reported.
 */
class StockShortfall
{
    /**
     * @return list<array{name: string, ordered: int, on_hand: int}>
     */
    public function lines(Order $order, ?int $locationId = null): array
    {
        $order->loadMissing('items');
        $short = [];

        foreach ($order->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $lineLocationId = $locationId ?? $item->location_id ?? $order->pickup_location_id;

            $onHand = $lineLocationId
                ? LocationProduct::query()
                    ->where('location_id', $lineLocationId)
                    ->where('product_id', $item->product_id)
                    ->value('on_hand')
                : null;

            $onHand ??= Product::query()->withoutGlobalScope(CompanyScope::class)->whereKey($item->product_id)->value('stock');

            if ($onHand !== null && (int) $onHand < (int) $item->quantity) {
                $short[] = ['name' => $item->name, 'ordered' => (int) $item->quantity, 'on_hand' => max(0, (int) $onHand)];
            }
        }

        return $short;
    }
}
