# Lifehouse Audit Remediation - State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Complete | 2026-07-09 | User approved continuing into Phase 1 |
| 1 | Baseline and issue ledger | Complete | 2026-07-09 | Clean desktop/mobile Lighthouse baseline captured and issue ledger updated |
| 2 | Static delivery and missing manifest | Complete | 2026-07-09 | Manifest added; missing favicon PNG references removed; Apache cache rules added |
| 3 | Image payload and LCP | Complete | 2026-07-09 | WebP landing assets added, Unsplash removed, hero LCP image made eager/high priority, unsized logo fixed |
| 4 | Fonts and third-party loading | Complete | 2026-07-09 | Google Fonts made non-blocking; reCAPTCHA moved to on-demand loader; report-only CSP allowlist updated |
| 5 | Accessibility fixes | Complete | 2026-07-09 | User approved continuing into Phase 6 |
| 6 | Security headers | Complete | 2026-07-09 | Public CSP enforced with nonces; COOP and HTTPS-only HSTS added; Trusted Types evaluated and deferred |
| 7 | Final verification and closeout | Awaiting Validation | 2026-07-09 | Final local Lighthouse comparison, targeted tests, build, and Pint all passed; production HTTPS/HSTS and Apache cache headers still need a live-deploy check |

## Issue Ledger

| Finding | Category | Evidence | Owner | Planned Phase | Status | Notes |
|---|---|---|---|---|---|---|
| Missing cache lifetimes for first-party assets | Performance | `141242`; clean baseline `cache-insight` | App/deploy | 2 | Awaiting deploy validation | Added Apache `.htaccess` rules for `/build/assets/`; PHP built-in server cannot validate `.htaccess` headers; still needs a live Apache check after deploy |
| Missing `/site.webmanifest` returns 404 | Best Practices | `141642`; clean baseline console errors | App | 2 | Confirmed | `public/site.webmanifest` present and returns 200 locally; Phase 7 final run shows zero console errors |
| Oversized local JPGs | Performance | `141323`, `141335`, `141346`, `141504`; clean baseline `total-byte-weight` | App | 3 | Confirmed | Optimized WebP imports in place; Phase 7 final local run: desktop total 1,403 KiB, mobile total 1,287 KiB (baseline was ~9.35 MiB) |
| Remote Unsplash payload | Performance | `141346`, `141440`, `141504`; clean baseline network payloads | App | 3 | Confirmed | Replaced two remote Unsplash images in `Compliance.vue` with local `compliance-bg-1400.webp`; no remote image hosts in Phase 7 final network payloads |
| LCP image lacks high fetch priority | Performance | `141409`, `141430`; clean mobile LCP 31.5 s | App | 3 | Confirmed | Hero uses real eager image elements with `fetchpriority="high"`, width/height, and WebP srcset; Phase 7 final local LCP: desktop 1.6 s, mobile 8.6 s (not directly comparable to the production-network baseline, but consistent with earlier local phase runs) |
| Image elements missing explicit width/height | Performance/CLS | `141456`; clean baseline `unsized-images` | App | 3/5 | Confirmed | Added dimensions to hero/about/compliance/industry images and navigation logo; local Lighthouse `unsized-images` score is 1 |
| Render-blocking CSS/font request | Performance | `141354`; clean baseline `render-blocking-insight` | App | 4 | Confirmed | Google Fonts made async; local Phase 4 render-blocking now only app CSS with ~10 ms savings |
| Font display/preconnect warning | Performance | `141402`, `141417`; clean baseline `font-display-insight` and preconnect warning | App | 4 | Confirmed | Local Phase 4 `font-display-insight` passed |
| Unused JavaScript from GTM/reCAPTCHA/app bundle | Performance | `141448`; clean baseline `unused-javascript` | Mixed | 4 | Partially resolved | reCAPTCHA removed from initial page payload; GTM and app bundle remain and are deferred to future bundle/application work |
| Third-party cookie warnings from reCAPTCHA | Best Practices | `141616`, `141635`, `141650` | External/product decision | 4 | Accepted / deferred | reCAPTCHA remains for spam protection and now loads on demand; Google cookie behavior is external |
| Low contrast text/buttons/footer | Accessibility | `141520`; clean baseline `color-contrast` | App | 5 | Confirmed | Darkened amber CTA buttons, raised footer muted text contrast, and adjusted hero eyebrow colors; Phase 7 final accessibility score is 100 on desktop and mobile |
| Redundant logo alt text | Accessibility | `141530` | App | 5 | Confirmed | Navigation logo images are now decorative with `alt="" aria-hidden="true"` while the enclosing links retain the existing screen-reader label; local `/` Lighthouse `image-alt` passed |
| Manual accessibility checks | Accessibility | `141558` | App | 5 | Awaiting manual validation | Local Lighthouse accessibility is 100 for `/` and `/repair-services`; remaining keyboard/focus checks require a real browser/manual pass, not just Lighthouse |
| Missing CSP enforcement | Best Practices/Security | `141705`, `141729`; manual header check | App/deploy | 6 | Confirmed (public routes) | Public routes now receive enforced nonce-based `Content-Security-Policy`; admin/API remain report-only because Filament/Livewire/vendor views still use inline scripts; Phase 7 final `csp-xss` audit passed with score 1 |
| Missing HSTS | Best Practices/Security | `141714`; manual header check | Deploy/app | 6 | Awaiting deploy validation | Middleware emits `Strict-Transport-Security: max-age=31536000; includeSubDomains` only for secure requests; local HTTP has no HSTS by design, production HTTPS/proxy forwarding must be validated after deploy |
| Missing COOP | Best Practices/Security | `141721`; manual header check | App/deploy | 6 | Confirmed | Added `Cross-Origin-Opener-Policy: same-origin-allow-popups`; local header probe confirms it on public, admin, and API responses |
| Missing Trusted Types directive | Best Practices/Security | `141729`; clean baseline details | App/deploy | 6 | Accepted / deferred | Enforcing `require-trusted-types-for 'script'` caused a local no-paint Lighthouse failure; deferred until app and third-party scripts are audited for Trusted Types compatibility. Final Phase 6 Lighthouse best-practices score is 100 without enforcement |
| Chrome extension long task/unused JS | Performance | `141440`, `141448`, `141511` | Environment | 1 | Clean-profile noise | Extension URLs disappeared from clean Lighthouse baseline |
| SEO score already 100 | SEO | `141242`; clean baseline | None | 7 | Confirmed | Desktop and mobile SEO both 100 |

