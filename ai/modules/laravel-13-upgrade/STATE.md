# Laravel 12 → 13 Upgrade — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Approved | 2026-07-09 | User approved; proceeding phase by phase |
| 1 | Preflight audit & dependency pinning | Complete | 2026-07-09 | composer.json pinned; live dry-run reshaped Phase 2's scope; production DB driver confirmed MySQL 8.0 |
| 2 | Core framework + first-party package upgrade | Complete | 2026-07-09 | All packages resolved to plan; two real code fixes required (Inertia SSR gateway, MCP tool method collision); zero new test failures vs. baseline. Confirmed Complete in Phase 5 — Phases 3–5 all built on it and passed |
| 3 | Inertia.js v2 → v3 migration | Complete | 2026-07-09 | Config/package migration done, zero test regressions, real-browser interactive check passed clean |
| 4 | Filament/Livewire verification | Complete | 2026-07-09 | All 5 resources + MFA + write action verified live; found and root-caused a stale-view-cache bug that broke the MFA code UI |
| 5 | Remaining package sweep + full test suite | Complete | 2026-07-09 | Full composer update clean (no held-back packages), full test suite identical to baseline (71/5/455), pint clean, npm build clean, live Stripe test-mode checkout completed end-to-end. Found and fixed a pre-existing (pre-upgrade) missing `statefulApi()` Sanctum registration that 401'd the storefront checkout |
| 6 | Deployment readiness | Awaiting Validation | 2026-10-11 | Deployed and public checks pass; owner checklist (Forge settings, MFA login, checkout) outstanding |

## Log

