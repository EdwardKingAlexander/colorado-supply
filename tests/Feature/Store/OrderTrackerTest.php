<?php

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderPaymentReceived;
use Illuminate\Support\Facades\URL;

test('a guest can view the tracker through a signed link', function () {
    $order = Order::factory()->create();

    $this->get(URL::signedRoute('orders.track', ['order' => $order]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Store/OrderTracker')
            ->where('order.order_number', $order->order_number));
});

test('the tracker works for orders belonging to unverified users', function () {
    $unverified = User::factory()->unverified()->create();
    $order = Order::factory()->create(['portal_user_id' => $unverified->id]);

    // Deliberately NOT acting as anyone — the emailed link is used logged-out.
    $this->get(URL::signedRoute('orders.track', ['order' => $order]))
        ->assertOk();
});

test('the tracker exposes only buyer-safe fields', function () {
    $order = Order::factory()->create(['notes' => 'internal margin notes']);

    $this->get(URL::signedRoute('orders.track', ['order' => $order]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing('order.notes')
            ->missing('order.billing_address')
            ->missing('order.id'));
});

test('an unsigned tracker url is rejected', function () {
    $order = Order::factory()->create();

    $this->get(route('orders.track', ['order' => $order]))
        ->assertForbidden();
});

test('a tampered signature is rejected', function () {
    $order = Order::factory()->create();

    $url = URL::signedRoute('orders.track', ['order' => $order]);

    $this->get($url.'tampered')->assertForbidden();
});

test('the order confirmation mail contains the signed tracker link', function () {
    $order = Order::factory()->create();

    $html = (new OrderConfirmationMail($order->load('items')))->render();

    expect($html)
        ->toContain('/orders/'.$order->getKey().'/track')
        ->toContain('signature=')
        ->toContain('Track Your Order')
        ->toContain($order->order_number);
});

test('the payment received notification links to the tracker instead of the gated success page', function () {
    $order = Order::factory()->create();

    $mail = (new OrderPaymentReceived($order))->toMail((object) []);

    expect($mail->actionText)->toBe('Track Your Order')
        ->and($mail->actionUrl)->toContain('/orders/'.$order->getKey().'/track')
        ->and($mail->actionUrl)->toContain('signature=');
});