## Phase 1 Baseline

Target: `https://cogovsupply.com/`

Artifacts:

- `artifacts/lighthouse-baseline-desktop.report.html`
- `artifacts/lighthouse-baseline-desktop.report.json`
- `artifacts/lighthouse-baseline-mobile.report.html`
- `artifacts/lighthouse-baseline-mobile.report.json`

Lighthouse command notes:

- Ran via `npx --yes lighthouse` using local Chrome at
  `C:\Program Files\Google\Chrome\Application\chrome.exe`.
- Chrome flags included `--headless=new`, `--disable-extensions`,
  `--disable-component-extensions-with-background-pages`, `--no-first-run`,
  and `--disable-default-apps`.
- Lighthouse emitted a Windows temp cleanup `EPERM` after report generation on
  both runs; reports were still written successfully and parsed.

Scores:

| Form Factor | Performance | Accessibility | Best Practices | SEO | Fetch Time |
|---|---:|---:|---:|---:|---|
| Desktop | 93 | 97 | 96 | 100 | 2026-07-09T20:33:11.456Z |
| Mobile | 57 | 97 | 96 | 100 | 2026-07-09T20:33:45.222Z |

Important clean-baseline findings:

- Mobile is the priority performance target: FCP 8.7 s, LCP 31.5 s, TTI
  33.4 s, Speed Index 8.7 s.
- Desktop performance is better but still has the same structural issues:
  cache headers, payload weight, render-blocking fonts/CSS, unused JS, contrast,
  and missing manifest.
- Total transfer size remains about 9.35 MiB on both desktop and mobile.
- `/site.webmanifest` is still the only clean-baseline console error.
- Chrome extension findings from the screenshots did not reproduce with a clean
  Lighthouse profile.
