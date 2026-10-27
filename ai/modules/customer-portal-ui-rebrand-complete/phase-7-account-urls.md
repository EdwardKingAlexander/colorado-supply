# Phase 7 — Account URLs Under /account

Added 2026-10-23 at the user's request after reviewing Phases 1–6.

## Goal

Group every customer account page under one `/account` prefix that matches
the account tab bar. Keep every old address working with permanent redirects,
because old links are already in sent emails, push alerts and stored
notifications.

No user ID or email goes in the URL. Account pages identify the user from the
session (the Amazon / GitHub / Google convention). An email in the URL would
leak into logs, browser history, analytics and referrers, would invite
someone to edit the URL to view another user's account, and would break links
whenever an email changes.

## Changes
- `routes/web.php`: `/dashboard` → `/account`, `/dashboard/reports(/export)`
  → `/account/reports(/export)`, `/dashboard/locations*` and
  `/dashboard/organization-history` → `/account/…`, `/profile*` (incl.
  two-factor) → `/account/profile*`, `/notifications*` →
  `/account/notifications*`, `/company` → `/account/company`. Route **names**
  are unchanged, so every `route('…')` call keeps working.
- GET redirects (301) from each old page address, keeping the query string
  (Laravel's `Route::permanentRedirect` drops it).
- Hard-coded fallbacks: `AppNotification` default URL, `Navigation.vue`
  fallback.
- Tests: literal paths updated; `tests/Feature/AccountUrlRedirectTest.php`.

## Success Criteria
- New addresses serve the pages; old addresses 301 to them with query
  intact; full suite and the portal browser spec green.
