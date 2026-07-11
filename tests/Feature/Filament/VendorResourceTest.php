<?php

use App\Filament\Resources\VendorResource\Pages\CreateVendor;
use App\Filament\Resources\VendorResource\Pages\EditVendor;
use App\Filament\Resources\VendorResource\Pages\ListVendors;
use App\Models\Admin;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorContact;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    $this->actingAs(Admin::factory()->create(), 'admin');
    $this->actingAs($this->user = User::factory()->create());

    $role = Role::query()->firstOrCreate(
        ['name' => 'super_admin', 'guard_name' => 'web'],
        ['name' => 'super_admin', 'guard_name' => 'web'],
    );

    $this->user->assignRole($role);
});

it('creates an admin-managed vendor without requiring a portal user or contact', function () {
    Livewire::test(CreateVendor::class)
        ->fillForm([
            'name' => 'Brighton-Best',
            'email' => 'denver@brightonbest.com',
            'phone' => '303-576-0530',
            'address' => '123 Supply Way, Denver, CO 80202',
            'description' => 'Fastener supplier',
            'contacts' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $vendor = Vendor::query()->where('email', 'denver@brightonbest.com')->firstOrFail();

    expect($vendor->user_id)->toBeNull()
        ->and($vendor->slug)->toBe('brighton-best')
        ->and($vendor->address)->toBe('123 Supply Way, Denver, CO 80202')
        ->and($vendor->contacts)->toHaveCount(0);
});

it('creates optional contacts with their personal information in the vendor form', function () {
    Livewire::test(CreateVendor::class)
        ->fillForm([
            'name' => 'Acme Industrial',
            'email' => 'sales@acme.test',
            'contacts' => [
                [
                    'name' => 'Jordan Lee',
                    'job_title' => 'Account Manager',
                    'email' => 'jordan@acme.test',
                    'phone' => '303-555-0100',
                    'mobile_phone' => '720-555-0101',
                    'notes' => 'Best reached in the morning.',
                    'is_preferred' => true,
                ],
                [
                    'name' => 'Morgan Chen',
                    'email' => 'morgan@acme.test',
                    'is_preferred' => false,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $vendor = Vendor::query()->where('email', 'sales@acme.test')->firstOrFail();

    expect($vendor->contacts)->toHaveCount(2)
        ->and($vendor->preferredContact?->name)->toBe('Jordan Lee')
        ->and($vendor->preferredContact?->job_title)->toBe('Account Manager')
        ->and($vendor->preferredContact?->email)->toBe('jordan@acme.test')
        ->and($vendor->preferredContact?->phone)->toBe('303-555-0100')
        ->and($vendor->preferredContact?->mobile_phone)->toBe('720-555-0101');
});

it('keeps only one preferred contact when preference changes', function () {
    $vendor = Vendor::factory()->create();
    $first = VendorContact::factory()->preferred()->for($vendor)->create();
    $second = VendorContact::factory()->for($vendor)->create();

    $second->update(['is_preferred' => true]);

    expect($first->fresh()->is_preferred)->toBeFalse()
        ->and($second->fresh()->is_preferred)->toBeTrue()
        ->and($vendor->contacts()->where('is_preferred', true)->count())->toBe(1);
});

it('supports contact update and deletion and cascades contacts with the vendor', function () {
    $vendor = Vendor::factory()->create();
    $contact = VendorContact::factory()->for($vendor)->create();

    $contact->update([
        'name' => 'Updated Contact',
        'email' => 'updated@example.com',
    ]);

    expect($contact->fresh()->name)->toBe('Updated Contact')
        ->and($contact->fresh()->email)->toBe('updated@example.com');

    $contact->delete();
    expect(VendorContact::query()->whereKey($contact->id)->exists())->toBeFalse();

    $remaining = VendorContact::factory()->for($vendor)->create();
    $vendor->delete();

    expect(VendorContact::query()->whereKey($remaining->id)->exists())->toBeFalse();
});

it('updates and removes contacts through the vendor edit form', function () {
    $vendor = Vendor::factory()->create();
    $contact = VendorContact::factory()->preferred()->for($vendor)->create([
        'name' => 'Original Contact',
        'email' => 'original@example.com',
    ]);

    Livewire::test(EditVendor::class, ['record' => $vendor->getRouteKey()])
        ->fillForm([
            'contacts' => [[
                'id' => $contact->id,
                'name' => 'Edited Contact',
                'job_title' => 'Sales Director',
                'email' => 'edited@example.com',
                'phone' => '303-555-0110',
                'mobile_phone' => null,
                'notes' => null,
                'is_preferred' => true,
            ]],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $savedContact = $vendor->contacts()->sole();

    expect($savedContact->name)->toBe('Edited Contact')
        ->and($savedContact->job_title)->toBe('Sales Director')
        ->and($savedContact->email)->toBe('edited@example.com')
        ->and($savedContact->is_preferred)->toBeTrue();

    Livewire::test(EditVendor::class, ['record' => $vendor->getRouteKey()])
        ->fillForm(['contacts' => []])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($vendor->contacts()->count())->toBe(0);
});

it('generates collision-safe slugs for vendors with the same name', function () {
    $first = Vendor::factory()->create(['name' => 'Shared Vendor', 'slug' => null]);
    $second = Vendor::factory()->create(['name' => 'Shared Vendor', 'slug' => null]);

    expect($first->slug)->toBe('shared-vendor')
        ->and($second->slug)->toBe('shared-vendor-2');
});

it('shows preferred contact columns in the vendor table', function () {
    $vendor = Vendor::factory()->create();
    VendorContact::factory()->preferred()->for($vendor)->create([
        'name' => 'Preferred Person',
        'email' => 'preferred@example.com',
        'phone' => '303-555-0199',
    ]);

    Livewire::test(ListVendors::class)
        ->assertCanSeeTableRecords([$vendor])
        ->assertTableColumnExists('preferredContact.name')
        ->assertTableColumnExists('preferredContact.email')
        ->assertTableColumnExists('preferred_contact_phone');
});
