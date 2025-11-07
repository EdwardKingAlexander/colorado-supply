<?php

use App\Models\User;

test('guest users see public store page', function () {
    $response = $this->get('/store');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Store/Public')
    );
});

test('authenticated users see personalized store page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/store');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Store/Authenticated')
        ->has('products')
        ->where('products', fn ($products) => count($products) > 0)
    );
});

test('authenticated users receive product data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/store');

    $response->assertInertia(fn ($page) => $page
        ->has('products.0', fn ($product) => $product
            ->has('id')
            ->has('name')
            ->has('description')
            ->has('price')
            ->has('image')
        )
    );
});
