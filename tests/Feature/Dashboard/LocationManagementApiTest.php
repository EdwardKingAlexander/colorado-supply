<?php

use App\Models\Company;
use App\Models\CompanyEmailDomain;
use App\Models\Location;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Organizations\CompanyDomainService;

function locationApiUser(string $domain = 'example.com'): array
{
    $company = Company::factory()->create();
    CompanyEmailDomain::factory()->for($company)->create(['domain' => $domain]);
    $user = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'member@'.$domain,
    ]);
    app(CompanyDomainService::class)->activate($company);

    return [$company->fresh(), $user->fresh()];
}

it('requires authentication and a domain-ready company', function () {
    $this->getJson(route('dashboard.locations.index'))->assertUnauthorized();

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user)
        ->getJson(route('dashboard.locations.index'))
        ->assertForbidden();
});

it('creates roots and sublocations using the authenticated company', function () {
    [$company, $user] = locationApiUser();

    $rootId = $this->actingAs($user)
        ->postJson(route('dashboard.locations.store'), [
            'name' => 'Denver Warehouse',
            'company_id' => Company::factory()->create()->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'denver-warehouse')
        ->json('data.id');

    $childId = $this->postJson(route('dashboard.locations.store'), [
        'name' => 'Tool Crib',
        'parent_id' => $rootId,
    ])
        ->assertCreated()
        ->assertJsonPath('data.parent_id', $rootId)
        ->json('data.id');

    expect(Location::findOrFail($rootId)->company_id)->toBe($company->id)
        ->and(Location::findOrFail($childId)->company_id)->toBe($company->id);
});

it('rejects foreign parents and foreign location mutations', function () {
    [$companyA, $userA] = locationApiUser('alpha.test');
    [$companyB] = locationApiUser('bravo.test');
    $foreignParent = Location::create([
        'company_id' => $companyB->id,
        'name' => 'Foreign Parent',
        'slug' => 'foreign-parent',
    ]);

    $this->actingAs($userA)
        ->postJson(route('dashboard.locations.store'), [
            'name' => 'Injected Child',
            'parent_id' => $foreignParent->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('parent_id');

    $this->patchJson(route('dashboard.locations.update', $foreignParent), [
        'name' => 'Hacked',
        'parent_id' => null,
    ])->assertForbidden();

    expect($foreignParent->fresh()->name)->toBe('Foreign Parent')
        ->and(Location::where('company_id', $companyA->id)->where('name', 'Injected Child')->exists())->toBeFalse();
});

it('rejects arbitrary-depth hierarchy cycles', function () {
    [$company, $user] = locationApiUser();
    $root = Location::create(['company_id' => $company->id, 'name' => 'Root', 'slug' => 'root']);
    $child = Location::create(['company_id' => $company->id, 'parent_id' => $root->id, 'name' => 'Child', 'slug' => 'child']);
    $grandchild = Location::create(['company_id' => $company->id, 'parent_id' => $child->id, 'name' => 'Grandchild', 'slug' => 'grandchild']);

    $this->actingAs($user)
        ->patchJson(route('dashboard.locations.update', $root), [
            'name' => 'Root',
            'parent_id' => $grandchild->id,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('parent_id');

    expect($root->fresh()->parent_id)->toBeNull();
});

it('requires an explicit child strategy and can promote children while archiving', function () {
    [$company, $user] = locationApiUser();
    $root = Location::create(['company_id' => $company->id, 'name' => 'Root', 'slug' => 'root']);
    $child = Location::create(['company_id' => $company->id, 'parent_id' => $root->id, 'name' => 'Child', 'slug' => 'child']);

    $this->actingAs($user)
        ->deleteJson(route('dashboard.locations.destroy', $root))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('child_strategy');

    $this->deleteJson(route('dashboard.locations.destroy', $root), [
        'child_strategy' => 'promote',
    ])->assertOk()->assertJson(['archived' => true]);

    expect(Location::query()->find($root->id))->toBeNull()
        ->and(Location::withTrashed()->findOrFail($root->id)->trashed())->toBeTrue()
        ->and($child->fresh()->parent_id)->toBeNull();
});

it('archives a subtree and requires parent-first restoration', function () {
    [$company, $user] = locationApiUser();
    $root = Location::create(['company_id' => $company->id, 'name' => 'Root', 'slug' => 'root']);
    $child = Location::create(['company_id' => $company->id, 'parent_id' => $root->id, 'name' => 'Child', 'slug' => 'child']);

    $this->actingAs($user)
        ->deleteJson(route('dashboard.locations.destroy', $root), [
            'child_strategy' => 'archive_subtree',
        ])->assertOk();

    expect(Location::withTrashed()->findOrFail($root->id)->trashed())->toBeTrue()
        ->and(Location::withTrashed()->findOrFail($child->id)->trashed())->toBeTrue();

    $this->postJson(route('dashboard.locations.restore', $child->id))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('parent_id');

    $this->postJson(route('dashboard.locations.restore', $root->id))->assertOk();
    $this->postJson(route('dashboard.locations.restore', $child->id))->assertOk();

    expect($root->fresh()->trashed())->toBeFalse()
        ->and($child->fresh()->trashed())->toBeFalse();
});

it('preserves historical item locations and rejects archived locations in new checkout', function () {
    [$company, $user] = locationApiUser();
    $location = Location::create(['company_id' => $company->id, 'name' => 'History', 'slug' => 'history']);
    $product = Product::factory()->create();
    $order = Order::factory()->create(['company_id' => $company->id, 'portal_user_id' => $user->id]);
    $item = $order->items()->create([
        'product_id' => $product->id,
        'location_id' => $location->id,
        'name' => $product->name,
        'quantity' => 1,
        'unit_price' => 10,
        'line_discount' => 0,
    ]);

    $this->actingAs($user)
        ->deleteJson(route('dashboard.locations.destroy', $location))
        ->assertOk();

    expect($item->fresh()->location?->name)->toBe('History');

    $this->postJson('/api/v1/store/checkout', [
        'items' => [[
            'id' => $product->id,
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => 1,
            'price' => 10,
            'location_id' => $location->id,
        ]],
    ])->assertUnprocessable()->assertJsonValidationErrors('items.0.location_id');
});

it('lists active and archived records only for the authenticated company', function () {
    [$company, $user] = locationApiUser('alpha.test');
    [$otherCompany] = locationApiUser('bravo.test');
    $active = Location::create(['company_id' => $company->id, 'name' => 'Active', 'slug' => 'active']);
    $archived = Location::create(['company_id' => $company->id, 'name' => 'Archived', 'slug' => 'archived']);
    $archived->delete();
    Location::create(['company_id' => $otherCompany->id, 'name' => 'Foreign', 'slug' => 'foreign']);

    $ids = collect($this->actingAs($user)
        ->getJson(route('dashboard.locations.index'))
        ->assertOk()
        ->json('data'))
        ->pluck('id');

    expect($ids)->toContain($active->id, $archived->id)
        ->not->toContain(Location::withTrashed()->where('company_id', $otherCompany->id)->value('id'));
});
