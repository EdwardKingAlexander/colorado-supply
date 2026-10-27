# Verification and rollout

## Repair

Insights previously sent an admin-authenticated session to the customer-only Favorites route. The new named Filament route stays inside the admin panel and displays the focus opportunity even before it is saved. Independent admin-owned favorites prevent collisions with customer IDs. Opening a URL does not create a favorite. Insights counts include both actor types.

## Evidence

- Initial focused run: 12 passed / 49 assertions (focused-tests.txt).
- Final regression run: 6 passed / 37 assertions (final-regressions.txt), including the persisted SAM.gov URL and combined counts.
- Route inspection: /admin/sam-favorites, Filament admin authentication, session and required MFA middleware.
- HTTP and Livewire checks: admin-only session renders the requested opportunity, customer/guest denied, escaped descriptions, safe source URL, add/remove and ownership isolation.
- Pint and git diff --check passed.
- Full suite: 911 passed, 5 skipped, 3,766 assertions (full-tests.txt). The final source-URL correction also passed its separate six-test regression run.

## Rollout

Deploy code with the additive migration 2026_10_05_200000_create_sam_opportunity_admin_favorites_table.php using the normal Forge migration step before serving the updated pages. The migration adds a separate table with cascading foreign keys and a unique admin/opportunity pair. Existing customer favorites are unchanged. No application database migration or production deployment was performed in this session.

After rollout, open SAM Insights while signed into the admin panel, select Open in Favorites, confirm the selected opportunity appears without another login, then add/remove it. Customer document upload, RAG and export workflows remain on the existing customer page; this repair provides admin opportunity viewing and favorites.

