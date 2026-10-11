<?php

use App\Enums\FulfillmentMethod;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PickupStatus;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Location;
use App\Models\LocationProduct;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use App\Notifications\Admin\BackorderAlert;
use App\Notifications\Admin\NewOrderAlert;
use App\Notifications\Admin\PaymentAlert;
use App\Notifications\Admin\PickupUncollectedAlert;
use App\Notifications\Admin\QuoteRequestedAlert;
use App\Notifications\NotificationType;
use App\Notifications\OrderReceivedNotification;
use App\Notifications\OrderStatusUpdated;
use App\Notifications\PickedUpNotification;
use App\Notifications\PickupReminderNotification;
use App\Notifications\QuoteReadyNotification;
use App\Notifications\ReadyForPickupNotification;
use App\Services\Orders\WillCallPickupService;
use App\Services\Store\ProductQuoteRequestService;
use App\Services\Stripe\StripePaymentSyncService;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Stripe\PaymentIntent;

function storeCheckout($test, User $buyer, Product $product, int $quantity, array $overrides = [])
{
    return $test->actingAs($buyer, 'sanctum')->postJson('/api/v1/store/checkout', array_merge([
        'items' => [['id' => $product->id, 'product_id' => $product->id, 'name' => $product->name, 'quantity' => $quantity]],
        'contact_name' => 'Jane Buyer',
        'contact_email' => 'jane@example.com',
        'company_name' => 'Acme Fab',
        'billing_address' => ['line1' => '1 Main St', 'city' => 'Denver', 'state' => 'CO', 'postal_code' => '80202', 'country' => 'US'],
        'shipping_same_as_billing' => true,
    ], $overrides));
}

function remindOn($test, string $date): void
{
    Carbon::setTestNow($date);
    $test->artisan('will-call:remind')->assertSuccessful();
}

function willCallOrder(array $attributes = []): Order
{
    return Order::factory()->create(array_merge([
        'fulfillment_method' => FulfillmentMethod::WillCall,
        'pickup_location_id' => Location::factory()->pickup()->create(['name' => 'Front Range Will Call'])->id,
        'pickup_status' => PickupStatus::AwaitingPick,
    ], $attributes));
}

beforeEach(function () {
    Notification::fake();
    Mail::fake();
    $this->admin = Admin::factory()->create();
    $this->company = Company::factory()->create();
    $this->buyer = User::factory()->create(['company_id' => $this->company->id]);
    $this->product = Product::factory()->create(['price' => 10, 'is_active' => true, 'minimum_order_quantity' => 1, 'stock' => 100]);
    $this->company->products()->attach($this->product->id);
    $this->counter = Location::factory()->pickup()->create(['company_id' => $this->company->id, 'name' => 'Front Range Will Call']);
});

describe('checkout', function () {
    it('alerts admins to a will call order and confirms it in the buyer bell', function () {
        storeCheckout($this, $this->buyer, $this->product, 2, ['fulfillment_method' => 'will_call', 'pickup_location_id' => $this->counter->id])->assertCreated();

        Notification::assertSentTo($this->buyer, OrderReceivedNotification::class, fn ($n, array $channels) => $channels === ['database']);
        Notification::assertSentTo($this->admin, NewOrderAlert::class, function (NewOrderAlert $alert, array $channels) {
            return $alert->type() === NotificationType::AdminNewWillCallOrder
                && $channels === ['mail', 'database']
                && str_contains($alert->body($this->admin), 'Acme Fab · 1 item · $20.00 · Pickup at Front Range Will Call · Payment: Unpaid');
        });
        Notification::assertNotSentTo($this->admin, BackorderAlert::class);
        Notification::assertNotSentTo($this->buyer, NewOrderAlert::class);
    });

    it('alerts admins to a shipped order as a new order', function () {
        storeCheckout($this, $this->buyer, $this->product, 2)->assertCreated();

        Notification::assertSentTo($this->admin, NewOrderAlert::class, fn (NewOrderAlert $alert) => $alert->type() === NotificationType::AdminNewOrder
            && str_contains($alert->body($this->admin), 'Ships to Denver, CO'));
    });

    it('flags lines that exceed stock as a backorder', function () {
        LocationProduct::create(['location_id' => $this->counter->id, 'product_id' => $this->product->id, 'on_hand' => 3, 'visible' => true]);

        storeCheckout($this, $this->buyer, $this->product, 30, ['fulfillment_method' => 'will_call', 'pickup_location_id' => $this->counter->id])->assertCreated();

        Notification::assertSentTo($this->admin, BackorderAlert::class, fn (BackorderAlert $alert) => $alert->lines === [
            ['name' => $this->product->name, 'ordered' => 30, 'on_hand' => 3],
        ]);
    });
});

