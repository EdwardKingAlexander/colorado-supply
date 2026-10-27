# Non-Admin Mobile UI Reformat - Overview

## Problem Statement

The non-admin site is responsive in the narrow sense that most content stacks,
but it does not use a consistent mobile interaction or type system. Many
buttons, navigation rows, filter chips, quantity steppers, pagination controls,
form controls, and data-table actions render at roughly 28-42 CSS pixels high
with `text-xs` or `text-sm` labels. The result is visually small, difficult to
scan, and less reliable to operate by touch.

Reformat every route-reachable non-admin Vue/Inertia surface so mobile users get
readable type, dependable touch targets, appropriate spacing, single-column task
flows, and mobile-specific presentations for dense catalog and reporting data.
Desktop behavior and backend business logic must remain intact.

## Scope

### In Scope

- Public pages: `/`, `/repair-services`, and their shared header/footer,
  marketing sections, document modal, contact form, and repair request form.
- Public authentication: login, registration, forgot/reset password, email
  verification, and password confirmation.
- Authenticated customer pages: dashboard, reports, company, profile, SAM
  favorites, store catalog, product detail, cart, checkout, payment, success,
  and cancellation pages.
- Shared Vue controls and layouts used by those pages.
- Mobile behavior through 767 CSS px, plus touch-friendly behavior and reflow
  checks on tablet and zoomed desktop layouts.
- WCAG 2.2 AA checks and a stronger 48 CSS px project touch-target standard.

### Out of Scope

- Every `/admin` route, Filament resource/page, Livewire admin view, and files
  under `resources/views/vendor/filament*`.
- Backend ordering, payment, authentication, reporting, SAM, or repair-request
  behavior.
- Homepage information-architecture changes already proposed in
  `ai/modules/site-navigation-restructure/`.
- Unused legacy pages `Store/Public.vue` and `Store/Authenticated.vue` unless a
  route/reference audit during implementation finds that they are reachable.
- A bottom navigation bar in the first pass. The site mixes public marketing,
  account, catalog, and task-specific checkout navigation; a consistent top bar
  plus modal drawer is the safer first implementation. Sticky bottom areas are
  reserved for page actions such as checkout.

## Current Audit Findings

- `PrimaryButton.vue`, `SecondaryButton.vue`, and `DangerButton.vue` use
  `text-xs px-4 py-2`, producing controls below the 44-48 px mobile target.
- The public menu trigger is about 44 px and authenticated menu trigger about
  40 px. Several mobile navigation rows use `py-2`, also below the project
  target.
- Contact, repair, auth, checkout, dashboard, and SAM forms independently define
  field sizing, label sizing, errors, and focus styles. There is no shared
  48 px/16 px mobile form baseline.
- Store filter pills, breadcrumbs, pagination, cart steppers, SAM row actions,
  document actions, and report actions frequently use 12-14 px text with
  `py-1` or `py-1.5`.
- `RecentOrdersTable.vue`, `ReportPreviewTable.vue`, and `Sam/Favorites.vue`
  preserve desktop tables on phones. Horizontal scrolling is technically
  possible in places but is inefficient for repeated mobile tasks.
- The public hero occupies at least `100dvh`, so it does not leave a visual cue
  for the next section. Its action row can become cramped at narrow widths.
- Safe-area variables are not consistently applied, and the viewport metadata
  does not opt into `viewport-fit=cover`.
- `resources/css/app.css` contains no shared mobile tokens or component layer;
  sizing is duplicated across dozens of templates.
- Frontend test files reference Vitest but are skipped and the dependency is not
  installed. There is no browser-level mobile layout or accessibility suite.

## Standards and Project Dimensions

The Gemini CLI was invoked for the requested research, but the installed client
returned a daily API quota exhaustion error before producing findings. The plan
therefore uses the following primary authorities directly:

- WCAG 2.2 SC 2.5.8 requires a 24 by 24 CSS px minimum target at AA, subject to
  documented exceptions: https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum
- Apple recommends a hit region of at least 44 by 44 points:
  https://developer.apple.com/design/human-interface-guidelines/buttons
- Android recommends at least 48 by 48 dp:
  https://developer.android.com/guide/topics/ui/accessibility/views/apps-views
- WCAG 2.2 AA reflow requires vertically scrolling content to work at an
  equivalent width of 320 CSS px, including the 400% zoom use case:
  https://www.w3.org/TR/WCAG22/#reflow
- WCAG text must resize to 200% without loss of content or functionality:
  https://www.w3.org/WAI/WCAG22/Understanding/resize-text
- User-applied line, paragraph, letter, and word spacing must not break content:
  https://www.w3.org/WAI/WCAG22/Understanding/text-spacing
- Interface boundaries and states require 3:1 non-text contrast at AA, while
  normal text requires 4.5:1: https://www.w3.org/TR/WCAG22/#non-text-contrast
- Focus must remain visible and not be entirely obscured by authored sticky
  content: https://www.w3.org/TR/WCAG22/#focus-not-obscured-minimum
- CSS safe-area environment variables are defined by the browser and must have
  fallbacks: https://developer.mozilla.org/en-US/docs/Web/CSS/env

WCAG does not prescribe a universal body font, header height, gutter, or form
height. The following values are explicit project design decisions informed by
the platform recommendations above, not additional WCAG requirements.

