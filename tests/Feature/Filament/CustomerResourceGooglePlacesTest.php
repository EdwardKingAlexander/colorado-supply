<?php

use App\Filament\Resources\CRM\CustomerResource;
use App\Filament\Resources\CRM\CustomerResource\Pages\CreateCustomer;
use App\Filament\Resources\CRM\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\CRM\CustomerResource\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up Filament panel
    Filament::setCurrentPanel('admin');

    // Create a user with permissions
    $this->actingAs($this->user = User::factory()->create());

    // Create super_admin role
    $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->user->assignRole($role);
});

test('customer resource create page loads successfully', function () {
    Livewire::test(CreateCustomer::class)
        ->assertSuccessful();
});

test('customer resource edit page loads successfully', function () {
    $customer = Customer::factory()->create();

    Livewire::test(EditCustomer::class, [
        'record' => $customer->id,
    ])
        ->assertSuccessful();
});

test('customer resource list page displays customers', function () {
    $customers = Customer::factory()->count(3)->create();

    Livewire::test(ListCustomers::class)
        ->assertCanSeeTableRecords($customers);
});

test('can create customer with basic information', function () {
    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '555-1234',
            'company' => 'Acme Inc',
            'website' => 'https://acme.com',
        ])
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('customers', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '555-1234',
        'company' => 'Acme Inc',
        'website' => 'https://acme.com',
    ]);
});

test('can create customer with billing address from Google Places', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
            'status' => 'OK',
            'predictions' => [
                [
                    'description' => '1600 Amphitheatre Parkway, Mountain View, CA',
                    'place_id' => 'ChIJ2eUgeAK6j4ARbn5u_wAGqWA',
                ],
            ],
        ], 200),
        'maps.googleapis.com/maps/api/place/details/*' => Http::response([
            'status' => 'OK',
            'result' => [
                'place_id' => 'ChIJ2eUgeAK6j4ARbn5u_wAGqWA',
                'formatted_address' => '1600 Amphitheatre Pkwy, Mountain View, CA 94043, USA',
                'address_components' => [
                    ['long_name' => '1600', 'short_name' => '1600', 'types' => ['street_number']],
                    ['long_name' => 'Amphitheatre Parkway', 'short_name' => 'Amphitheatre Pkwy', 'types' => ['route']],
                    ['long_name' => 'Mountain View', 'short_name' => 'Mountain View', 'types' => ['locality']],
                    ['long_name' => 'California', 'short_name' => 'CA', 'types' => ['administrative_area_level_1']],
                    ['long_name' => '94043', 'short_name' => '94043', 'types' => ['postal_code']],
                    ['long_name' => 'United States', 'short_name' => 'US', 'types' => ['country']],
                ],
                'geometry' => [
                    'location' => [
                        'lat' => 37.4224764,
                        'lng' => -122.0842499,
                    ],
                ],
            ],
        ], 200),
    ]);

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ])
        ->set('data.billing_address_lookup', '1600 Amphitheatre')
        ->assertSet('data.billing_address.street', '1600 Amphitheatre Parkway')
        ->assertSet('data.billing_address.city', 'Mountain View')
        ->assertSet('data.billing_address.state', 'CA')
        ->assertSet('data.billing_address.zip', '94043')
        ->assertSet('data.billing_address.country', 'US')
        ->assertSet('data.billing_address.place_id', 'ChIJ2eUgeAK6j4ARbn5u_wAGqWA')
        ->assertSet('data.billing_address.latitude', 37.4224764)
        ->assertSet('data.billing_address.longitude', -122.0842499)
        ->call('create')
        ->assertHasNoErrors();

    $customer = Customer::where('email', 'john@example.com')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->billing_address['street'])->toBe('1600 Amphitheatre Parkway')
        ->and($customer->billing_address['city'])->toBe('Mountain View')
        ->and($customer->billing_address['state'])->toBe('CA')
        ->and($customer->billing_address['zip'])->toBe('94043')
        ->and($customer->billing_address['country'])->toBe('US')
        ->and($customer->billing_address['latitude'])->toBe(37.4224764)
        ->and($customer->billing_address['longitude'])->toBe(-122.0842499);
});

