<?php

namespace App\Services\Orders;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PickupStatus;
use App\Events\OrderPickedUp;
use App\Events\OrderReadyForPickup;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Will-call pickup workflow: awaiting pick → ready for pickup → picked up.
 * Transitions are enforced here, not only by which buttons are visible.
 */
class WillCallPickupService
{
    /** No 0/O, 1/I/L, 2/Z, 5/S or 8/B, so a code read over the counter is unambiguous. */
    private const CODE_ALPHABET = 'ACDEFGHJKMNPQRTUVWXY34679';

    private const CODE_LENGTH = 4;

    /** orders.meta key recording which uncollected-pickup reminders went out. */
    public const REMINDERS_META_KEY = 'pickup_reminders';

    public function __construct(private StockShortfall $shortfall) {}

    public function canMarkReady(Order $order): bool
    {
        return $this->isOpenWillCall($order) && $order->pickup_status === PickupStatus::AwaitingPick;
    }

    public function canMarkPickedUp(Order $order): bool
    {
        return $this->isOpenWillCall($order) && $order->pickup_status === PickupStatus::ReadyForPickup;
    }

    public function canRevertToAwaitingPick(Order $order): bool
    {
        return $this->canMarkPickedUp($order);
    }

    public function markReady(Order $order): Order
    {
        $this->guard($this->canMarkReady($order), 'This order is not waiting to be picked.');

        DB::transaction(function () use ($order) {
            $meta = $order->meta ?? [];
            // A fresh "ready" restarts the uncollected-pickup reminder clock.
            unset($meta[self::REMINDERS_META_KEY]);

            $order->forceFill([
                'pickup_status' => PickupStatus::ReadyForPickup,
                'pickup_code' => $order->pickup_code ?? $this->uniqueCode(),
                'ready_for_pickup_at' => now(),
                'meta' => $meta,
            ])->save();
        });

        OrderReadyForPickup::dispatch($order);

        return $order;
    }

    /**
     * @param  string  $collectedBy  name of the person collecting the order
     * @param  string|null  $code  code the collector gave; checked unless $overrideCode
     */
    public function markPickedUp(Order $order, string $collectedBy, ?string $code = null, bool $overrideCode = false): Order
    {
        $this->guard($this->canMarkPickedUp($order), 'This order is not ready for pickup.');

        if (blank($collectedBy)) {
            throw ValidationException::withMessages(['picked_up_by' => 'Enter who collected the order.']);
        }

        if (! $overrideCode && ! $this->codeMatches($order, $code)) {
            throw ValidationException::withMessages(['pickup_code' => 'The pickup code does not match this order.']);
        }

        $order->forceFill([
            'pickup_status' => PickupStatus::PickedUp,
            'picked_up_at' => now(),
            'picked_up_by' => trim($collectedBy),
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
            'fulfilled_at' => $order->fulfilled_at ?? now(),
        ])->save();

        OrderPickedUp::dispatch($order);

        return $order;
    }

    /**
     * Undo "ready" (e.g. marked by mistake). The code is kept so a buyer who
     * already received it is not confused when it is marked ready again.
     */
    public function revertToAwaitingPick(Order $order): Order
    {
        $this->guard($this->canRevertToAwaitingPick($order), 'Only orders that are ready for pickup can be moved back.');

        $order->forceFill([
            'pickup_status' => PickupStatus::AwaitingPick,
            'ready_for_pickup_at' => null,
        ])->save();

        return $order;
    }

    public function codeMatches(Order $order, ?string $code): bool
    {
        $normalize = fn (?string $value) => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $value));

        return filled($order->pickup_code) && $normalize($code) === $normalize($order->pickup_code);
    }

    /**
     * Lines the pickup location (or the product, when the location has no
     * stock record) can't currently cover — shown as a warning before an
     * order is marked ready, since orders may exceed stock (backorders).
     *
     * @return list<array{name: string, ordered: int, on_hand: int}>
     */
    public function shortLines(Order $order): array
    {
        // Everything is handed over at the pickup counter, so its stock counts.
        return $this->shortfall->lines($order, $order->pickup_location_id);
    }

    private function isOpenWillCall(Order $order): bool
    {
        return $order->isWillCall() && $order->status !== OrderStatus::Cancelled;
    }

    private function guard(bool $allowed, string $message): void
    {
        if (! $allowed) {
            throw ValidationException::withMessages(['pickup_status' => $message]);
        }
    }

    private function uniqueCode(): string
    {
        $last = strlen(self::CODE_ALPHABET) - 1;

        for ($attempt = 0; $attempt < 20; $attempt++) {
            $code = 'WC-';

            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, $last)];
            }

            if (! Order::withoutGlobalScopes()->where('pickup_code', $code)->exists()) {
                return $code;
            }
        }

        // 25^4 ≈ 390k codes; 20 straight collisions means the space needs widening.
        throw new \RuntimeException('Could not generate a unique pickup code.');
    }
}
