# Phase 1: Conditional Verification Enforcement

## Goal

New registrations must verify their email before reaching gated customer
surfaces — but only while the `email-verification` setting is enabled.
Existing users are grandfathered in.

## Sub-Phases

### 1.1 Settings support
- Add `App\Support\EmailVerificationSettings` (thin wrapper over
  `McpSettings::for('email-verification', ['enabled' => true])` with
  `isEnabled(): bool` and `setEnabled(bool): void`) so phases 2/3 and the
  middleware share one accessor instead of scattering string keys.

### 1.2 Model + notification hook
- `User implements MustVerifyEmail`.
- Override `sendEmailVerificationNotification()`:
  - setting disabled → return (no send);
  - enabled → `$this->notify(new VerifyEmailAddress)` (phase 2 class; in
    this phase, temporarily send the framework default so phase 1 is
    independently shippable).

### 1.3 Middleware + routes
- `App\Http\Middleware\EnsureEmailIsVerifiedWhenEnabled` — mirror of the
  framework `EnsureEmailIsVerified` but returns `$next($request)` when the
  setting is disabled. Alias `verified.enabled` in `bootstrap/app.php`.
- Apply to gated customer routes in `routes/web.php` (confirm exact list at
  phase start: dashboard, orders/quotes, checkout, SAM favorites; NOT the
  verification routes, profile logout, or public store browsing).
- Registration redirect: after register, unverified users land on
  `verification.notice` (Breeze default behavior once MustVerifyEmail is
  present — verify, don't assume).

### 1.4 Grandfather migration
- Migration: `UPDATE users SET email_verified_at = created_at WHERE
  email_verified_at IS NULL` — idempotent, guarded to run once (existing
  users keep access; only post-deploy registrations are gated).

### 1.5 Tests (Pest)
- Registration leaves `email_verified_at` null and redirects to notice.
- Unverified user is redirected from a gated route; verified user passes.
- Signed verify link sets `email_verified_at` (Breeze test exists — adapt).
- Resend endpoint throttles.
- With setting disabled: unverified user passes gated routes AND no
  notification is sent (`Notification::fake()`).

## Files

| File | Change |
|------|--------|
| `app/Models/User.php` | implement MustVerifyEmail + send override |
| `app/Support/EmailVerificationSettings.php` | new |
| `app/Http/Middleware/EnsureEmailIsVerifiedWhenEnabled.php` | new |
| `bootstrap/app.php` | middleware alias |
| `routes/web.php` | apply `verified.enabled` to gated groups |
| `database/migrations/...backfill_email_verified_at.php` | new |
| `tests/Feature/Auth/EmailVerification*` | new/extended |

## Success Criteria
- Full matrix in 1.5 green; `vendor/bin/pint --dirty` clean; suite failure
  count does not exceed the 71-failure pre-existing baseline.
- Manual: register a throwaway user locally with `MAIL_MAILER=log`,
  confirm redirect to notice page and the signed URL in `laravel.log`
  verifies the account.

## Risks
- Route-gating list is judgment — reviewed with user at phase validation.
- Queue not required yet (default notification is sync unless queued).

STOP. Await phase approval.