- Live header checks for `https://cogovsupply.com/` currently show
  `Cache-Control: no-cache, private`, `X-Content-Type-Options: nosniff`, and
  `X-Frame-Options: SAMEORIGIN`, but no CSP, HSTS, or COOP headers.

## Log

- 2026-07-09 - Reviewed `CLAUDE.md` planning workflow and all 25 screenshots
  in `errors/lifehouse audit/`.
- 2026-07-09 - Created module plan, seven phase files, and state ledger. No
  production code, assets, config, or tests changed.
- 2026-07-09 - User approved continuing into Phase 1. Phase 1 set to
  `In Progress`; scope remains limited to baseline verification and issue
  ledger updates.
- 2026-07-09 - Phase 1 baseline completed:
  - Saved clean desktop and mobile Lighthouse reports under `artifacts/`.
  - Parsed exact scores and failing audit IDs.
  - Confirmed app-owned findings and separated Chrome extension noise.
  - Checked live response headers manually because Lighthouse security-header
    scoring was inconsistent with its details and the actual headers.
  - Set Phase 1 to `Awaiting Validation`.
- 2026-07-09 - User approved proceeding after Phase 1. Marked Phase 1
  `Complete` and set Phase 2 to `Awaiting Phase Approval`. Phase 2 scope:
  add/correct `/site.webmanifest`, verify icon references, and implement or
  document first-party static cache headers for fingerprinted build assets.
- 2026-07-09 - User approved Phase 2 implementation. Phase 2 set to
  `In Progress`.
- 2026-07-09 - Phase 2 implemented:
  - Added `public/site.webmanifest` with existing icon references only:
    `/favicon.svg`, `/favicon.ico`, and `/apple-touch-icon.png`.
  - Removed missing `/favicon-32x32.png` and `/favicon-16x16.png` links from
    `resources/js/Pages/Welcome.vue`; those files do not exist in `public/`.
  - Added Apache `.htaccess` cache rules:
    - `/build/assets/` -> `Cache-Control: public, max-age=31536000, immutable`.
    - manifest/favicon/apple-touch icon files -> `Cache-Control: public,
      max-age=86400`.
  - Verified `public/site.webmanifest` parses as JSON and all manifest icon
    files exist.
  - Verified local HTTP `GET /site.webmanifest` returns 200 using PHP's built-in
    server. Cache headers still require Apache/production validation because the
    built-in server does not apply `.htaccess`.
  - Ran `npm run build` successfully.
  - Set Phase 2 to `Awaiting Validation`.
- 2026-07-09 - Pre-Phase 3 stabilization after user-reported `/` and `/store`
  errors in `errors/errors.md`:
  - Root cause was an enforcing CSP from `App\Http\Middleware\SecurityHeaders`
    blocking Google Tag Manager, reCAPTCHA, inline scripts, and Ziggy's inline
    `@routes` data. The resulting frontend `route(...)` failures were
    downstream of Ziggy route data being blocked, not missing Laravel routes.
  - Changed CSP from `Content-Security-Policy` to
    `Content-Security-Policy-Report-Only` so existing app behavior works while
    CSP enforcement remains a staged Phase 6 task.
  - Updated `tests/Feature/SecurityHeadersTest.php` to assert report-only CSP
    and avoid a database-dependent API route for header-only coverage.
  - Verified local `/` and `/store` responses no longer include an enforcing
    CSP header and do include `Content-Security-Policy-Report-Only`.
  - Tests/build passed:
    `php artisan test tests/Feature/SecurityHeadersTest.php`,
    `php artisan test tests/Feature/RepairServiceControllerTest.php tests/Feature/StoreTest.php`,
    `npm run build`, and
    `vendor/bin/pint app/Http/Middleware/SecurityHeaders.php tests/Feature/SecurityHeadersTest.php`.
