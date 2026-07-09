<?php

use App\Services\Stripe\StripeClientFactory;
use Stripe\StripeClient;

test('it resolves a configured stripe client', function () {
    $client = app(StripeClient::class);

    expect($client)->toBeInstanceOf(StripeClient::class);
});

test('the factory builds a stripe client from config', function () {
    $factory = new StripeClientFactory;

    expect($factory->make())->toBeInstanceOf(StripeClient::class);
});

test('the factory throws when the stripe secret is missing', function () {
    config(['services.stripe.secret' => null]);

    $factory = new StripeClientFactory;

    expect(fn () => $factory->make())->toThrow(RuntimeException::class);
});
