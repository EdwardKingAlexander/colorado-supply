# Phase 1: Repair admin navigation and ownership

- Objective: A logged-in admin opens the selected opportunity from Insights and manages personal favorites without customer login.
- Dependencies / entry: Investigation confirmed guard mismatch; user explicitly requested the fix.
- Tasks: Add separate admin favorite pivot/relation; implement admin-authenticated Filament Favorites with focused details and favorite/unfavorite actions; point Insights to its named panel URL; verify customer data and overlapping actor IDs remain isolated.
- Verification: Regression tests for link target, focus without auto-favoriting, idempotent add/remove, admin isolation, guest/customer denial, and existing customer favorites.
- Exit: Focused tests pass and scope is implemented; advance to approved verification.
- Risks: Guard confusion, numeric user/admin ID collisions, untrusted opportunity content/URLs. Use explicit panel guard, separate FK ownership, escaped details and validated HTTP(S) links.
- Out of scope: Reworking customer login, bypassing MFA, redesigning customer Favorites, production migration/deployment, NSN code.

Lifecycle: COMPLETE. Scope implemented; focused tests passed (12 tests, 49 assertions). Additional Insights count regression included in full-suite verification.

