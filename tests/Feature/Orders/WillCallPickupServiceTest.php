<?php

use App\Enums\FulfillmentMethod;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PickupStatus;
use App\Events\OrderPickedUp;
use App\Events\OrderReadyForPickup;
use App\Models\Location;
use App\Models\LocationProduct;
use App\Models\Order;
use App\Models\Product;
use App\Services\Orders\WillCallPickupService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Notification::fake();
    $this->service = app(WillCallPickupService::class);
    $this->location = Location::factory()->pickup()->create();
    $this->order = Order::factory()->create([
        'fulfillment_method' => FulfillmentMethod::WillCall,
        'pickup_location_id' => $this->location->id,
        'pickup_status' => PickupStatus::AwaitingPick,
    ]);
});

it('marks an order ready with a unique, unambiguous pickup code', function () {
    Event::fake([OrderReadyForPickup::class]);

    $this->service->markReady($this->order);

    expect($this->order->fresh()->pickup_status)->toBe(PickupStatus::ReadyForPickup)
        ->and($this->order->pickup_code)->toMatch('/^WC-[ACDEFGHJKMNPQRTUVWXY34679]{4}$/')
        ->and($this->order->ready_for_pickup_at)->not->toBeNull();

    Event::assertDispatched(OrderReadyForPickup::class);
});

it('marks a ready order picked up when the code matches, ignoring case and dashes', function () {
    Event::fake([OrderPickedUp::class]);
    $this->service->markReady($this->order);
    $code = strtolower(str_replace('-', ' ', $this->order->pickup_code));

    $this->service->markPickedUp($this->order, '  Sam Driver ', $code);

    $order = $this->order->fresh();
    expect($order->pickup_status)->toBe(PickupStatus::PickedUp)
        ->and($order->picked_up_by)->toBe('Sam Driver')
        ->and($order->picked_up_at)->not->toBeNull()
        ->and($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->fulfilled_at)->not->toBeNull();

    Event::assertDispatched(OrderPickedUp::class);
});

it('refuses a wrong code unless the admin overrides it', function () {
    $this->service->markReady($this->order);

    expect(fn () => $this->service->markPickedUp($this->order, 'Sam', 'WC-ZZZZ'))
        ->toThrow(ValidationException::class, 'pickup code does not match');

    $this->service->markPickedUp($this->order, 'Sam', null, overrideCode: true);

    expect($this->order->fresh()->pickup_status)->toBe(PickupStatus::PickedUp);
});

it('requires the name of the person collecting', function () {
    $this->service->markReady($this->order);

    expect(fn () => $this->service->markPickedUp($this->order, ' ', $this->order->pickup_code))
        ->toThrow(ValidationException::class, 'who collected');
});

it('enforces the transition order', function () {
    expect(fn () => $this->service->markPickedUp($this->order, 'Sam', null, true))
        ->toThrow(ValidationException::class, 'not ready for pickup');

    $this->service->markReady($this->order);

    expect(fn () => $this->service->markReady($this->order))
        ->toThrow(ValidationException::class, 'not waiting to be picked');
});

it('refuses pickup transitions on shipped and cancelled orders', function () {
    $shipped = Order::factory()->create();
    $cancelled = Order::factory()->create([
        'status' => OrderStatus::Cancelled,
        'fulfillment_method' => FulfillmentMethod::WillCall,
        'pickup_status' => PickupStatus::AwaitingPick,
    ]);

    expect($this->service->canMarkReady($shipped))->toBeFalse()
        ->and($this->service->canMarkReady($cancelled))->toBeFalse()
        ->and(fn () => $this->service->markReady($cancelled))->toThrow(ValidationException::class);
});

it('moves a ready order back and keeps its code', function () {
    $this->service->markReady($this->order);
    $code = $this->order->pickup_code;

    $this->service->revertToAwaitingPick($this->order);

    $order = $this->order->fresh();
    expect($order->pickup_status)->toBe(PickupStatus::AwaitingPick)
        ->and($order->ready_for_pickup_at)->toBeNull()
        ->and($order->pickup_code)->toBe($code);

    $this->service->markReady($order);
    expect($order->fresh()->pickup_code)->toBe($code);
});

it('lists lines the pickup counter cannot cover', function () {
    $covered = Product::factory()->create(['stock' => 0]);
    $short = Product::factory()->create(['stock' => 100]);
    $noRecord = Product::factory()->create(['stock' => 1]);
    LocationProduct::create(['location_id' => $this->location->id, 'product_id' => $covered->id, 'on_hand' => 5, 'visible' => true]);
    LocationProduct::create(['location_id' => $this->location->id, 'product_id' => $short->id, 'on_hand' => 2, 'visible' => true]);

    foreach ([[$covered, 5], [$short, 3], [$noRecord, 4]] as [$product, $quantity]) {
        $this->order->items()->create([
            'product_id' => $product->id, 'sku' => $product->sku, 'name' => $product->name,
            'quantity' => $quantity, 'unit_price' => 1, 'line_discount' => 0, 'line_total' => $quantity,
        ]);
    }

    expect($this->service->shortLines($this->order))->toBe([
        ['name' => $short->name, 'ordered' => 3, 'on_hand' => 2],
        ['name' => $noRecord->name, 'ordered' => 4, 'on_hand' => 1],
    ]);
});