- 2026-07-09 - User approved continuing after Phase 2/stabilization. Marked
  Phase 2 `Complete` and set Phase 3 to `Awaiting Phase Approval`. Phase 3
  scope is still accurate: reduce oversized first-party JPG payloads, address
  remote Unsplash imagery in `Compliance.vue`, improve hero/LCP image loading,
  and add dimensions/aspect-ratio where image layout stability requires it.
- 2026-07-09 - User approved Phase 3 implementation. Phase 3 set to
  `In Progress`.
- 2026-07-09 - Phase 3 implemented:
  - Generated optimized WebP assets under `resources/images/optimized/`:
    `pipeline-1600.webp`, `pipeline-900.webp`, `grinding-900.webp`,
    `compliance-bg-1400.webp`, and six industry card images.
  - Updated `Hero.vue` to replace CSS background-image LCP loading with real
    eager `<img>` elements using `srcset`, `sizes="100vw"`,
    `fetchpriority="high"`, `decoding="async"`, and explicit dimensions.
  - Updated `About.vue`, `IndustriesServed.vue`, and `Compliance.vue` to import
    optimized WebP assets, lazy-load below-fold images, and set dimensions.
  - Removed remote Unsplash image requests from `Compliance.vue`.
  - Added explicit dimensions to logo images in `Navigation.vue`, resolving the
    local Lighthouse `unsized-images` audit.
  - Captured `artifacts/phase-3-home-desktop.png` using local headless Chrome;
    hero renders correctly.
  - Local Phase 3 Lighthouse desktop artifact:
    `artifacts/lighthouse-phase3-local-desktop-final.report.html/json`.
    Scores: Performance 91, Accessibility 97, Best Practices 92, SEO 100.
    `total-byte-weight` passed with 2,231 KiB total transfer; `unsized-images`
    passed. `image-delivery-insight` still reports about 62 KiB of possible
    savings on hero/about images, which is small enough to defer unless visual
    validation suggests more compression is acceptable.
  - Verification passed:
    `npm run build`;
    `php artisan test tests/Feature/RepairServiceControllerTest.php tests/Feature/StoreTest.php tests/Feature/SecurityHeadersTest.php`.
  - Browser plugin was unavailable (`iab` not available), so visual validation
    used headless Chrome screenshot instead.
  - Set Phase 3 to `Awaiting Validation`.
- 2026-07-09 - User approved proceeding after Phase 3. Marked Phase 3
  `Complete` and set Phase 4 to `Awaiting Phase Approval`. Phase 4 scope is
  still accurate: improve font loading/preconnect behavior, review GTM and
  reCAPTCHA loading, preserve contact/repair forms and analytics, and record
  third-party cookie warnings as accepted or deferred where external.
- 2026-07-09 - User approved Phase 4 implementation. Phase 4 set to
  `In Progress`.
- 2026-07-09 - Phase 4 implemented:
  - Changed Google Fonts loading in `resources/views/app.blade.php` from
    preload + blocking stylesheet to `media="print"` / `onload` async loading
    with a `noscript` fallback.
  - Removed the initial-page reCAPTCHA API script from `app.blade.php`; retained
    `window.googleRecaptchaSiteKey` for forms.
  - Added `resources/js/Support/recaptcha.js`, a shared on-demand loader that
    appends the reCAPTCHA script only when a form requests a token and reuses
    the same promise across forms.
  - Updated `Contact.vue` and `RepairRequestForm.vue` to use
    `getRecaptchaToken(action)`.
  - Updated report-only CSP allowlists in `SecurityHeaders.php` for GTM,
    Google Analytics, and reCAPTCHA script/frame/connect behavior. CSP remains
    report-only until Phase 6.
  - Verified local initial HTML no longer contains
    `www.google.com/recaptcha/api.js`, still contains
    `window.googleRecaptchaSiteKey`, keeps GTM, and uses async font CSS.
  - Local Phase 4 Lighthouse desktop artifact:
    `artifacts/lighthouse-phase4-local-desktop.report.html/json`.
    Scores: Performance 90, Accessibility 97, Best Practices 96, SEO 100.
    Total transfer dropped to 1,401 KiB; `font-display-insight` passed; initial
    network no longer includes reCAPTCHA. Remaining unused JS is GTM plus the
    app bundle and is deferred to later bundle/application work.
  - Verification passed:
    `npm run build`;
    `php artisan test tests/Feature/RepairServiceControllerTest.php tests/Feature/StoreTest.php tests/Feature/SecurityHeadersTest.php`;
    `php artisan test tests/Feature/SecurityHeadersTest.php` after Pint; and
    `vendor/bin/pint app/Http/Middleware/SecurityHeaders.php`.
  - Set Phase 4 to `Awaiting Validation`.