describe('will call pickup', function () {
    it('tells the buyer the order is ready with the code and counter details', function () {
        $order = willCallOrder(['portal_user_id' => $this->buyer->id]);

        app(WillCallPickupService::class)->markReady($order);

        Notification::assertSentTo($this->buyer, ReadyForPickupNotification::class, function (ReadyForPickupNotification $notification, array $channels) use ($order) {
            $html = (string) $notification->toMail($this->buyer)->render();

            return $channels === ['mail', 'database']
                && str_contains($html, $order->pickup_code)
                && str_contains($html, '100 Supply Way, Denver, CO 80216')
                && str_contains($html, 'Mon–Fri 7am–5pm')
                && str_contains($notification->body($this->buyer), "Pickup code: {$order->pickup_code}");
        });
    });

    it('emails guest buyers that their order is ready', function () {
        $order = willCallOrder(['contact_email' => 'guest@example.com']);

        app(WillCallPickupService::class)->markReady($order);

        Notification::assertSentOnDemand(ReadyForPickupNotification::class, fn ($n, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'guest@example.com' && $channels === ['mail']);
    });

    it('sends "picked up" instead of the generic "order complete" message', function () {
        $order = willCallOrder(['portal_user_id' => $this->buyer->id]);
        $service = app(WillCallPickupService::class);
        $service->markReady($order);

        $service->markPickedUp($order, 'Sam Driver', $order->pickup_code);

        Notification::assertSentTo($this->buyer, PickedUpNotification::class, fn (PickedUpNotification $n) => str_contains($n->body($this->buyer), 'Collected by Sam Driver'));
        Notification::assertNotSentTo($this->buyer, OrderStatusUpdated::class);
    });

    it('still sends "order complete" when a shipped order is fulfilled', function () {
        $order = Order::factory()->create(['portal_user_id' => $this->buyer->id]);

        $order->update(['fulfillment_status' => 'fulfilled']);

        Notification::assertSentTo($this->buyer, OrderStatusUpdated::class, fn (OrderStatusUpdated $n) => $n->transition === 'fulfilled');
    });
});

describe('uncollected reminders', function () {
    beforeEach(function () {
        Carbon::setTestNow('2026-10-01 09:00:00');
        $this->order = willCallOrder(['portal_user_id' => $this->buyer->id]);
        app(WillCallPickupService::class)->markReady($this->order);
        Notification::fake();
    });

    afterEach(fn () => Carbon::setTestNow());

    it('reminds the buyer at 3 and 7 days and alerts admins at 10, once each', function () {
        remindOn($this, '2026-10-03 09:00:00');
        Notification::assertNothingSent();

        remindOn($this, '2026-10-04 09:00:00');
        remindOn($this, '2026-10-04 15:00:00');
        Notification::assertSentToTimes($this->buyer, PickupReminderNotification::class, 1);

        remindOn($this, '2026-10-08 09:00:00');
        Notification::assertSentToTimes($this->buyer, PickupReminderNotification::class, 2);
        Notification::assertNotSentTo($this->admin, PickupUncollectedAlert::class);

        remindOn($this, '2026-10-11 09:00:00');
        remindOn($this, '2026-10-12 09:00:00');
        Notification::assertSentToTimes($this->admin, PickupUncollectedAlert::class, 1);
        Notification::assertSentToTimes($this->buyer, PickupReminderNotification::class, 2);
    });

    it('sends only the latest due reminder when it first runs late', function () {
        remindOn($this, '2026-10-09 09:00:00');

        Notification::assertSentToTimes($this->buyer, PickupReminderNotification::class, 1);
        Notification::assertSentTo($this->buyer, PickupReminderNotification::class, fn ($n) => $n->days === 8);

        remindOn($this, '2026-10-09 10:00:00');
        Notification::assertSentToTimes($this->buyer, PickupReminderNotification::class, 1);
    });

    it('skips collected, cancelled and not-yet-ready orders', function () {
        $this->order->update(['pickup_status' => PickupStatus::PickedUp]);
        willCallOrder(['portal_user_id' => $this->buyer->id, 'ready_for_pickup_at' => '2026-09-01']);
        willCallOrder([
            'portal_user_id' => $this->buyer->id, 'status' => 'cancelled',
            'pickup_status' => PickupStatus::ReadyForPickup, 'ready_for_pickup_at' => '2026-09-01',
        ]);

        remindOn($this, '2026-10-20 09:00:00');

        Notification::assertNothingSent();
    });

    it('restarts the reminder clock when an order is marked ready again', function () {
        remindOn($this, '2026-10-04 09:00:00');
        Notification::assertSentToTimes($this->buyer, PickupReminderNotification::class, 1);

        $service = app(WillCallPickupService::class);
        $service->revertToAwaitingPick($this->order->fresh());
        $service->markReady($this->order->fresh());

        remindOn($this, '2026-10-07 09:00:00');
        Notification::assertSentToTimes($this->buyer, PickupReminderNotification::class, 2);
    });
});

describe('quotes', function () {
    it('alerts admins when a buyer requests a quote', function () {
        $quote = app(ProductQuoteRequestService::class)->create($this->buyer, $this->product, 25, 'Need by Friday');

        Notification::assertSentTo($this->admin, QuoteRequestedAlert::class, function (QuoteRequestedAlert $alert) use ($quote) {
            return $alert->quote->is($quote)
                && str_contains($alert->body($this->admin), "25 × {$this->product->name}")
                && str_contains($alert->body($this->admin), 'Note: Need by Friday')
                && str_contains($alert->url($this->admin), '/admin/');
        });
        Notification::assertNotSentTo($this->buyer, QuoteRequestedAlert::class);
    });

    it('tells the buyer when their quote is marked sent, once', function () {
        $quote = app(ProductQuoteRequestService::class)->create($this->buyer, $this->product, 5, null);

        $quote->update(['notes' => 'edited']);
        Notification::assertNotSentTo($this->buyer, QuoteReadyNotification::class);

        $quote->update(['status' => 'sent']);
        $quote->update(['notes' => 'edited again']);

        Notification::assertSentToTimes($this->buyer, QuoteReadyNotification::class, 1);
    });

    it('does not message walk-in quote contacts', function () {
        $quote = Quote::factory()->create(['portal_user_id' => null, 'status' => 'draft']);

        $quote->update(['status' => 'sent']);

        Notification::assertSentTimes(QuoteReadyNotification::class, 0);
    });
});

describe('payments', function () {
    it('alerts admins when a card payment succeeds or fails', function () {
        foreach (['pi_ok' => 'handlePaymentIntentSucceeded', 'pi_bad' => 'handlePaymentIntentFailed'] as $intentId => $handler) {
            $order = Order::factory()->create(['company_name' => 'Acme Fab']);
            $order->payments()->create([
                'method' => PaymentMethod::Card, 'status' => PaymentStatus::Pending, 'amount' => $order->grand_total,
                'currency' => 'USD', 'gateway' => 'stripe', 'gateway_payment_intent_id' => $intentId,
            ]);

            app(StripePaymentSyncService::class)->{$handler}(PaymentIntent::constructFrom([
                'id' => $intentId, 'object' => 'payment_intent', 'metadata' => ['order_id' => (string) $order->id],
                'last_payment_error' => ['code' => 'card_declined', 'message' => 'Your card was declined.'],
            ]));
        }

        Notification::assertSentTo($this->admin, PaymentAlert::class, fn (PaymentAlert $a, array $channels) => $a->succeeded && $channels === ['database']);
        Notification::assertSentTo($this->admin, PaymentAlert::class, fn (PaymentAlert $a) => ! $a->succeeded
            && str_ends_with($a->body($this->admin), 'Your card was declined.'));
    });
});

it('links admin emails to the alert settings page', function () {
    $order = willCallOrder();
    $html = (string) (new NewOrderAlert($order))->toMail($this->admin)->render();

    expect($html)->toContain('admin/my-notifications')
        ->and($html)->toContain('Open order');
});
