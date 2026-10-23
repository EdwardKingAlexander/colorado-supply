<?php

use App\Models\Company;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('the company page lists the user company locations', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id]);

    $this->actingAs($user)
        ->get(route('company.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Company/Index')
            ->where('company.id', $company->id)
            ->has('locations'));
});

test('the company page shows an empty state instead of failing when the user has no company', function () {
    $user = User::factory()->create(['company_id' => null]);

    $this->actingAs($user)
        ->get(route('company.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Company/Index')
            ->where('company', null)
            ->where('locations', []));
});
