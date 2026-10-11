<?php

namespace App\Console\Commands;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Enums\PickupStatus;
use App\Models\Order;
use App\Notifications\Admin\PickupUncollectedAlert;
use App\Notifications\PickupReminderNotification;
use App\Services\Orders\WillCallPickupService;
use App\Support\AdminNotifier;
use App\Support\OrderNotifier;
use Illuminate\Console\Command;

/**
 * Reminds buyers about will call orders still waiting at the counter and
 * alerts admins about long-uncollected ones (ai/modules/will-call-notifications,
 * phase 4). Each reminder is sent once per "ready" (recorded in orders.meta),
 * so running it more than once a day is harmless.
 */
class SendPickupReminders extends Command
{
    protected $signature = 'will-call:remind';

    protected $description = 'Remind buyers about uncollected will call orders and alert admins about old ones';

    public function handle(): int
    {
        $buyerDays = collect(config('will_call.buyer_reminder_days', []))->map(fn ($days) => (int) $days)->filter()->sort()->values();
        $adminDays = (int) config('will_call.admin_alert_days', 0);
        $sent = 0;

        Order::withoutGlobalScopes()
            ->with('pickupLocation')
            ->where('fulfillment_method', FulfillmentMethod::WillCall)
            ->where('pickup_status', PickupStatus::ReadyForPickup)
            ->where('status', '!=', OrderStatus::Cancelled)
            ->whereNull('deleted_at')
            ->whereNotNull('ready_for_pickup_at')
            ->chunkById(100, function ($orders) use ($buyerDays, $adminDays, &$sent) {
                foreach ($orders as $order) {
                    $sent += $this->remind($order, $buyerDays->all(), $adminDays);
                }
            });

        $this->info("Sent {$sent} pickup reminders.");

        return self::SUCCESS;
    }

    /**
     * @param  list<int>  $buyerDays
     */
    private function remind(Order $order, array $buyerDays, int $adminDays): int
    {
        $waited = (int) $order->ready_for_pickup_at->diffInDays(now());
        $meta = $order->meta ?? [];
        $done = $meta[WillCallPickupService::REMINDERS_META_KEY] ?? [];
        $sent = 0;

        // Only the latest reminder that is due: an order found late (say at
        // day 8) gets one 7-day reminder, not both.
        $due = collect($buyerDays)->filter(fn (int $days) => $waited >= $days)->last();

        if ($due !== null && ! isset($done["buyer_{$due}"])) {
            OrderNotifier::send($order, new PickupReminderNotification($order, $waited));
            foreach ($buyerDays as $days) {
                if ($days <= $due) {
                    $done["buyer_{$days}"] ??= now()->toIso8601String();
                }
            }
            $sent++;
        }

        if ($adminDays > 0 && $waited >= $adminDays && ! isset($done['admin'])) {
            AdminNotifier::send(new PickupUncollectedAlert($order, $waited));
            $done['admin'] = now()->toIso8601String();
            $sent++;
        }

        if ($sent > 0) {
            $meta[WillCallPickupService::REMINDERS_META_KEY] = $done;
            $order->forceFill(['meta' => $meta])->saveQuietly();
        }

        return $sent;
    }
}
