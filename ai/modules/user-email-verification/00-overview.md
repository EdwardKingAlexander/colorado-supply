# User Email Verification

## Problem Statement

Customer registrations (web guard `User`s) currently activate immediately —
no proof of email ownership. The owner wants:

1. Mandatory email verification for normal users on registration.
2. A professional, brand-styled verification email.
3. An admin-panel toggle (profile-dropdown item, same pattern as the
   store-availability toggle) to disable verification in development.
4. Best-practice extras where sensible.

## Current State (verified in code)

- `app/Models/User.php` does **not** implement `MustVerifyEmail` — that is
  the only missing switch; the rest of the scaffold already exists:
  - `routes/auth.php:39-48` — `verification.notice`, `verification.verify`
    (signed), `verification.send` (throttled 6/min) routes.
  - `resources/js/Pages/Auth/VerifyEmail.vue` — Inertia notice page.
  - `RegisteredUserController` already fires `event(new Registered($user))`.
- `users.email_verified_at` column exists (standard migration).
- Settings pattern to mirror: `App\Support\McpSettings::for()/put()`
  (encrypted key-value in `mcp_settings` table) + profile-menu `Action` in
  `AdminPanelProvider::storeToggleUserMenuAction()` duplicated as a header
  action on `McpDashboard`. The comment there mandates keeping both in sync.
- Mail: Mailgun in production (`no-reply@mail.cogovsupply.com`, from-name
  "Colorado Supply & Procurement LLC"); the Markdown mail theme is already
  published at `resources/views/vendor/mail/` (html + text + default.css,
  header partial with logo slot). **Known issue:** Mailgun sends fail in
  local dev — a second reason the dev toggle exists.
- Admin guard (`Admin` model, Filament panel) is **out of scope** — it has
  MFA instead; only the customer `web` guard is affected.

## Design

### Enforcement (conditional MustVerifyEmail)

- `User` implements `MustVerifyEmail`.
- New setting key `email-verification` in `McpSettings`, payload
  `['enabled' => bool]`, default **enabled**.
- New middleware `App\Http\Middleware\EnsureEmailIsVerifiedWhenEnabled`
  (wraps Laravel's `verified` behavior): if the setting is disabled → pass
  through; otherwise redirect unverified users to `verification.notice`.
  Registered under alias `verified.enabled`.
- `User::sendEmailVerificationNotification()` is overridden to no-op when
  the setting is disabled (prevents doomed Mailgun sends in dev) and to send
  the custom branded notification otherwise.
- Routes gated with `verified.enabled`: authenticated customer surfaces —
  dashboard, profile-adjacent account pages, SAM favorites (exact list
  confirmed at phase start from `routes/web.php`). The verification
  notice/resend/logout routes stay reachable.
- **Owner decision (2026-07-10): checkout is NOT verification-gated.**
  Unverified users may place orders; they get full order visibility through
  the emailed order confirmation + signed status tracker (phase 4), which
  works without login or verification.

### Professional email

- `App\Notifications\VerifyEmailAddress extends
  Illuminate\Auth\Notifications\VerifyEmail`, queued (`ShouldQueue`),
  professional copy (company name, why-you-received-this, expiry note,
  support contact), signed URL from the parent class.
- Brand the published mail theme: logo in `header.blade.php`, navy/gold
  accents in `themes/default.css` (matches `#16334c` / `#e9c251`).
- Plain-text fallback comes free from the Markdown mailable.

### Admin toggle (mirrors store toggle)

- `emailVerificationToggleUserMenuAction()` in `AdminPanelProvider` profile
  dropdown: label "Disable Email Verification" / "Enable Email
  Verification", confirmation modal, writes the `McpSettings` key.
- Matching header action on `McpDashboard` (the store toggle comment
  requires the pair; follow the same convention).

### Best-practice extras (included)

- Filament `UserResource`: "Email verified" badge column + a manual
  "Mark verified" action (support/ops escape hatch when a customer can't
  receive mail).
- Pest feature tests for the full matrix (see phases).
- Docs note in `.env.example`/README: use `MAIL_MAILER=log` locally, since
  Mailgun rejects local sends.

## Data Flow

```
Register.vue → RegisteredUserController@store → Registered event
  → User::sendEmailVerificationNotification()
      setting disabled → no-op (user proceeds, unenforced)
      setting enabled  → queued VerifyEmailAddress (branded, signed URL)
User clicks link → verification.verify (signed, throttled) → email_verified_at set → intended redirect
Unverified user hits gated route → verified.enabled middleware → verification.notice (VerifyEmail.vue, resend button)
Admin profile dropdown → toggle → McpSettings['email-verification']
```

## Risks / Constraints

- **Existing users** predate verification: their `email_verified_at` is
  null. Enforcement would lock them out. Mitigation: one-time data
  migration marks all users created before the feature ships as verified.
- **Queue dependency**: queued notification requires a running worker
  (dev: `composer run dev` runs one; production: confirm Forge worker — if
  absent, note to create one or fall back to sync send).
- Toggle is global (not per-environment); turning it off in production is
  possible — the confirmation modal copy must warn about that.
- Two auth stacks exist in the repo (Inertia controllers + some Livewire
  auth views); the active customer flow is the Inertia one — phase 1
  confirms the Livewire views are dormant before ignoring them.

## Phase Table

| Phase | File | Goal | Depends on |
|-------|------|------|------------|
| 1 | `phase-1-enforcement.md` | Conditional MustVerifyEmail enforcement + backfill migration | — |
| 2 | `phase-2-branded-email.md` | Professional queued verification email + themed mail template | 1 |
| 3 | `phase-3-admin-toggle-and-ops.md` | Profile-dropdown toggle, McpDashboard action, UserResource ops tools | 1 |
| 4 | `phase-4-order-tracker.md` | Order confirmation email with signed status-tracker link + public tracker page | 2 (mail theme) |
| 5 | `phase-5-status-notifications.md` | Status-change emails + in-app notification bell (database channel) | 4 (tracker links) |
