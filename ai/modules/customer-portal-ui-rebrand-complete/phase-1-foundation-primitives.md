# Phase 1 — Theme Foundation & Shared Primitives

## Goal

Add the per-browser theme preference (dark default) for portal pages, remove
the Laravel logo, and brand-align the shared Breeze primitives without
breaking the auth pages that also use them. No page layout changes yet.

## Sub-phases

### 1a. Server-side portal detection
- Create `app/Support/PortalTheme.php` with `appliesTo(string $component): bool`.
  Portal components: `Dashboard`, `Dashboard/*`, `Profile/*`, `Company/*`,
  `Notifications/*`, `Sam/*`, and `Store/*` except `Store/Unavailable`,
  `Store/Public`, `Store/Authenticated`.
- `resources/views/app.blade.php`: add `data-portal-theme` to `<html>` when
  it applies; add a nonce'd inline head script that resolves
  `localStorage['cs-theme']` (`light|dark|system`, default `dark`) and sets
  the `dark` class plus `meta[name=theme-color]` before first paint.
- Done when: a Pest unit test covers `appliesTo` and a feature test asserts
  the attribute appears on `/dashboard` and not on `/`.

### 1b. Client theme module
- Create `resources/js/Support/theme.js`: `getThemePreference()`,
  `setThemePreference(pref)`, `applyPortalTheme()`, `forceSiteDark()`, with a
  `prefers-color-scheme` listener while the preference is `system`. Make it
  SSR-safe (no `window` access at import time) and wrap storage in try/catch.
- `AppLayout.vue`, `BrandedAuthLayout.vue`, `GuestLayout.vue` call
  `forceSiteDark()` on mount. `StoreLayout.vue` calls `applyPortalTheme()` on
  mount (wiring only; the switch UI lands in Phase 2).
- Done when: client navigation public → store → public lands on the right
  theme each time.

### 1c. Brand logo
- `ApplicationLogo.vue`: render `@images/logo-cleansed.svg` /
  `logo-cleansed-light.svg` (dark), same as `StoreLayout`. Keep the path and
  name so callers don't change.
- Done when: no Laravel SVG is left in `resources/js`.

### 1d. Primitives
- `PrimaryButton`: brand blue fill (`primary-700`, hover `primary-800`).
- `SecondaryButton`, `DangerButton`, `TextInput`, `Checkbox`, `InputLabel`,
  `InputError`, `DropdownLink`, `NavLink`, `ResponsiveNavLink`: indigo →
  brand blue focus/active. Inside the shell, use `[.store-shell_&]:` variants
  (secondary surface → `st-panel`/`st-line`/`st-ink`, labels → `st-ink`,
  errors → readable red in dark).
- `Modal.vue`: accept the shell context. The panel gets `store-shell` when
  opened from a shell page (prop `shell`, default false), so the `st-*` tokens
  resolve inside the `<dialog>`.
- No plain `dark:` variants (auth pages are light cards in a dark document).
- Done when: prop APIs are unchanged and no `indigo-` is left in the touched
  primitives.

## Risks
- Auth pages restyle slightly (brand blue button instead of gray-800). That's
  intended; verified in Phase 6.
- Light-mode flash if the head script is blocked by CSP → it uses the nonce.

## Tests
- `tests/Unit/PortalThemeTest.php`, a feature assertion on the attribute.
- `npm run build`, `php artisan test`, `vendor/bin/pint` on edited PHP files.

## Success Criteria
- Theme preference resolves before first paint on portal pages; public
  and auth pages stay dark.
- Brand logo everywhere `ApplicationLogo` is used.
- Build and tests green.
