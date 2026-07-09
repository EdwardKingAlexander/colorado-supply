<?php

use App\Services\Paypal\PaypalClientFactory;
use Illuminate\Support\Facades\Http;
use Tests\Support\Paypal\FakesPaypalHttp;

uses(FakesPaypalHttp::class);

test('it returns the sandbox base url by default', function () {
    $factory = new PaypalClientFactory;

    expect($factory->baseUrl())->toBe('https://api-m.sandbox.paypal.com');
});

test('it returns the live base url when configured', function () {
    config(['services.paypal.mode' => 'live']);

    $factory = new PaypalClientFactory;

    expect($factory->baseUrl())->toBe('https://api-m.paypal.com');
});

test('it fetches and caches an access token', function () {
    $this->fakePaypal();

    $factory = new PaypalClientFactory;

    expect($factory->accessToken())->toBe('A21AAtest_access_token');

    Http::assertSentCount(1);

    // Second call should be served from cache, not a new HTTP request.
    expect($factory->accessToken())->toBe('A21AAtest_access_token');

    Http::assertSentCount(1);
});

test('it throws when paypal credentials are missing', function () {
    config(['services.paypal.client_id' => null]);

    $factory = new PaypalClientFactory;

    expect(fn () => $factory->accessToken())->toThrow(RuntimeException::class);
});
