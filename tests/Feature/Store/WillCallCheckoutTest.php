<?php

use App\Enums\FulfillmentMethod;
use App\Enums\PickupStatus;
use App\Events\WillCallOrderPlaced;
use App\Mail\OrderConfirmationMail;
use App\Models\Admin;
use App\Models\Company;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

function willCallPayload(Product $product, array $overrides = []): array
{
    return array_merge([
        'items' => [[
            'id' => $product->id, 'product_id' => $product->id, 'name' => $product->name, 'quantity' => 2, 'slug' => $product->slug,
        ]],
        'contact_name' => 'Jane Buyer',
        'contact_email' => 'jane@example.com',
        'billing_address' => [
            'line1' => '123 Main St', 'city' => 'Denver', 'state' => 'CO', 'postal_code' => '80202', 'country' => 'US',
        ],
        'shipping_same_as_billing' => true,
    ], $overrides);
}

beforeEach(function () {
    Mail::fake();
    $this->company = Company::factory()->create();
    $this->buyer = User::factory()->create(['company_id' => $this->company->id]);
    $this->product = Product::factory()->create(['price' => 10, 'is_active' => true, 'minimum_order_quantity' => 1]);
    $this->company->products()->attach($this->product->id);
    $this->counter = Location::factory()->pickup()->create(['company_id' => $this->company->id, 'name' => 'Front Range Will Call']);
});

it('places a will call order at a pickup location with no shipping address', function () {
    Event::fake([WillCallOrderPlaced::class]);

    $this->actingAs($this->buyer, 'sanctum')
        ->postJson('/api/v1/store/checkout', willCallPayload($this->product, [
            'fulfillment_method' => 'will_call',
            'pickup_location_id' => $this->counter->id,
            'shipping_same_as_billing' => false,
            'shipping_address' => ['line1' => 'ignored', 'city' => 'x', 'state' => 'CO', 'postal_code' => '1', 'country' => 'US'],
        ]))
        ->assertCreated()
        ->assertJsonPath('order.fulfillment_method', 'will_call');

    $order = Order::query()->latest('id')->firstOrFail();

    expect($order->fulfillment_method)->toBe(FulfillmentMethod::WillCall)
        ->and($order->pickup_location_id)->toBe($this->counter->id)
        ->and($order->pickup_status)->toBe(PickupStatus::AwaitingPick)
        ->and($order->pickup_code)->toBeNull()
        ->and($order->shipping_address)->toBeNull()
        ->and((float) $order->shipping_total)->toBe(0.0);

    Event::assertDispatched(WillCallOrderPlaced::class, fn ($event) => $event->order->is($order));
});

it('keeps shipping orders unchanged when no method is sent', function () {
    Event::fake([WillCallOrderPlaced::class]);

    $this->actingAs($this->buyer, 'sanctum')
        ->postJson('/api/v1/store/checkout', willCallPayload($this->product))
        ->assertCreated();

    $order = Order::query()->latest('id')->firstOrFail();

    expect($order->fulfillment_method)->toBe(FulfillmentMethod::Ship)
        ->and($order->pickup_location_id)->toBeNull()
        ->and($order->pickup_status)->toBeNull()
        ->and($order->shipping_address['line1'])->toBe('123 Main St');

    Event::assertNotDispatched(WillCallOrderPlaced::class);
});

it('ignores a pickup location on shipping orders', function () {
    $this->actingAs($this->buyer, 'sanctum')
        ->postJson('/api/v1/store/checkout', willCallPayload($this->product, [
            'fulfillment_method' => 'ship', 'pickup_location_id' => $this->counter->id,
        ]))
        ->assertCreated();

    expect(Order::query()->latest('id')->first()->pickup_location_id)->toBeNull();
});

it('rejects will call without a usable pickup location', function (Closure $location, string $message) {
    $this->actingAs($this->buyer, 'sanctum')
        ->postJson('/api/v1/store/checkout', willCallPayload($this->product, [
            'fulfillment_method' => 'will_call',
            'pickup_location_id' => $location($this),
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['pickup_location_id' => $message]);

    expect(Order::query()->count())->toBe(0);
})->with([
    'missing' => [fn () => null, 'Choose where you will pick up this order.'],
    'not a pickup counter' => [fn ($test) => Location::factory()->create(['company_id' => $test->company->id])->id, 'That pickup location is not available'],
    'another company' => [fn () => Location::factory()->pickup()->create()->id, 'That pickup location is not available'],
    'archived' => [fn ($test) => tap(Location::factory()->pickup()->create(['company_id' => $test->company->id]))->delete()->id, 'That pickup location is not available'],
]);

it('rejects an unknown fulfillment method', function () {
    $this->actingAs($this->buyer, 'sanctum')
        ->postJson('/api/v1/store/checkout', willCallPayload($this->product, ['fulfillment_method' => 'drone']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('fulfillment_method');
});

it('tells the buyer in the confirmation email where to pick up', function () {
    $this->actingAs($this->buyer, 'sanctum')
        ->postJson('/api/v1/store/checkout', willCallPayload($this->product, [
            'fulfillment_method' => 'will_call', 'pickup_location_id' => $this->counter->id,
        ]))
        ->assertCreated();

    Mail::assertSent(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) {
        $html = $mail->render();

        return str_contains($html, 'Will Call at Front Range Will Call')
            && str_contains($html, '100 Supply Way, Denver, CO 80216');
    });
});

it('offers only the buyer company pickup counters on the checkout page', function () {
    Location::factory()->create(['company_id' => $this->company->id, 'name' => 'Warehouse only']);
    Location::factory()->pickup()->create(['name' => 'Someone else']);

    $this->actingAs($this->buyer)
        ->get(route('store.checkout'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Store/Checkout')
            ->has('pickupLocations', 1)
            ->where('pickupLocations.0.name', 'Front Range Will Call')
            ->where('pickupLocations.0.address', '100 Supply Way, Denver, CO 80216')
            ->where('pickupLocations.0.hours', 'Mon–Fri 7am–5pm'));
});

it('offers admins every pickup counter', function () {
    Location::factory()->pickup()->create(['name' => 'Someone else']);

    $this->actingAs(Admin::factory()->create(), 'admin')
        ->get(route('store.checkout'))
        ->assertInertia(fn (Assert $page) => $page->has('pickupLocations', 2));
});

it('shows pickup details on the tracker and reveals the code only when ready', function () {
    $order = Order::factory()->create([
        'fulfillment_method' => FulfillmentMethod::WillCall,
        'pickup_location_id' => $this->counter->id,
        'pickup_status' => PickupStatus::AwaitingPick,
        'pickup_code' => 'WC-AAAA',
    ]);

    $this->get(URL::signedRoute('orders.track', ['order' => $order]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('order.pickup.location.name', 'Front Range Will Call')
            ->where('order.pickup.status.value', 'awaiting_pick')
            ->missing('order.pickup.code'));

    $order->update(['pickup_status' => PickupStatus::ReadyForPickup, 'ready_for_pickup_at' => now()]);

    $this->get(URL::signedRoute('orders.track', ['order' => $order]))
        ->assertInertia(fn (Assert $page) => $page->where('order.pickup.code', 'WC-AAAA'));
});

it('sends no pickup block for shipped orders', function () {
    $order = Order::factory()->create();

    $this->get(URL::signedRoute('orders.track', ['order' => $order]))
        ->assertInertia(fn (Assert $page) => $page->where('order.pickup', null));
});
