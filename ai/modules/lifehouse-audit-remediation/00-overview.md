# Lifehouse Audit Remediation - Overview

## Problem Statement

The screenshots in `errors/lifehouse audit/` show the public landing page at
`cogovsupply.com` scoring:

| Category | Score | Main Problems |
|---|---:|---|
| Performance | 88 | Large image payloads, missing cache lifetimes, LCP image priority, render-blocking font CSS |
| Accessibility | 97 | Low contrast text/buttons/footer copy, redundant logo alt text |
| Best Practices | 73 | Missing web manifest, absent security headers, third-party cookie warnings |
| SEO | 100 | No current remediation needed |

The goal is to correct the actionable audit findings one phase at a time,
track state explicitly, and avoid mixing high-risk security/header changes with
visual or asset optimizations.

## Evidence Reviewed

Screenshots reviewed from `errors/lifehouse audit/`:

- `Screenshot 2026-07-09 141242.png` - summary scores and cache lifetime issue.
- `Screenshot 2026-07-09 141323.png` through `141346.png` - image delivery
  savings across first-party JPGs and one Unsplash image.
- `Screenshot 2026-07-09 141354.png` through `141430.png` - render-blocking
  CSS/fonts, font-display, LCP request discovery, preconnect, and LCP breakdown.
- `Screenshot 2026-07-09 141440.png` through `141511.png` - third-party work,
  unused JavaScript, explicit image dimensions, network payloads, and extension
  noise.
- `Screenshot 2026-07-09 141520.png` through `141558.png` - contrast,
  redundant alt text, and manual accessibility checks.
- `Screenshot 2026-07-09 141616.png` through `141729.png` - third-party
  cookies, missing `/site.webmanifest`, Chrome Issues panel, missing CSP/HSTS/
  COOP, and missing Trusted Types directive.

## Likely Implementation Surfaces

- `resources/views/app.blade.php` - global head, fonts, Google tag,
  reCAPTCHA script, Vite entrypoint, shared metadata.
- `resources/js/Pages/Welcome.vue` - page-specific head metadata, favicon and
  manifest links.
- `resources/js/Components/FrontEnd/Hero.vue` - LCP background image,
  contrast, CTA styling, modal focus behavior.
- `resources/js/Components/FrontEnd/IndustriesServed.vue` - large industry card
  images, explicit dimensions, responsive image loading.
- `resources/js/Components/FrontEnd/Compliance.vue` - remote Unsplash images,
  third-party payload, background image loading.
- `resources/js/Components/FrontEnd/Footer.vue` - low-contrast footer text and
  CTA styling.
- `resources/images/**` - large local JPG assets:
  `construction-site.jpg`, `manufacturing.jpg`, `grinding.jpg`,
  `food-processing.jpg`, `pipeline.jpg`, `energy.jpg`, `automotive.jpg`,
  `aerospace.jpg`.
- `public/` - favicon assets exist, but `site.webmanifest` is missing.
- Laravel/web-server headers - cache policy and security headers may live in
  app middleware, server config, or deployment config depending on current
  hosting.

## Constraints

- No production code changes until the overall plan is approved.
- Each phase must be approved before work begins.
- Each phase updates `STATE.md` before and after work.
- Do not begin the next phase until the current phase is marked `Complete`.
- Keep fixes narrowly scoped to audit findings unless a dependency is found.
- Treat Chrome extension entries in Lighthouse as environment noise unless
  reproduced in a clean browser profile.
- Treat Google reCAPTCHA cookie warnings as third-party behavior; do not remove
  reCAPTCHA without explicit product/security approval.
- Header changes must be staged because CSP, COOP, HSTS, and Trusted Types can
  break third-party scripts, PDF embeds, auth, analytics, or forms.

## Architecture (textual)

```
Audit screenshots
  -> issue ledger in STATE.md
  -> clean baseline Lighthouse run
  -> low-risk static delivery fixes
  -> image/LCP fixes
  -> font and third-party loading fixes
  -> accessibility fixes
  -> security header rollout
  -> final regression Lighthouse run
```

## Data Flow

```
Browser requests landing page
  -> Laravel/Inertia renders app shell and page head
  -> Vite-built JS/CSS and imported image assets load from /build/assets
  -> external Google Fonts, GTM, and reCAPTCHA scripts load
  -> landing Vue components render hero, compliance, industries, contact, footer
  -> browser receives cache/security headers from app/server layer
```

## Risks

- **Image quality regression** - aggressive compression could make hero and
  industry imagery look cheap. Mitigate with visual comparison and responsive
  variants rather than one-size compression.
- **Broken LCP background handling** - current hero uses CSS background images;
  priority hints are more reliable with real image elements. Mitigate with a
  phase-specific visual check across desktop/mobile.
- **Font flash or layout shift** - changing font loading may alter metrics and
  typography. Mitigate by preserving Inter or replacing it deliberately with a
  local equivalent.
- **Third-party script breakage** - deferring GTM or reCAPTCHA can break
  analytics/contact forms. Mitigate with form submission tests and network
  verification.
- **CSP breakage** - enforcing CSP too early can block Inertia/Vite chunks,
  Google services, inline scripts, or PDF embeds. Mitigate with report-only
  first, then enforce after violations are handled.
- **HSTS deployment risk** - HSTS should only be enabled when HTTPS is stable
  for the production domain and subdomain policy is understood.

## Phase List

| # | Phase | File |
|---|-------|------|
| 1 | Baseline and issue ledger | `phase-1-baseline-issue-ledger.md` |
| 2 | Static delivery and missing manifest | `phase-2-static-delivery-manifest.md` |
| 3 | Image payload and LCP | `phase-3-image-payload-lcp.md` |
| 4 | Fonts and third-party loading | `phase-4-fonts-third-party-loading.md` |
| 5 | Accessibility fixes | `phase-5-accessibility.md` |
| 6 | Security headers | `phase-6-security-headers.md` |
| 7 | Final verification and closeout | `phase-7-final-verification.md` |

Status tracking and the issue ledger live in `STATE.md`.

**STOP. Await approval before any implementation begins.**
