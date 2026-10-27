# Phase 1 — Foundation & Configuration

## Goal

Establish a single, testable point of entry for Stripe configuration and the
Stripe SDK client, with no behavioral changes to checkout/orders yet.

## Files to Create/Modify

- `.env.example` — add `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
  (mirrors the existing pattern for `RESEND_KEY`, `MAILGUN_*`, etc.)
- `config/services.php` — add a `stripe` block:
  ```php
  'stripe' => [
      'key' => env('STRIPE_KEY'),
      'secret' => env('STRIPE_SECRET'),
      'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
  ],
  ```
- `app/Services/Stripe/StripeClientFactory.php` — thin wrapper that builds a
  `\Stripe\StripeClient` from `config('services.stripe.secret')`. Throws a
  clear exception if the secret is missing (fail fast in non-test
  environments).
- `app/Providers/AppServiceProvider.php` (or a new
  `app/Providers/StripeServiceProvider.php` if `AppServiceProvider` is
  already large) — bind `StripeClientFactory`/`\Stripe\StripeClient` as a
  singleton in the container.

## Out of Scope

- No routes, controllers, or webhook handling (Phases 2-3).
- No changes to `Payment`/`Order`/`StripeEvent` models.

## Tests

- `tests/Unit/Services/Stripe/StripeClientFactoryTest.php`
  - Resolves a `\Stripe\StripeClient` instance from config.
  - Throws/handles missing `STRIPE_SECRET` gracefully in a clear, typed way.

## Success Criteria

- `app(\Stripe\StripeClient::class)` (or the factory) resolves correctly in
  a test with `STRIPE_SECRET` set in `.env.testing`/config.
- `php artisan test` passes; `vendor/bin/pint --dirty` clean.
- No other application behavior changes.

**STOP. Await phase approval before implementation.**
