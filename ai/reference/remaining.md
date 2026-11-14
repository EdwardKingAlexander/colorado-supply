# Module status: complete vs. remaining

Last updated: 2026-11-13

Source of truth is each module's `STATE.md` in `ai/modules/`. A module counts
as complete only when every phase is `Complete`; it is then renamed with a
`-complete` suffix (see the Feature Planning Workflow in `AGENTS.md`).

**Totals:** 15 complete, 22 remaining.

## Complete (renamed to `-complete`)

| Module | Notes |
|---|---|
| `admin-account-security-complete` | 4/4 phases, owner approved 2026-10-23. Commit dc43f9a8 (not pushed). Includes Filament 4.15.3 upgrade, admin profile page, `admin:reset-mfa --force`; MFA recovery runbook in its STATE.md |
| `category-csv-import-complete` | 3/3 phases, owner validated 2026-10-10 |
| `customer-portal-ui-rebrand-complete` | 7/7 phases, owner approved 2026-10-23. Account pages on the store shell under `/account` (301s from old URLs), Light/Dark/System theme, starter-kit cleanup. Run `npm run ssr-build` before deploy |
| `mobile-ui-reformat-complete` | 7/7 phases |
| `normal-user-dashboard-panel-complete` | 6/6 phases |
| `nsn-sourcing-workflow-complete` | 5/5 phases, owner validated 2026-10-10. Commits a472e21d, ad7b1bf5, 4535cd6e (pushed 2026-10-23). Deployment = owner runs its RUNBOOK.md |
| `product-detail-page-complete` | 5/5 phases, owner validated 2026-10-10. Commits 612c7d8b, ea77ce8e, f3cfedde, b10b1926 (pushed 2026-10-23). Follow-ups listed in its STATE.md |
| `sam-api-key-format-fix-complete` | 2/2 phases, 2026-11-13 (owner asked to complete). Panel 400s were SAM.gov rejecting the old bare-UUID key; new `SAM-` key installed locally. Clearer credential errors, fail-fast, key redaction. Owner to-do: set the new `SAM_API_KEY` on Forge |
| `sam-fetch-repair-complete` | Owner closed 2026-10-10 (fetch works). Phase 6 operator visibility/regression suite closed without being built |
| `sam-result-volume-complete` | Investigation only, 2/2 phases; fixes continue in `sam-volume-fixes` |
| `store-toggle-profile-menu-complete` | 1/1 phase |
| `stripe-integration-complete` | 6/6 phases |
| `vendor-preferred-contacts-complete` | 1/1 phase, owner validated |
| `vite-dev-images-and-logo-dark-variant-complete` | 2/2 phases, owner validated |
| `will-call-notifications-complete` | 7/7 phases, owner approved 2026-10-23. Pushed through cf5da60e. Production setup = owner follows `OWNER-TODO.md` §2–5 (Forge, Reverb, VAPID, Twilio later) |

## Remaining

### All built work is waiting on validation (nothing left to build)

| Module | What's left |
|---|---|
| `customer-mfa` | 4 phases awaiting validation; plan row still says Awaiting Plan Approval |
| `store-checkout-redesign` | 6 phases awaiting validation |
| `store-dashboard-switch` | 1 phase awaiting validation |
| `store-home-redesign` | 1 phase awaiting validation |
| `sam-admin-favorites` | Phase 2 verification awaiting validation |

### Nearly done (one item left)

| Module | Done | What's left |
|---|---|---|
| `laravel-13-upgrade` | 5/6 | Phase 6: deployed, public checks pass 2026-10-11; owner checklist (Forge settings, MFA login, checkout) awaiting validation |
| `lifehouse-audit-remediation` | 6/7 | Final verification and closeout awaiting validation |
| `repair-services` | 3/4 | Testing & hardening awaiting validation |
| `sam-psc-discovery` | 2/3 | Control panel, schedule and measurement awaiting validation |
| `user-email-verification` | 4/5 | Phase 5 status-change notifications awaiting validation |

### Partly done

| Module | Done | What's left |
|---|---|---|
| `dibbs-rfq-tracking` | 5/8 | Batch quote export **Blocked**; hardening and approved-source/CAGE report awaiting validation |
| `non-admin-auth-redesign` | 1/3 | Login/register redesign awaiting validation; testing/visual verification not started |
| `organization-location-governance` | 1/5 | Location CRUD backend awaiting validation; 3 phases not started |
| `privacy-compliance` | 1/5 | Consent management awaiting validation; policy pages, DSR workflow, retention/hardening not started |
| `sam-volume-fixes` | 1/3 | Ingestion/display limits awaiting validation; search scope and defaults not started |
| `audit-logging` | 2/4 | Phase 3 admin UI in progress; hardening not started |

### Early (nothing finished)

| Module | What's left |
|---|---|
| `production-observability` | Sentry awaiting validation; automated backups **Blocked**; testing not started |
| `report-builder` | 3/8. Phase 1 complete (8f68d1f8); phase 2 complete (43f33dbf); phase 3 complete (6ff5fc88); phases 4–8 not started |

### Plan awaiting approval (not started)

- `analytics-measurement`
- `mobile-foundation` — cross-repo ([server] + [mobile]); 8 phases; customer app login, required MFA, 12 h session + biometric lock, app passkeys. Depends on `nist-auth-hardening`
- `nist-auth-hardening` — server; 6 phases; authenticator-only MFA (customers + admins), required MFA, breached-password check, 60 min / 12 h session limits, website passkeys. Do before `mobile-foundation`
- `site-navigation-restructure`

## Not a module

- `services`: holds only `2026 Brochure.pdf`.