- 2026-07-09 - User approved proceeding after Phase 4. Marked Phase 4
  `Complete` and set Phase 5 to `Awaiting Phase Approval`. Phase 5 scope is
  still accurate: fix low-contrast UI text/buttons/footer copy, review logo alt
  text redundancy, and perform keyboard/focus/manual accessibility checks.
- 2026-07-09 - User approved Phase 5 implementation. Phase 5 set to
  `In Progress`.
- 2026-07-09 - Phase 5 implemented:
  - Darkened public CTA buttons from amber-600/500 to amber-700/800 with
    matching focus outlines in `Capabilities.vue`, `Contact.vue`,
    `Footer.vue`, `RepairRequestForm.vue`, and `RepairServicesTeaser.vue`.
  - Raised footer muted copy and footer link contrast for light and dark
    themes in `Footer.vue`.
  - Adjusted homepage and repair page hero eyebrow text from
    `text-primary-700 dark:text-primary-400` to
    `text-primary-800 dark:text-primary-300`.
  - Marked desktop and mobile navigation logo images decorative while keeping
    each logo link's screen-reader label.
  - Verification passed:
    `npm run build`;
    `php artisan test tests/Feature/RepairServiceControllerTest.php tests/Feature/StoreTest.php tests/Feature/SecurityHeadersTest.php`.
  - Local Phase 5 Lighthouse artifacts:
    `artifacts/lighthouse-phase5-local-desktop-final.report.html/json` and
    `artifacts/lighthouse-phase5-repair-desktop.report.html/json`.
    Scores: `/` Performance 90, Accessibility 100, Best Practices 96, SEO 100;
    `/repair-services` Performance 92, Accessibility 100, Best Practices 96,
    SEO 100. `color-contrast` passed on both routes; `image-alt` passed on
    `/`. One home Lighthouse run still emitted the known Windows temp cleanup
    `EPERM` after writing reports.
  - Set Phase 5 to `Awaiting Validation`.
- 2026-07-09 - User approved continuing into Phase 6. Marked Phase 5
  `Complete` and set Phase 6 to `In Progress`.
- 2026-07-09 - Phase 6 implemented:
  - Updated `App\Http\Middleware\SecurityHeaders` to generate a per-request CSP
    nonce, add `Cross-Origin-Opener-Policy: same-origin-allow-popups`, enforce
    CSP on public non-admin routes, and keep admin/API/Livewire routes in
    report-only mode.
  - Kept `Strict-Transport-Security` HTTPS-only and covered it with secure
    request tests.
  - Updated `resources/views/app.blade.php` to apply the nonce to inline public
    scripts, `@routes`, Laravel Vite-generated tags/prefetch scripts, and the
    local Laravel Boost browser logger via `Vite::useCspNonce($cspNonce)`.
  - Removed CSP `unsafe-eval` from built-asset responses; it is now only added
    when `public/hot` indicates the Vite dev server is active.
  - Evaluated Trusted Types enforcement. Adding
    `require-trusted-types-for 'script'` caused a local Lighthouse `NO_FCP`
    no-paint failure, so Trusted Types enforcement was deferred rather than
    shipping a breaking header.
  - Verification passed:
    `vendor/bin/pint app/Http/Middleware/SecurityHeaders.php tests/Feature/SecurityHeadersTest.php`;
    `npm run build`;
    `php artisan test tests/Feature/RepairServiceControllerTest.php tests/Feature/StoreTest.php tests/Feature/SecurityHeadersTest.php`.
  - Local Phase 6 Lighthouse artifact:
    `artifacts/lighthouse-phase6-local-desktop-final2.report.html/json`.
    Scores: Performance 90, Accessibility 100, Best Practices 100, SEO 100.
    `csp-xss`, `trusted-types-xss`, `errors-in-console`, and
    `inspector-issues` all passed in the final report. Lighthouse still emitted
    the known Windows temp cleanup `EPERM` after writing reports.
  - Header probe confirmed `/` has enforced CSP and COOP, while `/admin/login`
    and `/api/v1/does-not-exist` have report-only CSP plus COOP.
  - Set Phase 6 to `Awaiting Validation`.
