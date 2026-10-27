# Phase 3 — HTTPS Enforcement (Production-Gated)

## Blocking Question (must be answered before this phase starts)

**Is production already served over HTTPS with valid TLS termination
(e.g., at a load balancer or reverse proxy)?** If not, this phase must not
ship until that's true — forcing HTTPS on an app not actually behind TLS
would make it completely unreachable, not just insecure.

## Goal

Force HTTPS in production only, leaving local development (Laravel Herd,
plain HTTP) untouched.

## Files to Modify

- `app/Providers/AppServiceProvider.php` — in `boot()`:
  ```php
  if ($this->app->environment('production')) {
      URL::forceScheme('https');
  }
  ```
- If production sits behind a load balancer/proxy that terminates TLS
  before forwarding plain HTTP internally (common on most PaaS/container
  setups), also configure trusted proxies
  (`App\Http\Middleware\TrustProxies` / Laravel 12's proxy config) so
  Laravel correctly detects the original request was HTTPS — otherwise
  `$request->secure()` (used by Phase 2's HSTS header) will be wrong for
  every request.

## Out of Scope

- No changes to local `.env`/`APP_URL` — this only affects `production`
  environment behavior.

## Tests

- `tests/Feature/HttpsEnforcementTest.php`: with `app()->environment()`
  faked/set to `production`, generated URLs (e.g., `route(...)`,
  `url(...)`) use `https://`; in the default `testing`/`local` environment,
  they don't.

## Success Criteria

- Production forces HTTPS; local dev is unaffected.
- Confirmed correct proxy/trusted-proxy configuration if applicable, so
  `$request->secure()` and HSTS (Phase 2) behave correctly behind any
  TLS-terminating proxy.
- `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval.**
