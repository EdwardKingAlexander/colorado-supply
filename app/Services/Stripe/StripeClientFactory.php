<?php

namespace App\Services\Stripe;

use RuntimeException;
use Stripe\StripeClient;

class StripeClientFactory
{
    /**
     * Build a configured Stripe SDK client.
     *
     * @throws RuntimeException if the Stripe secret key is not configured.
     */
    public function make(): StripeClient
    {
        $secret = config('services.stripe.secret');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('Stripe secret key is not configured. Set STRIPE_SECRET in your environment.');
        }

        return new StripeClient($secret);
    }
}