- 2026-07-09 - Continued the module (picking up from prior Codex work) per
  user request. Verified all Phase 6 claims still match the working tree
  (`SecurityHeaders.php`, CSP/COOP/HSTS tests, nonce plumbing in
  `app.blade.php`) and marked Phase 6 `Complete`. Set Phase 7 to
  `In Progress` per the standing blanket phase-gate approval for this
  project (continue-where-left-off requests do not require a fresh
  per-phase stop when no new ambiguity exists).
- 2026-07-09 - Phase 7 final verification performed:
  - Ran `php artisan test tests/Feature/SecurityHeadersTest.php
    tests/Feature/RepairServiceControllerTest.php tests/Feature/StoreTest.php`
    - all 17 tests passed.
  - Ran `npm run build` - succeeded.
  - Ran `vendor/bin/pint app/Http/Middleware/SecurityHeaders.php
    tests/Feature/SecurityHeadersTest.php` - passed, no changes needed.
    (Did not run a blanket `vendor/bin/pint --dirty` because the working
    tree currently has many unrelated uncommitted changes from other
    in-progress modules; formatting was scoped to files this module
    touched.)
  - Started `php artisan serve` locally and ran final Lighthouse audits
    against `/`:
    - `artifacts/lighthouse-phase7-final-desktop.report.html/json`:
      Performance 90, Accessibility 100, Best Practices 100, SEO 100.
      FCP 0.9 s, LCP 1.6 s, TTI 1.7 s, total transfer 1,403 KiB.
    - `artifacts/lighthouse-phase7-final-mobile.report.html/json`:
      Performance 64, Accessibility 100, Best Practices 100, SEO 100.
      FCP 4.1 s, LCP 8.6 s, TTI 8.6 s, total transfer 1,287 KiB.
    - Both runs: zero `errors-in-console` items, `inspector-issues` score 1,
      `csp-xss` and `trusted-types-xss` both score 1 (informative).
    - Lighthouse again emitted the known Windows temp cleanup `EPERM` after
      writing both reports; both report files were confirmed written and
      parse correctly.
  - Confirmed via direct header probe against the local server that `/`
    returns the enforced nonce-based CSP and
    `Cross-Origin-Opener-Policy: same-origin-allow-popups`.
  - Compared against the Phase 1 production baseline (Performance 93/57
    desktop/mobile, Best Practices 96, Accessibility 97): Best Practices and
    Accessibility both now score 100 locally, and total transfer weight
    dropped from ~9.35 MiB to under 1.4 MiB. Local-server and
    production-network numbers are not directly comparable (no real network
    latency/CDN locally), so this is a structural/regression comparison, not
    a claim that production will hit these exact scores.
  - Updated the issue ledger: items verifiable locally moved from
    "Awaiting validation" to "Confirmed"; items that require production
    infrastructure (Apache cache headers, real HTTPS/HSTS) remain
    "Awaiting deploy validation"; manual keyboard/focus accessibility checks
    remain "Awaiting manual validation" since Lighthouse cannot fully cover
    them.
  - Remaining open items for the orchestrator, none of which block closing
    this module's code changes:
    1. Deploy and verify Apache `.htaccess` cache headers on
       `/build/assets/` in production.
    2. Verify HSTS is emitted correctly behind production HTTPS/proxy.
    3. Run a manual keyboard/focus accessibility pass in a real browser.
    4. Optionally reduce remaining unused JS from GTM and the app bundle in
       a future module.
  - Set Phase 7 to `Awaiting Validation` (deploy-only items remain open;
    all locally-verifiable work is done and passing).
