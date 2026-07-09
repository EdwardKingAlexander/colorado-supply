<?php

namespace App\Services\Paypal;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaypalClientFactory
{
    /**
     * The base URL for the PayPal REST API, based on `services.paypal.mode`.
     */
    public function baseUrl(): string
    {
        return config('services.paypal.mode') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * Fetch (and briefly cache) an OAuth2 access token for the PayPal REST API.
     *
     * @throws RuntimeException if PayPal credentials are not configured.
     */
    public function accessToken(): string
    {
        $clientId = config('services.paypal.client_id');
        $clientSecret = config('services.paypal.client_secret');

        if (! is_string($clientId) || $clientId === '' || ! is_string($clientSecret) || $clientSecret === '') {
            throw new RuntimeException('PayPal client credentials are not configured. Set PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET in your environment.');
        }

        return Cache::remember('paypal_access_token', 270, function () use ($clientId, $clientSecret) {
            $response = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->post("{$this->baseUrl()}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials',
                ])
                ->throw();

            return $response->json('access_token');
        });
    }
}