- 2026-07-09 — Plan drafted at user request ("create a plan on how to update
  the application to the latest Laravel version," pointed at
  https://laravel.com/docs/13.x/upgrade). Research performed before writing
  the plan (per global CLAUDE.md's research requirement for planning):
  - Fetched the official Laravel 13 upgrade guide in full (High/Medium/Low
    impact changes).
  - Grepped the codebase against every breaking-change pattern in the guide
    (CSRF middleware, cache serialization, `upsert()`, MySQL `DELETE...JOIN`,
    model `boot()` nested instantiation, `Manager::extend()`, pagination
    view names, `Js::from`, custom `array_first`/`array_last` helpers, `Str`
    factory persistence). Found exactly one real hit: `VerifyCsrfToken`
    referenced twice in `bootstrap/app.php` (plus mentions in
    `AdminPanelProvider.php`, `config/sanctum.php`,
    `AdminPanelPanelProvider.php` flagged for Phase 2 to confirm as real vs.
    false-positive).
  - Queried packagist (`repo.packagist.org/p2/*.json`) live for ~15 key
    dependencies' actual Laravel-13 compatibility rather than guessing from
    training-data knowledge, since Laravel 13 postdates the assistant's
    knowledge cutoff. Confirmed `filament/filament` (4.11.8, published
    2026-07-02 — six days before this plan), `laravel/sanctum`,
    `spatie/laravel-backup`, `sentry/sentry-laravel`, `laravel/mcp`,
    `maatwebsite/excel`, `tightenco/ziggy` all already support Laravel 13
    without a constraint change.
  - Found two collateral major-version bumps hiding in the upgrade:
    `inertiajs/inertia-laravel` v2→v3 (whole Vue frontend) and
    `spatie/laravel-activitylog` v5 (breaks `CauserResolver`, which
    `app/Support/ActivitylogCauserResolver.php` from the in-progress
    `audit-logging` module depends on — see
    `[[project_gov_b2b_readiness]]` memory). Also found
    `spatie/laravel-permission` is unpinned (`"*"`) and would jump from the
    currently-locked `6.24.0` to `8.3.0` (2 majors) on a plain
    `composer update`.
  - Asked the user two blocking questions before finalizing the plan:
    production PHP version (confirmed already `^8.3+`, unblocking Phase 6)
    and whether to pin `spatie/laravel-permission`/`spatie/laravel-activitylog`
    to their current majors rather than bundle those rewrites into this
    upgrade (user agreed — pin both).
  - Wrote `00-overview.md` and all six phase files. Phases 1–2 are
    sequential; Phases 3 and 4 both depend only on Phase 2 and could be
    worked in parallel by an orchestrator if desired, but Phase 5 depends on
    both finishing first.
  - Awaiting orchestrator/user approval of the overall plan before any
    phase begins.
- 2026-07-09 — User approved the plan and Phase 1 began.
  - `composer.json`: `php` bumped `^8.2` → `^8.3`; `spatie/laravel-permission`
    pinned `*` → `^6.24` (both edits confirmed intact via `git diff`
    afterward). `spatie/laravel-activitylog` already `^4.0`, unchanged.
  - `composer validate` confirms `composer.json` itself is well-formed
    (the "lock file not up to date" message is expected — we haven't run a
    real update yet).
  - Ran a live dry run (`composer require laravel/framework:^13.0
    laravel/tinker:^3.0 laravel/boost:^2.0 pestphp/pest:^4.0 --dry-run`).
    Note: this command's interactive prompt + failure path left
    `composer.lock` in a partially-mutated, inconsistent state (new stray
    packages, changed content-hash) despite claiming to revert — caught via
    `git diff --stat` showing ~990 changed lines in a file that should have
    been untouched, and restored via `git checkout -- composer.lock`. No
    lasting damage; noting this composer quirk in case it recurs in Phase 2
    (`composer require --dry-run` is not a safe no-op the way `composer
    update --dry-run` normally is when combined with interactive prompts).
  - **Real, live conflict list** (supersedes the planning-time
    `00-overview.md` research table where they disagree):
    1. **`laravel/framework: ^13.0` is entirely blocked by composer's
       security-advisory policy** in this exact form. Root cause identified
       via web research: two CVEs affect early 13.x —
       [GHSA-5vg9-5847-vvmq](https://github.com/laravel/framework/security/advisories/GHSA-5vg9-5847-vvmq)
       (CRLF injection via email validation, fixed in 13.10.0) and
       [GHSA-crmm-hgp2-wgrp](https://github.com/laravel/framework/security/advisories/GHSA-crmm-hgp2-wgrp)
       (signed URL path confusion, fixed in 13.12.0). **Fix: target
       `^13.12` instead of `^13.0`** in Phase 2 — latest stable is
       `v13.19.0`, well past both fixed versions, so this is a
       constraint-wording fix, not a real blocker.
    2. **`inertiajs/inertia-laravel` v2.0.16 (locked) hard-requires
       `laravel/framework ^10|^11|^12`** — composer cannot resolve *any*
       subset of this upgrade while inertia-laravel stays locked to v2.
       This means Phase 2 and Phase 3's composer-level bump cannot actually
       be sequenced the way `00-overview.md` assumed; **Phase 2 must bump
       `inertiajs/inertia-laravel` to `^3.0` at the same time as
       `laravel/framework`**, even though Phase 3's *code-level* Vue
       migration work (Blade directive verification, `@inertiajs/vue3`
       bump, router event audit, manual browser smoke test) stays a
       separate phase as planned.
    3. **`livewire/volt` v1.10.1 (locked) hard-requires `laravel/framework
       ^10.38.2|^11|^12`** — same issue as inertia-laravel. Phase 2 must
       bump `livewire/volt` alongside the framework. (`livewire/flux` and
       `filament/filament` were not flagged as hard blockers themselves in
       this run — see below.)
    4. **`pestphp/pest-plugin-laravel` v3.2.0 (locked) requires
       `laravel/framework ^11.39.1|^12.9.2`** — needs an explicit compatible
       version bump in Phase 2 alongside `pestphp/pest` itself (composer's
       own error didn't state the exact required version; Phase 2 should
       let `composer update` resolve it once the pin above is relaxed,
       rather than guessing a version number).
    5. **`filament/filament` v4.3.1 (locked) conflict via `filament/actions`
       → `anourvalar/eloquent-serialize` 1.3.5, which requires
       `laravel/framework` only up to `^12.0`.** Initially looked like a
       real blocker contradicting planning-time research, but verified via
       packagist that `anourvalar/eloquent-serialize` **1.3.10** (newer than
       the locked 1.3.5, still satisfies filament/actions' `^1.2`
       constraint) already supports `laravel/framework ^13.0`. **Not a
       real blocker** — it only surfaced because this dry run didn't ask
       composer to update `filament/filament` or its transitive deps, only
       the four explicitly-named packages. A real `composer update` (Phase
       2, no `--dry-run` gate on this package) should resolve it correctly.
    6. **`tightenco/ziggy` v2.6.0** was also flagged, but only as a
       byproduct of the same security-advisory net around
       `laravel/framework`, not a real version incompatibility (ziggy's own
       constraint is `>=9.0`, already confirmed fine in planning). No action
       needed beyond fixing item 1 above.
  - **Net effect on Phase 2's scope**: it now needs to bump
    `inertiajs/inertia-laravel` (to `^3.0`) and `livewire/volt` (to a
    Laravel-13-compatible version) at the composer.json level as part of
    the framework bump, and must target `laravel/framework: ^13.12` (not
    `^13.0`) to avoid the two known CVEs in early 13.x releases. Phase 3
    remains the home for the actual Inertia v3 *code* migration
    (`@inertiajs/vue3` npm bump, Blade view changes, manual smoke test) —
    only the PHP-side composer constraint moves earlier.
  - Production DB driver confirmed by user: **MySQL 8.0**. This makes the
    guide's "MySQL DELETE Queries With JOIN, ORDER BY, and LIMIT" Low-impact
    item a genuinely applicable risk surface (not moot on a different
    driver) — planning-time grep still found zero `DELETE ... JOIN` query
    patterns in `app/`, so no action is expected, but Phase 5's full test
    run remains the backstop per the original risk note.
  - `composer.json` is left in its pinned state (`php ^8.3`,
    `spatie/laravel-permission ^6.24`); `composer.lock` was restored to its
    original committed state (no real update has been run yet — that's
    Phase 2). No other files were touched this phase.
- 2026-07-09 — Phase 2 implemented.
  - Captured a pre-phase test baseline: `php -d memory_limit=1024M
    vendor/bin/pest` → **71 failed, 5 skipped, 455 passed**. All 71 failures
    are pre-existing and unrelated (RBAC, Google Places, SAM services,
    Settings routes, etc.) — they match the repo's other in-progress WIP
    already visible in `git status` at session start (e.g. deleted
    `QuoteView.vue`/`useQuoteStore.js`, modified `routes/web.php`), not
    anything from this upgrade. (Note: `php artisan test` alone
    fatally OOMs on `SamInsightsDashboardTest` at the default 128M limit,
    a pre-existing issue documented back in the `stripe-integration`
    module's log — running the Pest binary directly with a raised
    `memory_limit` is the workaround used for this and all future test runs
    in this module.)
  - `composer.json`: `laravel/framework` → `^13.12` (not `^13.0`, per the
    CVE finding above), `laravel/tinker` → `^3.0`, `laravel/boost` → `^2.0`,
    `pestphp/pest` → `^4.0`, `pestphp/pest-plugin-laravel` → `^4.0`,
    `inertiajs/inertia-laravel` → `^3.0`. `livewire/volt` left at its
    existing `^1.7.0` (already permits 1.10.5, which supports Laravel 13 —
    confirmed via packagist before editing, no constraint change needed).
  - **New finding during this phase**: `laravel/boost ^2.0` transitively
    requires `laravel/mcp ^0.5.1+`, conflicting with the root
    `laravel/mcp: ^0.2.1` constraint — composer refused to resolve until
    this was widened. Checked GitHub release notes for `laravel/mcp`
    0.6.4–0.8.2 before bumping (this app has two custom MCP servers built
    on it): only additive changes (OAuth, MCP client, UI support), nothing
    touching the `Server`/`Tool`/`Request`/`Response` primitives this app's
    `App\Mcp\Servers\Tool` base class actually uses. Bumped
    `laravel/mcp: ^0.2.1` → `^0.8.0`.
  - Ran `composer update --with-all-dependencies`. Resolved cleanly, no
    security advisories. **All pins held**: `spatie/laravel-permission`
    landed on `6.25.0` (within `^6.24`), `spatie/laravel-activitylog` on
    `4.12.3` (within `^4.0`), `filament/filament` on `4.11.8` (not v5),
    `livewire/livewire` on `3.8.2` (not v4). `laravel/framework` landed on
    `13.19.0`, `laravel/mcp` on `0.8.2`, `inertiajs/inertia-laravel` on
    `3.1.1`, `livewire/volt` on `1.10.5`.
  - CSRF rename (`VerifyCsrfToken` → `PreventRequestForgery`) applied to
    all three real hits: `bootstrap/app.php` (import + the one
    `withoutMiddleware()` call for the `routes/ai.php` MCP group —
    `00-overview.md` said "twice," actual count was one),
    `app/Providers/Filament/AdminPanelProvider.php`, and
    `app/Providers/Filament/AdminPanelPanelProvider.php` (both Filament
    panel providers register it in their own `->middleware([...])` array).
    `config/sanctum.php`'s `ValidateCsrfToken` reference was **left
    unchanged** — confirmed it matches Sanctum's own freshly-updated
    vendor-shipped default config stub verbatim, so changing only our copy
    would create an inconsistency with the package's own convention, not
    resolve one.
  - Re-ran every Medium/Low-impact grep from planning against the
    post-bump code: cache `serializable_classes`, `->upsert(`, pagination
    view names — all still clean, no hits, no action needed.
  - **Two real breakages found and fixed** (both were fatal errors that
    killed the entire test run, not soft test failures):
    1. `app/Inertia/Ssr/FastHttpGateway.php` (custom SSR gateway with
       aggressive timeouts, bound in `AppServiceProvider`) failed to
       declare-match the v3 `Inertia\Ssr\HttpGateway` base class:
       `dispatch()` gained a `?Request $request = null` parameter, and the
       base class's `shouldDispatch()`/`getUrl()` methods were replaced
       with `ssrIsEnabled(Request)`/`getProductionUrl()`/`getHotUrl()` plus
       new Vite-hot-reload SSR branching. Rewrote the subclass to match the
       new v3 method signatures/flow while preserving its original intent
       (custom `Http::timeout()`/`connectTimeout()` wrapper). This is
       PHP-side Inertia v3 fallout that surfaced here because the
       composer-level bump had to move into Phase 2 (see Phase 1 findings)
       — Phase 3 still owns the broader Vue/frontend migration work.
    2. `laravel/mcp` 0.8.2 added a new **public**
       `outputSchema(JsonSchema $schema): array` method to the base
       `Server\Tool` class. Two unrelated app classes —
       `FetchSamOpportunitiesTool` and `FetchNsnDataTool` — already had
       their own **protected**, no-argument `outputSchema(): array` method
       (pure inline documentation of the legacy JSON response shape, not
       part of the MCP protocol, no internal callers). Renamed both to
       `legacyOutputSchema()` to remove the collision; confirmed via grep
       these were the only two occurrences and nothing else in the app
       calls `outputSchema()`.
  - `php artisan optimize:clear` run. `vendor/bin/pint --dirty` run
    (fixed formatting across both this phase's files and other pre-existing
    dirty files in the working tree — expected, `--dirty` operates on all
    uncommitted changes).
  - Post-fix full test run: **71 failed, 5 skipped, 455 passed** — diffed
    the failing test class list against the baseline file-for-file
    (`diff` on sorted `FAIL` lines): **identical set**. Zero new failures
    from this phase's changes.
  - Not yet done (deferred to later phases per the plan, not overlooked):
    Inertia v3's Vue-side/npm migration (Phase 3), Filament/Livewire manual
    browser verification (Phase 4), the remaining ~15 composer packages not
    individually touched this phase (Phase 5's sweep).
- 2026-07-09 — Phase 3 implemented.
  - Re-fetched the live Inertia v3 upgrade guide fresh per the phase's
    "First Step" instruction rather than trusting planning-time research.
    Confirmed the config-restructuring, API-rename, and Blade-markup
    changes below; the guide itself didn't state an exact Vite version
    floor, so that was checked separately (see next bullet).
  - **Resolved the planning-time Vite-version unknown**: checked
    `@inertiajs/vite`'s own published `peerDependencies` on the npm
    registry directly — `"vite": "^7.0.0 || ^8.0.0"`. The installed
    `vite: ^7.0.4` is fully supported; **no Vite bump needed** (the vague
    "drops Vite 6, needs Vite 8" web-search summary from planning was
    imprecise — Vite 7 works fine).
  - `config/inertia.php`: republished shape (read from
    `vendor/inertiajs/inertia-laravel/config/inertia.php` directly) merged
    with this app's customizations, since a blind `vendor:publish --force`
    would have silently dropped them. Changes: top-level `page_paths`/
    `page_extensions`/`ensure_pages_exist` → nested under `pages.*`;
    `testing.page_paths`/`testing.page_extensions` removed (v3 derives
    testing paths from `pages.*` directly — single source of truth now);
    added new `ssr.runtime`/`ssr.ensure_runtime_exists`/`ssr.throw_on_error`
    keys; added new `expose_shared_prop_keys` (true). **Preserved**: the
    app-specific `ssr.connect_timeout`/`ssr.request_timeout` keys (consumed
    by `FastHttpGateway`, not part of stock Inertia config) and the
    capitalized `resource_path('js/Pages')` path (stock default changed to
    lowercase `js/pages`, but this app's actual directory is `Pages`).
  - `package.json`: `@inertiajs/vue3` `^2.1.7` → `^3.0` (resolved to
    `3.6.1`). Also bumped `axios` `^1.7.4` → `^1.15.2` — `@inertiajs/core`
    3.6.1 declares a peer dependency on `axios ^1.15.2` (used for the
    optional Axios HTTP-client adapter), which the old pin didn't satisfy.
    `npm install` succeeded; audit shows pre-existing dev-tooling
    vulnerabilities (vite/rollup/tar/minimatch/concurrently/etc.) untouched
    by this bump — not new. **Noted but not fixed** (out of scope for this
    upgrade): `@inertiajs/server` (used in `resources/js/ssr.js`) is
    genuinely stale at `v0.1.0`, still peer-depending on the ancient
    pre-1.0 `@inertiajs/inertia` package name — it still works (confirmed
    via successful `ssr-build`) but is a candidate for future replacement.
  - `php artisan view:clear` run (v3 changed the compiled `@inertia`
    directive output).
  - Re-ran every router-event/`Inertia::lazy(`/testing-trait/`[inertia]`-
    selector grep from the fresh guide against current code: zero hits,
    confirmed still clean.
  - `npm run build` and `npm run ssr-build` both succeeded cleanly.
  - Full test suite re-run: 71 failed, 5 skipped, 455 passed — diffed
    against the baseline file, **identical failing set**, zero regressions.
  - **Manual verification**: Chrome extension not connected this session
    (same gap as `admin-account-security-complete` Phase 2's still-open CSP check) —
    full interactive browser smoke test (Step 8 of this phase) could not
    be performed. Did the next-best thing via `curl` against the live Herd
    site (`http://colorado-supply.test`): `/`, `/login`, `/register` return
    200; `/store`, `/dashboard` return expected 302 (auth redirect).
    Confirmed the new v3 markup shape directly —
    `<script data-page="app" type="application/json">{...}</script>`
    followed by an empty `<div id="app"></div>` (replaces v2's `data-page`
    div attribute), payload correctly reports
    `"laravelVersion":"13.19.0"` and includes the new `sharedProps` key.
    This confirms server-side markup/config correctness but **not**
    client-side hydration, interactivity, or console errors — that
    real-browser check is still owed, same as the CSP gap in
    `admin-account-security-complete`.
  - Incidental finding while testing: a background `composer run dev` task
    (started to have a dev server available, then found Herd already
    serves the site live) left a stray `public/hot` file, which put the
    app in Vite-hot-reload mode and routed `FastHttpGateway` through v3's
    new hot-reload SSR branch (`/__inertia_ssr`) — an endpoint that only
    exists if the optional `@inertiajs/vite` dev-SSR plugin is installed,
    which it isn't here. Result was a caught-and-logged 404 (graceful
    fallback to client rendering, page still rendered correctly) — not a
    regression, since SSR-during-`npm run dev` never worked pre-upgrade
    either (old code always targeted the production SSR port, which also
    isn't running in dev). Killed the stray dev-server processes and
    removed `public/hot`; confirmed clean behavior afterward.
  - Not yet done at that point: full interactive browser smoke test (Chrome
    extension not connected), Filament/Livewire verification (Phase 4),
    remaining package sweep (Phase 5).
  - 2026-07-09, same day — user connected the Chrome extension; completed
    the owed manual smoke test:
    - Homepage (`/`) loads and renders fully interactive, no console
      errors.
    - Clicked through client-side Inertia navigation (no full page
      reloads): Home → Store → category (Fasteners & Hardware) → subcategory
      (Hex Bolts) → product listing with filters. Breadcrumbs and filter
      UI all rendered correctly.
    - Exercised a real write action: clicked "Add to Cart" on a product,
      then navigated to `/store/cart` — the item's quantity had correctly
      incremented (2x Hex Head Bolt, cart total recalculated to $22.86).
      This confirms a full POST round-trip succeeds under the renamed
      `PreventRequestForgery` CSRF middleware plus the v3 Inertia/Axios
      stack — the two riskiest changes in this phase, now both exercised
      live, not just curl-verified.
    - `/dashboard` correctly redirected to `/admin` for the logged-in admin
      user ("Edward"), and the Filament panel (freshly bumped to 4.11.8 in
      Phase 2) rendered its full sidebar/dashboard cleanly — incidental
      bonus coverage overlapping with Phase 4's scope, though Phase 4 will
      still do its own dedicated deeper pass (MFA challenge flow, relation
      managers, write actions).
    - Console checked for errors after every step (homepage, store,
      category, cart, admin dashboard): zero errors or exceptions
      throughout.
    - Two screenshot calls hit a transient 30s CDP timeout
      ("renderer may be frozen or unresponsive") immediately after a
      click; both resolved on a bare retry with no console errors and the
      correct URL/page state already in place, confirming these were CDP
      connection hiccups, not actual app hangs.
  - Phase 3 marked **Complete** — both automated (identical test baseline)
    and manual (live interactive smoke test) verification passed.
- 2026-07-09 — Phase 4 implemented.
  - Confirmed pins held: `filament/filament` v4.11.8, `livewire/livewire`
    v3.8.2 (no unplanned majors). Re-ran `php artisan filament:upgrade`
    manually to double-check it applied cleanly (it also runs automatically
    via `composer.json`'s `post-autoload-dump` script).
  - `tests/Feature/Filament` suite: 12 failed, 11 passed — diffed the two
    failing classes (`CustomerResourceGooglePlacesTest`,
    `SamInsightsDashboardTest`) against the baseline: both pre-existing,
    already documented, no new Filament-specific failures.
  - **Manual browser verification** (Chrome extension connected):
    - MFA login flow: signed out, logged back in with real credentials.
      Password validation correctly rejected garbled/wrong credentials and
      accepted correct ones (root cause of an early failure: browser
      autofill inserted extra text into a field before typing — cleared
      fields explicitly and retried clean). The MFA challenge screen
      ("VERIFY YOUR IDENTITY", 6-digit code + recovery-code toggle)
      rendered correctly.
    - Wrong TOTP code (`000000`) was correctly rejected without crashing —
      form reset, stayed on the challenge screen, no incorrect auth.
    - **Found and root-caused a real bug**: the 6-digit code entry UI was
      completely invisible — inspecting the DOM directly (`javascript_tool`)
      showed six `<div class="fi-one-time-code-input-digit-field">`
      elements all rendering at `width:0, height:0`, alongside a separate
      hidden `<input class="fi-one-time-code-input">`. Cross-checked
      against the **currently-installed** `filament/support` v4.11.8
      Blade source (`vendor/filament/support/resources/views/components/input/one-time-code.blade.php`)
      and CSS source (`.../css/components/input/one-time-code.css`) —
      the real, current component renders **six separate real `<input>`
      elements** with class `fi-one-time-code-input-digit` (no "-field"
      suffix, no divs, no separate hidden input). The DOM the browser
      actually received didn't match what the installed package would
      produce — a clear signature of **stale compiled Blade views**
      surviving from before the Filament bump, despite Phase 3's
      `php artisan view:clear`. Re-ran `view:clear` (confirmed
      `storage/framework/views/` was already empty — the stale copy had
      been served from OPcache/a prior request cycle, not a leftover file),
      reloaded, and the MFA challenge then worked end-to-end: submitted a
      correct fresh TOTP code and reached the dashboard successfully.
    - **This is a real, load-bearing finding for Phase 6**: production
      deployment MUST clear compiled views (and ideally OPcache) as a
      required post-deploy step, or real users would hit this exact broken,
      unusable MFA code entry UI after the upgrade ships. Added this as an
      explicit note for Phase 6 (Forge's standard zero-downtime deploy
      script typically runs `artisan optimize:clear`/equivalent already,
      but Phase 6 must not assume this — verify it explicitly).
    - Recovery-code toggle (`wire:click="mountAction('useRecoveryCode', ...)"`)
      also initially appeared non-functional via mouse clicks — isolated via
      direct JS `.click()` invocation that it does work correctly
      server-side; the mouse-click non-response was a browser-automation
      tooling quirk (CDP synthetic clicks not registering on that specific
      Livewire-bound element), not an app bug. Confirmed Livewire/Alpine
      were both loaded and healthy throughout via `window.Livewire`/
      `window.Alpine` checks.
    - Verified all 5 target resources live: `OrderResource`
      (`/admin/c-r-m/orders`), `CompanyResource` (`/admin/companies`,
      real data), `ProductResource` (`/admin/products`, real data,
      sortable columns), `RepairRequestResource`
      (`/admin/repair-requests`), `StripeEventResource`
      (`/admin/stripe-events`) — all load cleanly, no console errors.
      Empty-data states on Orders/Repair Requests/Stripe Events are
      expected (no seeded data in this dev DB), not a bug.
    - Exercised a real write action: edited and saved a `Product` record
      via the Filament edit form — "Saved" confirmation toast appeared,
      no errors, confirming the full Livewire form-submission round-trip
      works under Filament 4.11.8 + Laravel 13.19.0.
    - Console checked after every step: only one unrelated error throughout
      the entire session, from a third-party browser extension's own
      content script (`chrome-extension://admmjipmmciaobhojoghlmleefbicajg`)
      — not from the application.
  - Full test suite re-run after all Phase 4 activity: 71 failed, 5
    skipped, 455 passed — identical to baseline, zero regressions.
  - Phase 4 marked **Complete**. The stale-view-cache finding is the most
    important output of this phase — it's invisible in automated tests
    (which don't hit compiled-view staleness the way a real long-running
    server process does) and would have shipped a broken MFA login to
    production without this manual pass.
- 2026-07-09 — Phase 5 implemented (remaining package sweep + full test
  suite).
  - Also resolved a loose end from Phase 2: its status is now **Complete**
    in the table above (was left at "Awaiting Validation"). Phases 3–5 all
    built on top of it and passed, so there was nothing left pending.
  - `composer update` (no package args): **"Nothing to modify in lock
    file"** — every package Phases 1–4 targeted had already fully resolved
    the dependency graph; no held-back packages, nothing left unverified.
    Confirmed via `composer show -D` that all ~15 previously-unverified
    packages (`barryvdh/laravel-dompdf` 3.1.2, `chrome-php/chrome` 1.16.1,
    `stripe/stripe-php` 18.2.0, `smalot/pdfparser` 2.12.5,
    `phpoffice/phpword` 1.4.0, `thiagoalessio/tesseract_ocr` 2.13.0,
    `symfony/http-client` 7.4.14, `symfony/mailgun-mailer` 7.4.14,
    `laravel/breeze` 2.4.2, `laravel/pail` 1.2.7, `laravel/sail` 1.63.0,
    `laravel/mcp` 0.8.2) are installed and Laravel-13-compatible. The two
    pins survived: `spatie/laravel-permission` 6.25.0 (`^6.24`),
    `spatie/laravel-activitylog` 4.12.3 (`^4.0`).
  - Full test suite (`php -d memory_limit=1024M vendor/bin/pest`): **71
    failed, 5 skipped, 455 passed** — identical to the Phase 2 baseline,
    same failing-class set (RBAC/Google Places/SAM API/Settings-route WIP,
    all pre-existing and unrelated to this upgrade). Confirmed the default
    128M limit still fatally OOMs on this suite (`CustomerResourceGooglePlacesTest`
    hit it this run instead of `SamInsightsDashboardTest` — same underlying
    cause, order-dependent) — the raised-memory-limit workaround from Phase
    2 remains required.
  - `vendor/bin/pint --dirty`: found and fixed one file
    (`FetchSamOpportunitiesTool.php` — import ordering/spacing left over
    from the Phase 2 `outputSchema` rename), clean on re-run.
  - `npm run build`: succeeded, no errors.
  - **Manual live Stripe checkout smoke test** (real browser, not
    automated): added items to cart → viewed cart (3 line items across 3
    locations, $22.86) → filled checkout form → **hit a real bug**:
    `POST /api/v1/store/checkout` returned 401, killing checkout entirely.
    Root-caused with `git log`/`git show bootstrap/app.php` history: the
    `/api/v1/store/checkout` route requires `auth:sanctum`, but
    `bootstrap/app.php` never called `$middleware->statefulApi()` —
    present since the route was first added in commit `5caf0a29` (Dec
    2025), so this is a **pre-existing bug unrelated to the Laravel 13
    upgrade**, not a regression. It was invisible to the automated test
    suite because Feature tests authenticate via `actingAs()`, which
    bypasses Sanctum's cookie-stateful check entirely. Flagged to the user
    with three options (fix now / log only / stop); user chose **fix now**.
    - Fix part 1: added `$middleware->statefulApi();` to
      `bootstrap/app.php`'s `withMiddleware` closure — this was the
      genuinely missing piece and is a real code fix, in-repo.
    - Fix part 2: even after part 1, still 401'd locally. Root cause:
      local `.env`'s `APP_URL=http://localhost` didn't match the actual
      Herd serving domain `colorado-supply.test`, so Sanctum's stateful
      domain list (which derives one entry from `APP_URL`) never
      recognized `colorado-supply.test` as a stateful (cookie-authenticated)
      origin. Confirmed `.env` is gitignored (`git check-ignore .env`)
      before editing — this is a local-only fix, not shipped:
      `APP_URL` → `http://colorado-supply.test`, added
      `SANCTUM_STATEFUL_DOMAINS=colorado-supply.test`. Ran
      `php artisan config:clear` after each change.
    - After both fixes: full flow verified live — cart → checkout form
      submission → order created (`Order #COGOV-202607-00001`, $22.86) →
      redirected to Stripe Checkout (confirmed "Sandbox" / test-mode badge,
      amount matched) → paid with Stripe's `4242 4242 4242 4242` test Visa
      → redirected back to the app's own success page ("Thank you for your
      order... We're confirming your payment now"). Verified server-side
      via tinker: `Order` record exists, one `Payment` record
      (`status=pending`, `method=card`, `amount=22.86`) — status is
      correctly `pending`/`unpaid` awaiting the `checkout.session.completed`
      webhook, which doesn't fire in local dev without a `stripe listen`
      forwarder running. Webhook *processing* itself is already covered by
      the passing `StripeWebhookTest`/`ProcessStripeWebhookEventTest`
      suites (part of the 455 passed above), so this isn't a gap — it's
      the expected local-dev boundary of what a live browser smoke test
      can exercise.
  - **New required item for Phase 6**: production (Forge) must be
    confirmed to have `APP_URL` matching its real domain and/or
    `SANCTUM_STATEFUL_DOMAINS` set correctly, or the exact same class of
    bug (storefront checkout 401ing for real customers) is live risk
    independent of this upgrade. This is pre-existing risk surfaced by
    this phase, not caused by it, but Phase 6 is the right gate to verify
    it before this ships.
  - Phase 5 marked **Complete**. `composer.json`/`composer.lock`
    unchanged by this phase (nothing to update); `bootstrap/app.php` and
    local `.env` were the only files touched, both for the
    pre-existing-bug fix above.
- 2026-07-09 — Phase 6 started (deployment readiness).
  - **Scope finding**: nothing from Phases 1–5 had been committed yet —
    the working tree had 189 changed files, and only a subset was this
    upgrade. The rest was substantial pre-existing, uncommitted feature
    work from other already-completed modules (Stripe/PayPal checkout +
    webhooks, repair request intake, audit logging via
    `spatie/laravel-activitylog`, admin MFA, dashboard reporting, backup
    config, Sentry, security headers) that predated this session. Flagged
    to the user with two questions: how to scope the commit, and how to
    handle Forge access (this session has no SSH/Forge CLI/API keys —
    confirmed via `which forge`, `~/.ssh/config`, and grepping `.env` for
    Forge credentials, all empty). User chose: commit everything together,
    and handle Forge-side steps themselves with me providing the checklist.
  - **No Forge access in this session** — Steps 1, 2, 5, 6, 7, 8, 9 all
    require the user's own Forge dashboard/SSH access. I cannot execute or
    directly verify these; the user reports results back and I record them
    here.
  - **Rollback boundary established**:
    - Tagged the last pre-upgrade commit as `pre-laravel-13-upgrade`
      (lightweight tag, since this machine's git had no configured
      committer identity for an annotated tag) → commit `27a719e1`
      ("feat: complete backend terminal UI redesign and ignore local
      reference/errors", 2026-02-11). This is the true pre-upgrade,
      pre-bundle state.
    - Confirmed no sensitive files (`.env`, credentials, keys, tokens)
      were in the changeset before staging (`git status --short | grep
      -iE ".env|credential|secret|.pem|.key$|token"` → empty).
    - Committed all 189 files in one commit (`b043db36`) at the user's
      explicit direction — see that commit's message for the full
      breakdown of upgrade vs. bundled feature work. User then confirmed
      pushing; pushed `main` (`27a719e1..b043db36`) and the
      `pre-laravel-13-upgrade` tag to `origin`.
  - **Rollback procedure** (if the production deploy needs to be undone):
    1. On Forge, redeploy the `pre-laravel-13-upgrade` tag (or commit
       `27a719e1`) instead of `main` — either via Forge's deploy-a-specific-
       commit UI if available, or by temporarily pointing the site's deploy
       branch/ref at the tag, deploying, then pointing it back.
    2. `composer.lock`/`package-lock.json` roll back automatically with the
       code since they're committed in the same tree — no separate lockfile
       rollback step needed.
    3. **No down-migrations required for the upgrade itself** — the Laravel
       13 upgrade is a dependency/framework change with zero schema
       changes. However, the bundled commit *does* include several new,
       not-yet-run migrations from the other feature work (PayPal events,
       location parent_id, repair_requests, admin MFA columns, activity_log
       tables) — if those have already been migrated on production as part
       of this deploy and a rollback is needed, they would need explicit
       down-migrations or a manual reverse migration, since they're
       unrelated to (and not reverted by) rolling back the Laravel 13
       upgrade code itself. Treat "rollback the Laravel upgrade" and
       "rollback the bundled feature migrations" as separate concerns if
       this ever comes up.
    4. If a full git-level revert is preferred over a Forge redeploy:
       `git revert b043db36` on `main` (creates a new commit undoing
       everything, safer than `reset --hard` on a shared branch), then
       redeploy `main`.
  - **User pushed to Forge and the first deploy attempt failed** — reported
    the failure via `errors/errors.md`. Root cause: `composer install`
    failed with 21 unresolvable problems, all the same shape —
    `symfony/*` packages (console, http-foundation, http-kernel, mailer,
    routing, string, translation, etc., all locked at v8.1.x) and
    `spatie/laravel-signal-aware-command` require PHP `>=8.4.1`, but
    production reported PHP `8.3.25`.
    - **This was a real gap in how I ran `composer update` throughout
      Phases 1–5, not a Forge misconfiguration.** This machine's local
      Herd PHP is `8.4.22` (confirmed via `php -v`), while
      `composer.json`'s `"php": "^8.3"` only constrains what *consumers*
      of this package may run it with — it does not force composer's own
      dependency resolution to target 8.3 when the tool itself is
      invoked from a newer PHP, unless `config.platform.php` is
      explicitly pinned. Since it wasn't, every `composer update` in this
      module resolved transitive `symfony/*` versions against my local
      8.4 runtime, silently drifting the lockfile ahead of what
      production's 8.3.25 can actually install.
    - Fix: added `"platform": {"php": "8.3.99"}` under `composer.json`'s
      `config` block, then re-ran `composer update` — confirmed via
      direct `composer.lock` inspection that the conflicting packages
      relocked to PHP-8.3-compatible versions (`symfony/console`
      v8.1.1 → v7.4.14, `symfony/http-foundation` v8.1.1 → v7.4.14,
      `symfony/clock` v8.1.0 → v7.4.8, `spatie/laravel-signal-aware-command`
      stayed at 2.1.2 but now against symfony 7.4). Re-ran the full test
      suite: **71 failed, 5 skipped, 455 passed** — identical to
      baseline, zero regressions from the relock. `composer validate
      --strict` clean. Committed (`daa5d14e`) and pushed to `main`.
    - **Process takeaway for future phases in any module**: when the
      dev machine's PHP version can drift ahead of production (as it can
      here — Herd tracks newer PHP releases faster than a pinned Forge
      server), `config.platform.php` must be set *before* the first
      `composer update` of a major upgrade, not discovered after a failed
      deploy. This should have been part of Phase 1's preflight, not
      found here in Phase 6.
    - Told the user to retry the Forge deploy now that `main` has the fix.
    - User's next report pasted identical content from `errors/errors.md`
      (same timestamp, same `27a719e1..b043db36` commit range, same 21
      problems) — flagged that this looked like stale file content rather
      than a fresh deploy result, since it didn't reflect `daa5d14e` at
      all. Asked the user to confirm a real redeploy was triggered and to
      check Forge's dashboard directly rather than the possibly-stale
      local file.
    - User confirmed: **the deploy succeeded** on retry.
  - **Still outstanding before Phase 6 can close**: the Forge checklist
    items (Steps 1, 2, 5, 7, 8 — PHP version, deploy script contents,
    staging existence, view/OPcache clearing, `APP_URL`/
    `SANCTUM_STATEFUL_DOMAINS`) were handed to the user as a checklist but
    never explicitly confirmed back one-by-one — only that the deploy
    itself succeeded. Step 9 (post-deploy smoke test: homepage, `/admin`
    MFA login completion, storefront page, real checkout through to
    Stripe, `/up`) has not been run yet. Both are needed before marking
    Phase 6 / the module **Complete**.
- 2026-10-11 — Public post-deploy checks against https://cogovsupply.com:
  `/` 200 (Inertia app renders), `/up` 200, `/admin/login` 200 (Filament
  login form renders), `/login` 200, `/store` redirects to `/login`
  (expected for guests/store toggle). Session and XSRF cookies are
  `secure`. Still needs the owner (requires logging in or Forge
  dashboard access): Steps 1, 2, 7, 8 confirmed one by one, staging
  question (Step 5), `/admin` login through the MFA code, and a
  storefront checkout through to the Stripe redirect.
