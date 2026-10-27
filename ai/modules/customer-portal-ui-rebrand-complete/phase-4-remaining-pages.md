# Phase 4 — Remaining Customer Pages

## Goal

Move every other signed-in page onto the shared shell so no customer page
shows Breeze or a different header.

## Sub-phases
- 4a. **Profile** — `Profile/Edit.vue` → `AccountLayout`; partials
  (`UpdateProfileInformationForm`, `UpdatePasswordForm`,
  `TwoFactorAuthenticationForm`, `DeleteUserForm`) → `st-*` text colors;
  sections as panels in a settings layout (heading + description column,
  form column on wide screens). The delete-account modal uses `Modal shell`.
- 4b. **Company** — `Company/Index.vue` → `AccountLayout`, locations list in
  the catalog-index style. `CompanyController` handles a user with no company
  (empty state instead of a 500). Add a feature test for that case.
- 4c. **Notifications** — `Notifications/Index.vue` from `StoreLayout` to
  `AccountLayout` (body is already on `st-*`).
- 4d. **SAM favorites** — `Sam/Favorites.vue` currently renders with no
  layout. Wrap it in `AccountLayout` and do a token pass. Don't add it to the
  sub-nav (it isn't linked today).
- 4e. **Checkout success / cancel** — `Store/CheckoutSuccess.vue`,
  `Store/CheckoutCancel.vue` from `AppLayout` (public marketing nav) to
  `StoreLayout`, with tokens.

Already done by `store-home-redesign` / `store-checkout-redesign`: catalog,
product, cart, checkout, and pay pages are on `StoreLayout`. Only verify them.

## Out of Scope
- `Store/Unavailable.vue` (shown to everyone during maintenance; it stays on
  the public chrome).
- Auth pages: owned by `non-admin-auth-redesign`. Only verify that primitive
  changes look right.

## Tests
- Profile/MFA/password, company, notifications, checkout feature tests and
  browser specs green.

## Success Criteria
- A signed-in customer sees one header from dashboard → store → checkout →
  confirmation → profile.