test('can create customer with shipping address from Google Places', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
            'status' => 'OK',
            'predictions' => [
                [
                    'description' => '1 Infinite Loop, Cupertino, CA',
                    'place_id' => 'ChIJHTRqF7e1j4AR0rKZvEU6iLQ',
                ],
            ],
        ], 200),
        'maps.googleapis.com/maps/api/place/details/*' => Http::response([
            'status' => 'OK',
            'result' => [
                'place_id' => 'ChIJHTRqF7e1j4AR0rKZvEU6iLQ',
                'formatted_address' => '1 Infinite Loop, Cupertino, CA 95014, USA',
                'address_components' => [
                    ['long_name' => '1', 'short_name' => '1', 'types' => ['street_number']],
                    ['long_name' => 'Infinite Loop', 'short_name' => 'Infinite Loop', 'types' => ['route']],
                    ['long_name' => 'Cupertino', 'short_name' => 'Cupertino', 'types' => ['locality']],
                    ['long_name' => 'California', 'short_name' => 'CA', 'types' => ['administrative_area_level_1']],
                    ['long_name' => '95014', 'short_name' => '95014', 'types' => ['postal_code']],
                    ['long_name' => 'United States', 'short_name' => 'US', 'types' => ['country']],
                ],
                'geometry' => [
                    'location' => [
                        'lat' => 37.3318,
                        'lng' => -122.0312,
                    ],
                ],
            ],
        ], 200),
    ]);

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ])
        ->set('data.shipping_address_lookup', '1 Infinite Loop')
        ->assertSet('data.shipping_address.street', '1 Infinite Loop')
        ->assertSet('data.shipping_address.city', 'Cupertino')
        ->assertSet('data.shipping_address.state', 'CA')
        ->assertSet('data.shipping_address.zip', '95014')
        ->assertSet('data.shipping_address.country', 'US')
        ->call('create')
        ->assertHasNoErrors();

    $customer = Customer::where('email', 'jane@example.com')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->shipping_address['street'])->toBe('1 Infinite Loop')
        ->and($customer->shipping_address['city'])->toBe('Cupertino')
        ->and($customer->shipping_address['state'])->toBe('CA')
        ->and($customer->shipping_address['zip'])->toBe('95014');
});

test('can create customer with both billing and shipping addresses', function () {
    Http::fake();

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'billing_address' => [
                'street' => '123 Main St',
                'city' => 'Denver',
                'state' => 'CO',
                'zip' => '80202',
                'country' => 'US',
            ],
            'shipping_address' => [
                'street' => '456 Oak Ave',
                'city' => 'Boulder',
                'state' => 'CO',
                'zip' => '80301',
                'country' => 'US',
            ],
        ])
        ->call('create')
        ->assertHasNoErrors();

    $customer = Customer::where('email', 'test@example.com')->first();

    expect($customer)->not->toBeNull()
        ->and($customer->billing_address['street'])->toBe('123 Main St')
        ->and($customer->billing_address['city'])->toBe('Denver')
        ->and($customer->shipping_address['street'])->toBe('456 Oak Ave')
        ->and($customer->shipping_address['city'])->toBe('Boulder');
});

test('address lookup does not trigger for input less than 4 characters', function () {
    Http::fake();

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'Test Customer',
        ])
        ->set('data.billing_address_lookup', 'abc')
        ->assertSet('data.billing_address.street', null);

    // HTTP should not have been called
    Http::assertNothingSent();
});

test('address lookup handles API errors gracefully', function () {
    Http::fake([
        'maps.googleapis.com/*' => Http::response([], 500),
    ]);

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'Test Customer',
        ])
        ->set('data.billing_address_lookup', 'test address')
        ->assertHasNoErrors();
});

