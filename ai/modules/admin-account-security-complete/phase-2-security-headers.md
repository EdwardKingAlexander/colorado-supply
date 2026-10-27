# Phase 2 — Security Headers Middleware

## Goal

Add baseline HTTP security headers to every response, without breaking the
Inertia/Vue storefront or the Filament admin panel.

## Files to Create/Modify

- `app/Http/Middleware/SecurityHeaders.php` (new) — sets, on every
  response:
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: DENY` (or `SAMEORIGIN` if any legitimate embedding
    use case exists — confirm none does before using the stricter `DENY`)
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy` — a conservative default (e.g., disable
    `camera`, `microphone`, `geolocation` unless a feature already uses
    the Google Places autocomplete, which may need `geolocation` —
    confirm against `CustomerResourceGooglePlacesTest`'s actual usage
    before locking this down)
  - `Content-Security-Policy` — **the risky one**. Start from a policy
    permissive enough to not break Filament (inline styles) and Vite
    (hashed/versioned bundle URLs in production, HMR websocket in local
    dev), then tighten iteratively. Do not ship a CSP that hasn't been
    manually verified against both surfaces (see Risks in 00-overview).
  - `Strict-Transport-Security: max-age=31536000; includeSubDomains` —
    **only** when `$request->secure()` is true, so local HTTP dev is
    never affected.
- `bootstrap/app.php` — register `SecurityHeaders` as global middleware
  (applies to web, admin panel, and API responses alike).

## Out of Scope

- HTTPS enforcement itself is Phase 3, not this phase — this phase only
  adds the HSTS *header* (which is inert unless the connection is already
  HTTPS).

## Tests

- `tests/Feature/SecurityHeadersTest.php`: hitting the homepage, the
  `/admin` login page, and an API endpoint all include the expected
  headers; HSTS is absent on a plain HTTP test request and present when
  the request is marked secure.

## Manual Verification (required, not optional)

- Load the homepage, `/repair-services`, `/store` (as a logged-in user),
  and the Filament admin panel dashboard in a real browser with the new
  headers active. Confirm: no CSP violations in the browser console, all
  styling/JS still loads, Filament's interactive components (tables,
  actions, notifications) still work. This is exactly the kind of change
  that passes automated tests while silently breaking the UI — don't skip
  this step.

## Success Criteria

- All headers present on all response types.
- Zero CSP console violations across the storefront and admin panel in a
  real browser check.
- `vendor/bin/pint --dirty` clean.

**STOP. Await phase approval.**
