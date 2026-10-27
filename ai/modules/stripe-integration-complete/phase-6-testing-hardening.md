# Phase 6 — Testing & Hardening

## Goal

Close gaps left by per-phase tests, add resilience (logging, error
handling, rate limiting) and document the local dev / Stripe CLI workflow.

## Files to Create/Modify

- `app/Exceptions/` — ensure `\Stripe\Exception\ApiErrorException` (and
  subclasses) thrown from any service in `app/Services/Stripe/` are caught
  at the controller boundary and converted to clean JSON error responses
  (no raw Stripe exception messages leaked to clients).
- Logging: all Stripe service classes log failures via the default channel
  with enough context (`order_id`, `event_id`, Stripe error code) to debug
  without exposing secrets.
- `routes/api.php` — confirm the webhook route has appropriate throttling
  (Stripe retries with backoff; ensure our own throttle doesn't reject
  legitimate retries — check existing throttle config used for
  `/sam-opportunities/export` as a reference point, 10/60s, and pick an
  appropriate limit for webhooks, likely higher or none).
- `tests/Feature/Api/StripeIntegrationFlowTest.php` — full happy-path
  integration test: create order → checkout session → simulate webhook →
  assert order paid, using Stripe's documented test objects/fixtures (per
  the stripe-integration skill's `TEST_CARDS` reference, adapted to
  PHP/Pest — no live network calls).
- Update root `README.md` or a new `docs/stripe.md` (only if a docs
  convention already exists — otherwise skip per "don't create docs unless
  asked") with: required `.env` vars, `stripe listen --forward-to` command
  for local webhook testing, test card numbers.

## Out of Scope

- Load/performance testing.
- Live-mode verification (manual, by orchestrator, outside this module).

## Tests

- All tests from Phases 1-5 pass together (`php artisan test`).
- New integration test covering the full happy path end-to-end.
- Negative-path tests: Stripe API errors during checkout creation surface
  as a clean error to the frontend, not a 500 with a stack trace.

## Success Criteria

- `php artisan test` green for the whole suite.
- `vendor/bin/pint --dirty` clean.
- Module marked `Complete` in `STATE.md`; orchestrator sign-off recorded.

**STOP. Await phase approval before implementation.**
