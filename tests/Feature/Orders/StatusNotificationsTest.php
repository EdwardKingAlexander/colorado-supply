<?php

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use App\Notifications\OrderPaymentReceived;
use App\Notifications\OrderStatusUpdated;
use App\Support\OrderNotifier;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

test('an account holder receives mail and database channels for an order transition', function () {
    Notification::fake();

    $user = User::factory()->create();
    $order = Order::factory()->create(['portal_user_id' => $user->id]);

    $order->update(['status' => OrderStatus::Cancelled]);

    Notification::assertSentTo(
        $user,
        OrderStatusUpdated::class,
        fn (OrderStatusUpdated $notification, array $channels) => $notification->transition === 'cancelled'
            && $channels === ['mail', 'database'],
    );
});

test('a guest order receives email only for an order transition', function () {
    Notification::fake();

    $order = Order::factory()->create();
    $order->update(['payment_status' => PaymentStatus::Refunded]);

    Notification::assertSentOnDemand(
        OrderStatusUpdated::class,
        fn (OrderStatusUpdated $notification, array $channels, AnonymousNotifiable $notifiable) => $notification->transition === 'refunded'
            && $channels === ['mail']
            && $notifiable->routes['mail'] === $order->customer_email,
    );
});

test('a multi-field order update sends only its most significant transition', function () {
    Notification::fake();

    $user = User::factory()->create();
    $order = Order::factory()->create(['portal_user_id' => $user->id]);

    $order->update([
        'status' => OrderStatus::Cancelled,
        'payment_status' => PaymentStatus::Refunded,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
    ]);

    Notification::assertSentToTimes($user, OrderStatusUpdated::class, 1);
    Notification::assertSentTo(
        $user,
        OrderStatusUpdated::class,
        fn (OrderStatusUpdated $notification) => $notification->transition === 'cancelled',
    );
});

test('shipment lifecycle changes send shipped and delivered notifications without announcing pending shipments', function () {
    Notification::fake();

    $user = User::factory()->create();
    $order = Order::factory()->create(['portal_user_id' => $user->id]);
    $shipment = Shipment::create([
        'order_id' => $order->id,
        'status' => 'pending',
        'carrier' => 'UPS',
        'tracking_number' => '1Z999',
    ]);

    Notification::assertNothingSent();

    $shipment->update(['status' => 'shipped', 'shipped_at' => now()]);
    $shipment->update(['status' => 'delivered', 'delivered_at' => now()]);

    Notification::assertSentToTimes($user, OrderStatusUpdated::class, 2);
    Notification::assertSentTo(
        $user,
        OrderStatusUpdated::class,
        fn (OrderStatusUpdated $notification) => $notification->transition === 'shipped'
            && $notification->context['tracking_number'] === '1Z999',
    );
    Notification::assertSentTo(
        $user,
        OrderStatusUpdated::class,
        fn (OrderStatusUpdated $notification) => $notification->transition === 'delivered',
    );
});

test('a shipment created in shipped state notifies exactly once', function () {
    Notification::fake();

    $user = User::factory()->create();
    $order = Order::factory()->create(['portal_user_id' => $user->id]);

    Shipment::create([
        'order_id' => $order->id,
        'status' => 'shipped',
        'shipped_at' => now(),
    ]);

    Notification::assertSentToTimes($user, OrderStatusUpdated::class, 1);
});

test('payment notifications use both channels for account orders', function () {
    Notification::fake();

    $user = User::factory()->create();
    $order = Order::factory()->create(['portal_user_id' => $user->id]);

    OrderNotifier::send($order, new OrderPaymentReceived($order));

    Notification::assertSentTo(
        $user,
        OrderPaymentReceived::class,
        fn (OrderPaymentReceived $notification, array $channels) => $channels === ['mail', 'database'],
    );
});

test('an unverified user can list their latest notifications', function () {
    $user = User::factory()->unverified()->create();
    $order = Order::factory()->create(['portal_user_id' => $user->id]);

    $user->notifyNow(new OrderStatusUpdated($order, 'shipped'), ['database']);

    $this->actingAs($user)
        ->getJson(route('notifications.index'))
        ->assertOk()
        ->assertJsonPath('unread_count', 1)
        ->assertJsonCount(1, 'notifications')
        ->assertJsonPath('notifications.0.order_number', $order->order_number)
        ->assertJsonPath('notifications.0.read_at', null)
        ->assertJsonStructure([
            'notifications' => [[
                'id',
                'order_number',
                'label',
                'tracker_url',
                'read_at',
                'created_at',
                'created_human',
            ]],
            'unread_count',
        ]);
});

test('an unverified user can mark all notifications as read', function () {
    $user = User::factory()->unverified()->create();
    $order = Order::factory()->create(['portal_user_id' => $user->id]);

    $user->notifyNow(new OrderStatusUpdated($order, 'fulfilled'), ['database']);

    $this->actingAs($user)
        ->postJson(route('notifications.read'))
        ->assertOk()
        ->assertJsonPath('unread_count', 0);

    expect($user->fresh()->unreadNotifications)->toHaveCount(0);
});

test('notification endpoints require customer authentication', function () {
    $this->getJson(route('notifications.index'))->assertUnauthorized();
    $this->postJson(route('notifications.read'))->assertUnauthorized();
});

test('the rendered status email includes the order number and signed tracker link', function () {
    $order = Order::factory()->create();
    $mail = (new OrderStatusUpdated($order, 'delivered'))->toMail(new AnonymousNotifiable);

    expect($mail->subject)
        ->toContain($order->order_number)
        ->and($mail->actionUrl)
        ->toContain('/orders/'.$order->id.'/track')
        ->toContain('signature=');
});