| Token / element | Mobile target | Notes |
|---|---:|---|
| General interactive target | 48 x 48 px minimum | Project standard; exceeds WCAG AA and Apple guidance |
| Icon button | 48 x 48 px target, 24 px icon | Visible icon may be smaller than its target |
| Primary/secondary/destructive button | 48 px minimum height | 16 px label, 20-24 px icon, 8 px internal gap |
| Navigation row | 52 px minimum height | 16 px label; active/current state visible |
| Filter chip / pagination target | 48 px minimum height/width | May use compact visual treatment inside full target |
| Checkbox/radio row | 48 px minimum hit height | 20-24 px visible control; full label is clickable |
| Text/select input | 48 px minimum height | 16 px input text to preserve readability and avoid mobile zoom behavior |
| Textarea | 128 px minimum height | User-resizable where supported |
| Header | 64 px plus `safe-area-inset-top` | Project decision sized around 48 px actions |
| Mobile drawer | `min(360px, 100vw)` | Full height, safe-area aware, modal scrim, focus trapped |
| Page gutter | 16 px at 320-389; 20 px at 390-639; 24 px at 640+ | Never reduce readable content below 288 px at 320 viewport |
| Spacing rhythm | 4 px base; common gaps 8/12/16/24/32 | Adjacent controls normally have at least 8 px separation |
| Card/panel padding | 16 px mobile; 20-24 px larger screens | Radius no more than 8 px unless an existing system requires it |
| Body / input text | 16 px / 24 px line height | Primary readable and interactive copy |
| Secondary text | 14 px / 20 px | Noncritical supporting copy only |
| Caption / metadata | 13 px / 18 px minimum | Never sole label for an action or critical value |
| Mobile H1 / H2 / H3 | 32/40, 28/36, 20/28 px | Marketing hero may use 40/48 when it still fits at 320 px |
| Sticky bottom action area | 64 px minimum plus bottom safe area | Must not obscure focus or content; hide/reflow for keyboard where needed |

## Architecture (Textual)

```text
resources/css/app.css + tailwind.config.js
  -> shared mobile tokens/utilities (targets, gutters, safe areas, focus)
  -> shared Vue controls (buttons, inputs, checkbox, errors, modal)
  -> public and authenticated navigation/layout shells
  -> page families:
       public marketing + repair
       auth + profile/company
       catalog + product + cart
       checkout + dashboard/reports + SAM
  -> Playwright viewport/a11y checks + existing Pest route/behavior tests
```

## Responsive Behavior

- Use mobile-first base classes. Do not shrink desktop styles down with fixed
  widths; add density at larger breakpoints.
- At 320-767 px, forms use one column. Two-column address and identity forms
  start at 640 px only when labels and values still fit at 200% text sizing.
- Primary action groups stack full width on narrow phones and may become inline
  from 480/640 px when space permits.
- Filters use a 48 px "Filters" trigger and modal/bottom sheet on phones rather
  than keeping a long sidebar above product results.
- Product and operational data use scan-friendly mobile cards/definition lists.
  A true comparison table may retain horizontal scrolling only when the
  two-dimensional relationship is essential and each row still has usable
  mobile actions.
- Sticky headers and bottom actions include safe-area padding and `scroll-margin`
  so focused controls and anchored sections are not hidden.
- Motion respects `prefers-reduced-motion`; hover is never the only way to reveal
  an action or state.

## Data Flow

No server-side data contracts change. Existing props, endpoints, form names,
cart state, payment redirects, and validation responses remain the source of
truth. Reformatting changes only presentation, responsive state, accessible
names/announcements, and client-side menu/filter disclosure behavior.

```text
Existing controller/API response
  -> existing Vue state/computed properties
  -> responsive presentation chosen by CSS/component state
  -> same existing route/API action
  -> inline error/status announced and rendered without layout overlap
```

## Cross-Module Dependencies

- `non-admin-auth-redesign` Phase 2 is awaiting validation and
  `BrandedAuthLayout.vue` is currently modified. Mobile auth work must begin
  from the validated version and must not revert those changes.
- `store-checkout-redesign` has phases awaiting validation. Store/checkout
  mobile work must preserve its completed behavior and begin only after its
  current implementation is accepted or explicitly incorporated.
- `site-navigation-restructure` is awaiting plan approval. Its route and
  information-architecture decisions are independent; Phase 2 here owns mobile
  sizing, drawer behavior, and accessibility only.

## Risks

- **Scope size**: all non-admin pages span several workflows. Mitigate with
  shared foundations first and one page family per approved phase.
- **Desktop regressions**: mobile-first class changes can alter larger screens.
  Preserve established `md`/`lg` compositions and include desktop snapshots in
  every browser test set.
- **Dense workflow inflation**: applying 48 px visually to every small element
  can reduce scan efficiency. Keep icons/glyphs visually compact while the
  containing target remains 48 px, and use disclosure instead of crowding.
- **Sticky UI versus keyboards/focus**: bottom actions can cover focused fields.
  Test virtual-keyboard layouts, focus scrolling, and WCAG 2.4.11.
- **Tables becoming lossy cards**: mobile cards must expose every value and
  action available in the desktop table; tests compare content/action parity.
- **In-flight work**: overlapping module changes can be overwritten. Re-audit
  git status and relevant module states before each phase.

## Phase List

| # | Phase | File |
|---|---|---|
| 1 | Mobile foundations and shared controls | `phase-1-foundations-controls.md` |
| 2 | Navigation and layout shells | `phase-2-navigation-layouts.md` |
| 3 | Public homepage and repair services | `phase-3-public-pages.md` |
| 4 | Authentication, profile, and company pages | `phase-4-account-pages.md` |
| 5 | Store discovery, product, and cart | `phase-5-store-browsing-cart.md` |
| 6 | Checkout, dashboard, reports, and SAM | `phase-6-customer-workflows.md` |
| 7 | Cross-device accessibility and regression hardening | `phase-7-accessibility-qa.md` |

Status tracking lives in `STATE.md`.

**STOP. Await approval before any implementation begins.**
