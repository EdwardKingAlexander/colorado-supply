# Admin Account Security (MFA + Security Headers) — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Approved | 2026-07-09 | User approved proceeding with all unblocked phases |
| 1 | Filament native MFA for the admin panel | Complete | 2026-10-23 | Browser-verified end to end. QR double-encoding bug fixed by upgrading Filament 4.11.8 → 4.15.3 (QR re-verified in browser). See log. |
| 2 | Security headers middleware | Complete | 2026-10-23 | Browser-verified under the production policy; storefront clean. Admin stays report-only by design (see log). |
| 3 | HTTPS enforcement (production-gated) | Complete | 2026-10-23 | Trusted-proxy question resolved: TLS terminates on the Forge box (Let's Encrypt), no `TrustProxies` config needed |
| 4 | Testing & hardening | Complete | 2026-10-23 | Admin CRUD + headers verified, recovery drill run, `admin:reset-mfa --force` fix, admin profile page added, runbook in log |

## Log

- 2026-07-04 — Plan drafted. One of four gap areas planned after the user
  asked for a gov/B2B-readiness assessment (siblings:
  `ai/modules/audit-logging`, `ai/modules/production-observability`, and a
  direct `ComplianceAuditTool` bug fix already applied).
  - Researched Filament v4's MFA support: confirmed native support exists
    (TOTP app-based + email one-time-code, both built-in, no third-party
    plugin needed) via `multiFactorAuthentication()` on the panel. Some
    convenience traits for the app-auth interface were called out as new
    in a later v4.5 point release in the research results; installed
    version here is v4.3.1, so Phase 1's first step is confirming exactly
    what's available at 4.3.1 before writing model code — not assuming the
    4.5 conveniences exist.
  - Scoped to the `admin` guard (Filament panel) only, not the
    customer-facing storefront login — matches what was actually asked for
    ("admin/staff accounts").
  - Flagged MFA recovery (lost-device lockout) as a hard prerequisite
    before requiring MFA — this must exist and be tested before
    `isRequired: true` ships, not discovered after the first support
    ticket.
  - Flagged CSP-breaking-the-UI as the highest risk in Phase 2 — requires
    real browser verification, not just passing automated tests, since a
    CSP violation typically manifests as a silently broken UI rather than
    a server error.
  - **Phase 3 (HTTPS enforcement) is blocked**: needs confirmation that
    production is already served over HTTPS with real TLS termination
    before this ships, since forcing HTTPS on an app not actually behind
    TLS would make it unreachable.
  - Awaiting orchestrator approval of the overall plan, and answers to the
    two blocking questions (Filament 4.3.1 API confirmation is a Phase 1
    research step, not a blocking question for the orchestrator; the
    HTTPS/TLS question in Phase 3 *is* a blocking question for the
    orchestrator), before implementation begins.
- 2026-07-09 — User approved proceeding with all unblocked phases across the
  three gov/B2B-readiness modules, and confirmed production is already
  served over HTTPS with real TLS termination (unblocks Phase 3).
  - Found Phase 1 (MFA) was already implemented in a prior session but
    never reflected here: `Admin` model implements `HasAppAuthentication`,
    `HasAppAuthenticationRecovery`, `HasEmailAuthentication` with encrypted
    secret/recovery-code storage; `AdminPanelProvider` configured with
    `multiFactorAuthentication([AppAuthentication::make()->recoverable(),
    EmailAuthentication::make()], isRequired: true)`; migration
    `2026_07_05_060609_add_multi_factor_authentication_columns_to_admins_table`
    ran; `admin:reset-mfa {email}` Artisan command exists as the recovery
    fallback. This confirms 4.3.1 does *not* need the later 4.5 convenience
    traits — implementing the contracts directly works fine.
  - `tests/Feature/Filament/AdminMfaSmokeTest.php` exists and passes (2/2):
    verifies contract persistence + at-rest encryption and the reset
    command. This is narrower than the plan's originally-described
    `AdminMfaEnrollmentTest` (which specified testing the actual
    enroll/prompt/wrong-code-rejection UI flow) — Filament's native MFA
    enrollment is a Livewire-driven interactive flow that isn't practical
    to assert via a plain HTTP feature test. Real-browser verification of
    the enrollment/challenge/wrong-code UI is still owed before this phase
    can be marked `Complete`, not just `Awaiting Validation`.
  - `vendor/bin/pint --dirty --test` clean (96 files).
  - Proceeding to Phase 2 (security headers) and Phase 3 (HTTPS, now
    unblocked) next.
- 2026-07-09 — Phase 2 implemented:
  - `app/Http/Middleware/SecurityHeaders.php` (new): sets
    `X-Content-Type-Options`, `X-Frame-Options: DENY`,
    `Referrer-Policy: strict-origin-when-cross-origin`,
    `Permissions-Policy: camera=(), microphone=(), geolocation=()`, a CSP,
    and HSTS (only when `$request->secure()`). Registered globally via
    `$middleware->append(...)` in `bootstrap/app.php` so it covers web,
    admin panel, and API responses alike, not just the `web` group.
  - Resolved the plan's open `geolocation` question: confirmed
    `PlacesAutocompleteService` calls Google's Places REST API entirely
    server-side (`Http::...` to `maps.googleapis.com`) — no client-side
    Maps JS or browser geolocation is ever used, so locking down
    `geolocation=()` is safe.
  - CSP starts permissive per the plan's own guidance: `style-src`
    includes `'unsafe-inline'` (Filament/Alpine inline style bindings) and
    `https://fonts.googleapis.com`; `script-src` only adds `'unsafe-eval'`
    and localhost websocket `connect-src` in the `local` environment (Vite
    HMR) — production gets neither. Confirmed by grep that no Blade view
    in this app has an inline `<script>` block, so `script-src 'self'`
    without `'unsafe-inline'` should hold in production.
  - `tests/Feature/SecurityHeadersTest.php`: 5/5 passing (homepage,
    `/admin/login`, an API endpoint, HSTS absent on plain HTTP, HSTS
    present on a secure request).
  - **Manual verification gap**: the plan requires loading the homepage,
    `/repair-services`, `/store`, and the admin dashboard in a real browser
    and confirming zero CSP console violations before this can be marked
    `Complete` — the Chrome browser extension was not connected in this
    session, so that check could not be performed. Verified via `curl`
    against the live Herd site (`http://colorado-supply.test`) that the
    headers are actually present on real responses (homepage and
    `/admin/login`), which is necessary but not sufficient — the real
    in-browser CSP-violation check is still outstanding.
- 2026-07-09 — Phase 3 implemented:
  - `app/Providers/AppServiceProvider.php` — `URL::forceScheme('https')`
    when `$this->app->environment('production')`, matching the plan
    exactly; local/testing untouched.
  - `tests/Feature/HttpsEnforcementTest.php`: 2/2 passing (URLs are
    `https://` under a faked `production` environment, `http://` in
    `testing`).
  - **Open question, not yet resolved**: the plan flags that if production
    sits behind a load balancer/CDN that terminates TLS before forwarding
    plain HTTP internally (e.g. Cloudflare in front of Forge), trusted-proxy
    configuration is also needed so `$request->secure()` (and thus Phase
    2's HSTS header) resolves correctly — otherwise every request looks
    insecure from Laravel's point of view even though the client connection
    really is HTTPS. The user confirmed production is behind real TLS
    termination but this session has no information on whether that
    termination happens on a separate proxy/CDN in front of the Forge box
    or directly on it (e.g. Forge-managed Let's Encrypt on the same
    server, which would need no trusted-proxy config at all). Not
    guessing at this — needs a direct answer before Phase 3 can be marked
    `Complete`.
  - `vendor/bin/pint --dirty` clean (106 files) across Phases 2 and 3.
- 2026-10-23 — Validation pass on Phases 1-3 (Chrome extension connected).
  - **Phase 3**: user confirmed production TLS terminates on the Forge box
    itself (Forge-managed Let's Encrypt), not a separate proxy/CDN, so
    `$request->secure()` resolves correctly with no `TrustProxies` config.
    Open question closed.
  - **Phase 2**: the middleware has changed since July. The storefront is
    now *enforced* with a per-request nonce; `/admin`, `livewire/*`,
    `filament/*` and `api/*` send `Content-Security-Policy-Report-Only`;
    `'unsafe-eval'` and dev websocket sources are added only while Vite's
    `public/hot` file exists; and the Reverb `wss://` origin is in
    `connect-src`.
    - Chrome's native CSP violation reports don't reach the extension's
      console feed (confirmed with a deliberate inline-script canary that
      was blocked but never logged), so "no console errors" proves nothing.
      Instead, with `public/hot` moved aside temporarily (restored
      byte-identical afterwards) so the production policy and built assets
      were served, `/`, `/repair-services` and `/store` were each checked
      for: Vue app mounted, every `<script src>` present in resource timing,
      no inline scripts without the nonce, no inline `on*` handlers, no
      broken images, and no `securitypolicyviolation` events after load.
      All three passed and `eval` was correctly blocked.
    - The unrouted Volt `resources/views/livewire/settings/*` views use
      Alpine but have no routes, so they don't affect the storefront.
    - Admin dashboard: 6 inline scripts without a nonce and about 92 Alpine
      `x-data` components that need `eval`. Enforcing CSP would break
      Filament, so report-only on the admin panel is **deliberate**, not a
      gap.
  - **Phase 1**: browser-tested with a throwaway admin (created, then
    deleted along with its roles and sessions):
    - First login is forced onto `/admin/multi-factor-authentication/set-up`;
      direct navigation to `/admin` and resource pages redirects back to it,
      so setup can't be bypassed.
    - Wrong TOTP code at enrollment → "The code you entered is invalid."
      Valid code → 8 recovery codes shown → authenticator enabled.
    - Login challenge: wrong code rejected; valid TOTP signs in;
      recovery-code-only sign-in works and consumes the code (1 → 0).
    - **Bug (upstream)**: the setup QR image doesn't render.
      `pragmarx/google2fa-qrcode` v4.0.0 already returns a `data:` URI and
      Filament v4.11.8's `AppAuthentication::generateQrCodeDataUri()` wraps
      it in a second `data:image/svg+xml;base64,` when Imagick isn't
      loaded. Fixed upstream ("prevent double encoding of app
      authentication QR codes", 2026-10-14), first released in **v4.15.3**.
      The manual setup key still works, so enrollment isn't blocked, but
      admins can't scan the QR. Needs a decision: upgrade Filament or
      override the method locally.
    - **Quirk (upstream, minor)**: if both the TOTP boxes and the
      recovery-code field are filled at the challenge, the recovery code is
      validated and consumed even when the bad TOTP then fails the form.
      This only happens if a user fills both fields. Noting it, not fixing.
- 2026-10-23 — The user chose the Filament upgrade to fix the QR code:
  `composer update "filament/*" --with-dependencies` took Filament
  v4.11.8 → v4.15.3 and Livewire v3.8.2 → v3.8.11, plus minor
  Symfony/league/nette bumps. Laravel framework stayed at v13.19.0.
  `filament:upgrade` republished the Inter font assets under
  `public/fonts/filament/`.
  - Re-checked in the browser with a second throwaway admin (deleted
    afterwards): the setup QR is now a single-encoded SVG that renders at
    200px.
  - New in 4.15.3: authenticator setup also asks for the admin's
    **current password**.
  - Full suite: 1165 passed, 5 skipped, 0 failed (`--parallel`).
  - Nothing changed in the app code, so there was nothing for pint to
    format.
  - `composer audit` reports 31 advisories across 8 packages. These were
    not triaged here and could be a Phase 4 item.
  - Phases 1-3 now have no open gaps; they're ready for the orchestrator to
    mark `Complete`. Phase 4 (testing & hardening) has not started.
- 2026-10-23 — Phases 1-3 marked `Complete` by the user. Phase 4 scope
  confirmed and started (`In Progress`). The full regression run and the
  MFA enroll / log out / second-factor login check were already done in
  the validation pass above.
  - **Admin panel functional with MFA + headers**: as a pre-enrolled
    `super_admin` throwaway signed in through the TOTP challenge, I created,
    edited and deleted a Business Link (`/admin/business-links`). The edit
    and the hard delete were both confirmed in the DB. Every admin response
    carried `X-Content-Type-Options`, `X-Frame-Options: DENY`,
    `Referrer-Policy`, `Permissions-Policy`, the report-only CSP and HSTS.
  - **Recovery drill (actually run, not just described)**: the throwaway
    was signed out and given credentials only, so it was stuck at "Verify
    your identity" (locked out). After running the reset command, the next
    login skipped the challenge and was forced to
    `/admin/multi-factor-authentication/set-up` (re-enrollment required, no
    panel access until done).
  - **Hardening fix found by the drill**: `admin:reset-mfa` run with
    `--no-interaction` (which is how Forge's "Commands" panel runs)
    answered "no" to the confirmation, reset **nothing**, printed nothing,
    and exited 0. An operator would have believed the admin was reset.
    Fixed in `app/Console/Commands/ResetAdminMultiFactorAuthentication.php`:
    added `--force` to skip the prompt, and a declined or non-interactive
    run now prints "Nothing was changed…" and exits 1. Tests were added to
    `tests/Feature/Filament/AdminMfaSmokeTest.php` (`--force` path;
    declined path plus `--no-interaction` via `Artisan::call` both leave
    the secret and codes untouched and return 1); they were red before the
    fix and pass after (4/4). Pint was run on both edited files and passed.
  - All throwaway admins (ids 19, 20, 21) were deleted along with their
    roles and sessions.

### MFA recovery runbook (locked-out admin)

1. **Verify identity out-of-band first** (phone call or in person). A reset
   gets past the second factor, so it must never happen just because
   someone emailed asking.
2. If the admin still has a **recovery code**: on the "Verify your
   identity" screen, click "Use a recovery code instead" and enter it.
   Leave the 6-digit boxes **empty**: if both are filled, the recovery code
   is used up even when the bad TOTP fails the form. Each code works once.
   After logging in, they regenerate codes (or change devices) at
   `/admin/profile` (user menu → Profile).
3. Otherwise, on the server (Forge → site → SSH, from the site root):
   `php artisan admin:reset-mfa their-email@example.com`
   and answer `yes`. From Forge's **Commands** panel, which is
   non-interactive, use `php artisan admin:reset-mfa their-email@example.com --force`.
   A run without `--force` that isn't confirmed prints "Nothing was
   changed" and exits 1. Check for the success line: "Multi-factor
   authentication reset for …".
4. The admin logs in with email + password and is sent straight to the
   two-factor setup screen (MFA is required, so there's no panel access
   until they re-enroll). They scan the QR or enter the setup key, confirm
   a code, re-enter their current password (Filament ≥ 4.15), and **save
   the new recovery codes**.
5. The reset clears the authenticator secret, all recovery codes and email
   codes for that admin only. No other admins are affected.
- 2026-10-23 — Phase 4, continued:
  - **Admin profile page added** (the user approved it in chat):
    `->profile(isSimple: false)` in `AdminPanelProvider`. This is where
    admins manage MFA themselves after enrollment (regenerate recovery
    codes, change devices, add or remove email codes), plus name, email and
    password. It closes the runbook gap noted above, so step 2 now applies:
    after a recovery-code login, the admin regenerates codes at
    `/admin/profile`.
  - Writing the profile test exposed `Admin::hasEmailAuthentication()`
    returning `null` (a `bool` TypeError, so the profile page returned 500)
    on a just-created model whose `has_email_authentication` DB default
    wasn't hydrated. Fixed with a `(bool)` cast. Rows loaded from the DB
    were never affected (0 of 2 admins had NULL).
  - New test `test_enrolled_admin_can_open_the_profile_page_to_manage_mfa`
    (fails without `->profile()`, passes with it). `AdminMfaSmokeTest` 5/5;
    pint passed on all edited files.
  - The profile page was **not** browser-checked: the Chrome extension
    stopped delivering clicks and keystrokes partway through (JS-driven
    submits still worked, but the TOTP boxes wouldn't take input). Coverage
    is the feature test only.
  - Full suite: 1165 passed, 5 skipped, 3 failed. All failures are in
    `tests/Feature/Dibbs/DibbsBatchQuoteExportTest.php` ("reports exactly
    what blocks a quote…", `ModelNotFoundException` on the fixture RFQ in
    `beforeEach`). They're intermittent: 0-4 failures per run, including
    when that file runs on its own. That test and the whole DIBBS feature
    are **untracked work in progress from another workstream**, and none of
    it touches MFA or headers. The first full run after the Filament
    upgrade had 0 failures. Not fixed here, but flagged to the user.
  - Throwaway admin id 22 was deleted (no `mfa-throwaway%` admins remain).
- 2026-10-23 — The user marked Phase 4 `Complete`, so every phase is now Complete. Code committed as `dc43f9a8` (only this module's hunks; another workstream's uncommitted `favoriteSamOpportunities` change in `Admin.php` was left unstaged). Module directory renamed to `admin-account-security-complete`.
