# Phase 2: Professional Branded Verification Email

## Goal

The verification email looks like it comes from Colorado Supply &
Procurement — branded header, professional copy, plain-text fallback — and
is sent through the queue.

## Sub-Phases

### 2.1 Notification
- `App\Notifications\VerifyEmailAddress extends VerifyEmail implements
  ShouldQueue`.
- Copy: subject "Verify your email address — Colorado Supply &
  Procurement"; greeting with the user's name; one-line purpose ("You're
  receiving this because this address was used to create a Colorado Supply
  account"); CTA button "Verify Email Address"; expiry note (uses
  `config('auth.verification.expire', 60)`); "didn't create an account?"
  ignore note; support contact line.
- Wire into `User::sendEmailVerificationNotification()` (replaces the
  phase-1 default).

### 2.2 Mail theme branding
- `resources/views/vendor/mail/html/header.blade.php`: company wordmark
  (mail clients need a raster or hosted image — use text wordmark styled
  with brand colors, or hosted logo URL from production; decide at
  implementation, text wordmark is the safe default).
- `themes/default.css`: navy `#16334c` header/button, gold `#e9c251`
  accents, readable body typography. Keep the palette consistent with the
  site logo.
- Verify the text theme (`text/`) still renders acceptably.

### 2.3 Queue readiness
- Confirm a queue worker path exists for production (Forge daemon) — if
  none is configured, ship the notification sync (`ShouldQueue` removed)
  and log a follow-up; do not silently strand jobs.

### 2.4 Tests + preview
- Pest: notification renders — assert subject, CTA URL is the signed
  verification route, expiry text present (`Notification::fake()` +
  `toMail` rendering assertions).
- Manual preview: route-less render via artisan tinker or a mailable
  preview route in local only; eyeball HTML in browser and capture
  screenshot for validation.

## Files

| File | Change |
|------|--------|
| `app/Notifications/VerifyEmailAddress.php` | new |
| `app/Models/User.php` | point send override at new notification |
| `resources/views/vendor/mail/html/header.blade.php` | brand header |
| `resources/views/vendor/mail/html/themes/default.css` | brand palette |
| `tests/Feature/Auth/VerificationEmailContentTest.php` | new |

## Success Criteria
- Rendered email shows branded header, professional copy, working signed
  URL; tests green; pint clean.
- Screenshot of the rendered email attached to validation.

## Risks
- Other notifications (order payment etc.) share the published theme —
  restyling `default.css` affects them too. That is desirable (consistent
  brand) but must be eyeballed: render one existing notification to
  confirm it still looks right.

STOP. Await phase approval.