test('can update customer with new billing address', function () {
    $customer = Customer::factory()->create([
        'billing_address' => [
            'street' => 'Old Street',
            'city' => 'Old City',
            'state' => 'CO',
            'zip' => '80000',
        ],
    ]);

    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
            'status' => 'OK',
            'predictions' => [
                [
                    'description' => 'New Address',
                    'place_id' => 'new-place-id',
                ],
            ],
        ], 200),
        'maps.googleapis.com/maps/api/place/details/*' => Http::response([
            'status' => 'OK',
            'result' => [
                'place_id' => 'new-place-id',
                'formatted_address' => '789 New St, Denver, CO 80202, USA',
                'address_components' => [
                    ['long_name' => '789', 'short_name' => '789', 'types' => ['street_number']],
                    ['long_name' => 'New Street', 'short_name' => 'New St', 'types' => ['route']],
                    ['long_name' => 'Denver', 'short_name' => 'Denver', 'types' => ['locality']],
                    ['long_name' => 'Colorado', 'short_name' => 'CO', 'types' => ['administrative_area_level_1']],
                    ['long_name' => '80202', 'short_name' => '80202', 'types' => ['postal_code']],
                    ['long_name' => 'United States', 'short_name' => 'US', 'types' => ['country']],
                ],
                'geometry' => [
                    'location' => [
                        'lat' => 39.7392,
                        'lng' => -104.9903,
                    ],
                ],
            ],
        ], 200),
    ]);

    Livewire::test(EditCustomer::class, [
        'record' => $customer->id,
    ])
        ->set('data.billing_address_lookup', 'new address')
        ->assertSet('data.billing_address.street', '789 New Street')
        ->assertSet('data.billing_address.city', 'Denver')
        ->assertSet('data.billing_address.zip', '80202')
        ->call('save')
        ->assertHasNoErrors();

    $customer->refresh();

    expect($customer->billing_address['street'])->toBe('789 New Street')
        ->and($customer->billing_address['city'])->toBe('Denver')
        ->and($customer->billing_address['zip'])->toBe('80202');
});

test('address fields can be manually edited after autocomplete', function () {
    Http::fake([
        'maps.googleapis.com/maps/api/place/autocomplete/*' => Http::response([
            'status' => 'OK',
            'predictions' => [
                [
                    'description' => 'Test Address',
                    'place_id' => 'test-place-id',
                ],
            ],
        ], 200),
        'maps.googleapis.com/maps/api/place/details/*' => Http::response([
            'status' => 'OK',
            'result' => [
                'place_id' => 'test-place-id',
                'formatted_address' => '123 Main St, Denver, CO 80202, USA',
                'address_components' => [
                    ['long_name' => '123', 'short_name' => '123', 'types' => ['street_number']],
                    ['long_name' => 'Main Street', 'short_name' => 'Main St', 'types' => ['route']],
                    ['long_name' => 'Denver', 'short_name' => 'Denver', 'types' => ['locality']],
                    ['long_name' => 'Colorado', 'short_name' => 'CO', 'types' => ['administrative_area_level_1']],
                    ['long_name' => '80202', 'short_name' => '80202', 'types' => ['postal_code']],
                    ['long_name' => 'United States', 'short_name' => 'US', 'types' => ['country']],
                ],
                'geometry' => [
                    'location' => [
                        'lat' => 39.7392,
                        'lng' => -104.9903,
                    ],
                ],
            ],
        ], 200),
    ]);

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ])
        ->set('data.billing_address_lookup', 'test address')
        ->assertSet('data.billing_address.street', '123 Main Street')
        ->assertSet('data.billing_address.city', 'Denver')
        ->set('data.billing_address.line2', 'Suite 500')
        ->set('data.billing_address.street', '123 Main St Edited')
        ->call('create')
        ->assertHasNoErrors();

    $customer = Customer::where('email', 'test@example.com')->first();

    expect($customer->billing_address['street'])->toBe('123 Main St Edited')
        ->and($customer->billing_address['line2'])->toBe('Suite 500')
        ->and($customer->billing_address['city'])->toBe('Denver');
});
