# Phase 3 — Navigation & Footer Routing + Store Link

## Goal

Replace homepage-only anchor links in `Navigation.vue` and `Footer.vue` with
real named routes so links work from any page, and add a "Store" link
visible to every visitor (logged-out clicks redirect to `/login` via the
existing `auth.web_or_admin` middleware — confirmed behavior, no middleware
changes needed).

## Files to Create/Modify

- `resources/js/Components/Navigation.vue`:
  - "Home" → `route('home')` (was `#home`).
  - "About" → `route('about.index')` (was `#about`).
  - "Capabilities" → `route('capabilities.index')` (was `#capabilities`).
  - "Repair Services" → unchanged (`route('repair-services.index')`,
    already correct).
  - "Contact" → `route('contact.index')` (was `#contact`).
  - New "Store" link, positioned after "Repair Services" and before
    "Contact", visible unconditionally (remove the current
    `shouldShowStoreLink`/`isUserAuthenticated` gating on the *nav link
    itself* — but leave the existing authenticated-only "Store" link in the
    right-hand auth-links block alone, since that's a different,
    already-correct piece of UI. Simplest: add one new always-visible
    "Store" entry in the main link group; don't touch the existing
    conditional one in the auth-links block, to avoid showing "Store"
    twice for authenticated users — hide the *existing* conditional one
    once the new unconditional one is added, since it's now redundant).
  - Apply the same changes to both the desktop link row and the mobile
    menu.
- `resources/js/Components/FrontEnd/Footer.vue`:
  - "Quick Links" column: same anchor → route swaps, plus a new "Store"
    link, matching Navigation's positioning logic.

## Out of Scope

- No changes to `EnsureStoreEnabled` or `AuthenticateWebOrAdmin` middleware.
- `Compliance`/`IndustriesServed` still use in-page anchors
  (`#credentials`/`#industries` per `Footer.vue`) since those sections still
  live on the homepage — leave those two anchors as-is.

## Success Criteria

- From `/repair-services` (or any other page), clicking "About",
  "Capabilities", "Contact", or "Store" in the nav correctly navigates
  there (Store redirects a logged-out visitor to `/login`).
- No duplicate "Store" entries shown for an authenticated user.
- `npm run build` succeeds.

**STOP. Await phase approval.**
