# Phase 3: Admin Toggle + Ops Tools

## Goal

Admins can flip verification on/off from the profile dropdown (exactly like
the store toggle), and support staff can see/fix verification state on
individual users.

## Sub-Phases

### 3.1 Profile-dropdown toggle
- `AdminPanelProvider::emailVerificationToggleUserMenuAction()` modeled on
  `storeToggleUserMenuAction()` (same Action shape, label flips between
  "Disable Email Verification"/"Enable Email Verification", confirmation
  modal, success notification).
- Modal copy warns this is a **global** switch: "New registrations will not
  be asked to verify their email while disabled. Intended for local
  development."
- Uses `EmailVerificationSettings::setEnabled()` from phase 1.

### 3.2 McpDashboard header action
- Mirror action on `McpDashboard::getHeaderActions()` — the store toggle's
  comment mandates the dropdown/dashboard pair stays in sync; follow the
  same pattern and extend that comment to cover this toggle.

### 3.3 UserResource ops tools
- `IconColumn`/badge "Verified" on the users table (from
  `email_verified_at`), toggleable filter.
- Row action "Mark verified" (confirmation modal; sets
  `email_verified_at = now()`); visible only for unverified users;
  gated by an appropriate permission (reuse existing user-management
  permission conventions from RBAC).

### 3.4 Tests
- Filament tests via `livewire()`: toggle action flips the setting value;
  UserResource shows badge; "Mark verified" sets the timestamp and is
  hidden for verified users.
- Regression: with toggle off, registration does not send mail (already in
  phase 1 matrix — re-run).

## Files

| File | Change |
|------|--------|
| `app/Providers/Filament/AdminPanelProvider.php` | new user-menu action |
| `app/Filament/Pages/McpDashboard.php` | mirrored header action |
| `app/Filament/Resources/UserResource.php` | verified column/filter/action |
| `tests/Feature/Filament/EmailVerificationToggleTest.php` | new |
| `tests/Feature/Filament/UserResourceVerificationTest.php` | new |

## Success Criteria
- Toggle visible in the admin profile dropdown, flips the stored setting,
  and immediately changes registration behavior (manual check locally).
- UserResource badge/action work; all tests green; pint clean.

## Risks
- Filament tests in this suite have pre-existing failures — new tests must
  be verified green in isolation (`--filter`) so baseline noise doesn't
  mask them.

STOP. Await phase approval.
