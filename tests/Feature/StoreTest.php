<?php

use App\Models\Admin;
use App\Models\User;

test('guest users are redirected to login', function () {
    $response = $this->get('/store');

    $response->assertRedirect(route('login'));
});

test('authenticated users can access store', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/store');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Store/StoreIndex')
    );
});

test('authenticated admins can access store', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->get('/store');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Store/StoreIndex')
    );
});

test('store quote page is accessible to authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/store/quote');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Store/QuoteView')
    );
});

test('store quote page is accessible to authenticated admins', function () {
    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')->get('/store/quote');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Store/QuoteView')
    );
});
