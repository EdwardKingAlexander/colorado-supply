---
plan: store-toggle-profile-menu
phase: 1
title: User-menu toggle action
status: complete
last_updated: 2026-07-10
depends_on: []
checkpoint: done
---

# Phase 1 — User-Menu Toggle Action

## Goal

Add a "Disable Store" / "Enable Store" item to the admin panel's
profile dropdown that toggles the same `store-settings.enabled` flag
the MCP Dashboard's existing header action already controls.

## Files to Create/Modify

- `app/Providers/Filament/AdminPanelProvider.php` — add
  `->userMenuItems([...])` to the `panel()` chain.
- `tests/Feature/Filament/StoreToggleUserMenuTest.php` — new test file.

## Steps

1. In `AdminPanelProvider::panel()`, add a `->userMenuItems([...])`
   call containing one `Filament\Actions\Action`, mirroring
   `McpDashboard::getHeaderActions()`'s `toggleStore` action
   (`app/Filament/Pages/McpDashboard.php:160-187`) but with all
   state-dependent parts (`label`, `icon`, `color`, `modalHeading`,
   `modalDescription`, the `action()` closure's captured value) as
   Closures that re-read `McpSettings::for('store-settings', ['enabled'
   => true])` on each evaluation — not computed once outside the
   action definition — since this menu renders on every admin page,
   not just one page load like the dashboard header action.
   - Same settings key (`store-settings`), same notification titles/
     bodies, same icons/colors as the existing action, so the two
     controls stay behaviorally identical.
   - Add a one-line comment above both this action and
     `McpDashboard`'s `toggleStore` action noting they intentionally
     duplicate the same ~15 lines and should be kept in sync if either
     changes (per `00-overview.md`'s risk note — not worth extracting a
     shared helper for this size).
2. Confirm the item appears for admin users only (Filament's
   `userMenuItems()` already only renders for authenticated admin
   sessions by nature of the panel's auth guard — no extra `visible()`
   check needed, but verify this assumption live rather than asserting
   it from memory).
3. Write `tests/Feature/Filament/StoreToggleUserMenuTest.php`:
   - Admin user sees the toggle action available on an admin page
     (assert the mounted action exists via Filament's testing helpers,
     not just string-matching HTML).
   - Invoking the action when the store is enabled flips
     `McpSettings::for('store-settings')['enabled']` to `false`.
   - Invoking it again flips it back to `true`.
   - After disabling via the action, a non-admin `web`-guard request to
     `/store` gets a 403 (reuses `EnsureStoreEnabled`'s existing
     behavior — confirms the two systems are actually wired together,
     not just that the setting value changed in isolation).
4. Manual verification (real browser, per this project's UI-change
   testing requirement): log into `/admin` locally, open the profile
   dropdown, confirm the item shows correct current-state label,
   confirm clicking shows the confirmation modal, confirm confirming it
   flips the label and a `/store` visit as a non-admin now 403s, then
   toggle back.

## Tests

- `tests/Feature/Filament/StoreToggleUserMenuTest.php` (new, per Step 3
  above).
- Full suite re-run to confirm no regressions against this session's
  established baseline (71 failed/5 skipped/455 passed).

## Success Criteria / Definition of Done

- Toggle item visible in the admin profile dropdown, label reflects
  live state.
- Clicking it requires confirmation, then flips the store's
  availability exactly as the existing MCP Dashboard control does.
- New test passes; full suite still matches baseline.
- Manually verified live in the browser (not just automated tests),
  per this project's UI-change verification standard.

**STOP. Await phase approval.**
