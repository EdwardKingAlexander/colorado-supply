# Phase 2 — Shared Shell: Account Layout, Sub-nav, Theme Switch, Footer

## Goal

Make the store shell the single signed-in shell. Add an `AccountLayout` with
an account sub-nav, a Light / Dark / System switch in the account menu, and a
slim footer, so account pages look and behave like part of the store.

## Sub-phases

### 2a. Theme switch
- Create `resources/js/Components/ThemeSwitcher.vue`: a three-option
  segmented `radiogroup` (Light / Dark / System) that calls
  `setThemePreference`. Keyboard operable, `aria-checked`, ≥44px targets.
- Add it to the `StoreLayout` desktop account menu and the mobile drawer.
- Done when: the choice persists across reloads and applies immediately.

### 2b. StoreLayout additions
- The header "Dashboard" link reads "Account" and shows an active state on
  account pages.
- Add Notifications and Company to the account menu/drawer (Company only
  when the user has a company: share `auth.user.has_company` if it isn't
  already available).
- Slim footer: © line, Home, text message terms, cookie preferences
  (re-opens the consent dialog if the banner exposes a hook; otherwise
  omitted).
- Done when: existing store Playwright specs pass unchanged.

### 2c. AccountLayout + AccountNav
- Create `resources/js/Layouts/AccountLayout.vue`: wraps `StoreLayout`,
  renders a page title row (props `title`, `description`, `#actions` slot)
  and `AccountNav`.
- Create `resources/js/Components/Account/AccountNav.vue`: tabs Overview
  (`dashboard`), Reports (`dashboard.reports`), Notifications
  (`notifications.index`), Company (`company.index`), Profile
  (`profile.edit`). Customer-only tabs are hidden for admin-guard viewers.
  `aria-current="page"`, horizontal scroll on narrow screens with no page
  overflow.
- Done when: the layout renders on a sample page with correct active state.

## Out of Scope
- Page bodies (Phases 3–4).

## Risks
- Header crowding at `lg` with the extra link. Mitigation: links move into the
  account menu; the header only gains the active state.

## Tests
- `npm run build`; existing `tests/Browser/*` and Pest suites green.

## Success Criteria
- One header across store and account pages; the theme switch works in
  both; the sub-nav is accessible.
