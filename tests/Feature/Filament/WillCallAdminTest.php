<?php

use App\Enums\FulfillmentMethod;
use App\Enums\PickupStatus;
use App\Filament\Resources\CRM\OrderResource\Pages\ListOrders;
use App\Filament\Resources\CRM\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\LocationResource\Pages\EditLocation;
use App\Models\Admin;
use App\Models\Location;
use App\Models\Order;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Notification::fake();
    Filament::setCurrentPanel('admin');

    // Orders are created before logging in: Order::creating stamps created_by
    // with auth()->id(), which references users, not admins.
    $this->location = Location::factory()->pickup()->create(['name' => 'Front Range Will Call']);
    $this->willCall = Order::factory()->create([
        'fulfillment_method' => FulfillmentMethod::WillCall,
        'pickup_location_id' => $this->location->id,
        'pickup_status' => PickupStatus::AwaitingPick,
    ]);
    $this->shipped = Order::factory()->create();
    $this->collected = Order::factory()->create([
        'fulfillment_method' => FulfillmentMethod::WillCall,
        'pickup_status' => PickupStatus::PickedUp,
    ]);

    $admin = Admin::factory()->create();
    $admin->assignRole(Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']));
    $this->actingAs($admin, 'admin');
});

it('saves will call details on a location and clears them', function () {
    $location = Location::factory()->create();

    Livewire::test(EditLocation::class, ['record' => $location->getRouteKey()])
        ->fillForm([
            'pickup_enabled' => true,
            'address' => ['line1' => '9 Dock St', 'city' => 'Golden', 'state' => 'CO', 'postal_code' => '80401'],
            'hours' => 'Mon–Fri 7–4',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $location->refresh();
    expect($location->pickup_enabled)->toBeTrue()
        ->and($location->formattedAddress())->toBe('9 Dock St, Golden, CO 80401')
        ->and($location->hours)->toBe('Mon–Fri 7–4');

    Livewire::test(EditLocation::class, ['record' => $location->getRouteKey()])
        ->fillForm(['pickup_enabled' => false, 'address' => ['line1' => null, 'city' => null, 'state' => null, 'postal_code' => null], 'hours' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($location->refresh()->pickup_enabled)->toBeFalse()
        ->and($location->formattedAddress())->toBeNull();
});

it('lists open will call orders in the queue tab', function () {
    Livewire::test(ListOrders::class)
        ->set('activeTab', 'will_call')
        ->assertCanSeeTableRecords([$this->willCall])
        ->assertCanNotSeeTableRecords([$this->shipped, $this->collected]);
});

it('shows pickup actions only where the transition is valid', function () {
    Livewire::test(ListOrders::class)
        ->assertTableActionVisible('markReadyForPickup', $this->willCall)
        ->assertTableActionHidden('markPickedUp', $this->willCall)
        ->assertTableActionHidden('markReadyForPickup', $this->shipped)
        ->assertTableActionHidden('markPickedUp', $this->shipped);
});

it('runs the pickup workflow from the order page', function () {
    Livewire::test(ViewOrder::class, ['record' => $this->willCall->getRouteKey()])
        ->callAction('markReadyForPickup')
        ->assertNotified();

    $code = $this->willCall->refresh()->pickup_code;
    // Admin ids are not user ids: the updated_by (users FK) stamp is skipped.
    expect($this->willCall->pickup_status)->toBe(PickupStatus::ReadyForPickup)
        ->and($this->willCall->updated_by)->toBeNull();

    Livewire::test(ViewOrder::class, ['record' => $this->willCall->getRouteKey()])
        ->callAction('markPickedUp', data: ['picked_up_by' => 'Sam Driver', 'pickup_code' => $code])
        ->assertHasNoFormErrors();

    expect($this->willCall->refresh()->pickup_status)->toBe(PickupStatus::PickedUp)
        ->and($this->willCall->picked_up_by)->toBe('Sam Driver');
});

it('keeps the order ready when the code is wrong', function () {
    $this->willCall->update(['pickup_status' => PickupStatus::ReadyForPickup, 'pickup_code' => 'WC-AAAA']);

    Livewire::test(ViewOrder::class, ['record' => $this->willCall->getRouteKey()])
        ->callAction('markPickedUp', data: ['picked_up_by' => 'Sam', 'pickup_code' => 'WC-CCCC'])
        ->assertNotified('Not updated');

    expect($this->willCall->refresh()->pickup_status)->toBe(PickupStatus::ReadyForPickup);
});

it('requires the code unless the override is on', function () {
    $this->willCall->update(['pickup_status' => PickupStatus::ReadyForPickup, 'pickup_code' => 'WC-AAAA']);

    Livewire::test(ViewOrder::class, ['record' => $this->willCall->getRouteKey()])
        ->callAction('markPickedUp', data: ['picked_up_by' => 'Sam', 'pickup_code' => null])
        ->assertHasActionErrors(['pickup_code' => 'required']);

    Livewire::test(ViewOrder::class, ['record' => $this->willCall->getRouteKey()])
        ->callAction('markPickedUp', data: ['picked_up_by' => 'Sam', 'pickup_code' => null, 'override_code' => true])
        ->assertHasNoActionErrors();

    expect($this->willCall->refresh()->pickup_status)->toBe(PickupStatus::PickedUp);
});

it('shows pickup details on the order page only for will call orders', function () {
    Livewire::test(ViewOrder::class, ['record' => $this->willCall->getRouteKey()])
        ->assertSee('Will call pickup')
        ->assertSee('Front Range Will Call');

    Livewire::test(ViewOrder::class, ['record' => $this->shipped->getRouteKey()])
        ->assertDontSee('Will call pickup');
});
