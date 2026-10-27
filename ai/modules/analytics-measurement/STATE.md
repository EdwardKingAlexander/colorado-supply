# Analytics Measurement and Observability — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|---|---|---|---|
| - | Module plan (`00-overview.md`) | Awaiting Plan Approval | 2026-07-10 | Four-part free-first plan drafted; no implementation started |
| 1 | Measurement foundation and Search Console | Not Started | 2026-07-10 | Requires owner Search Console verification during validation |
| 2 | GA4 ecommerce and lead events | Not Started | 2026-07-10 | Builds on existing privacy consent controls |
| 3 | First-party business analytics | Not Started | 2026-07-10 | Reuses domain records; customer dashboard already complete |
| 4 | Sentry activation and hardening | Not Started | 2026-07-10 | SDK/scrubber already implemented; needs free project DSN for live validation |

## Log

- 2026-07-10 — Plan drafted after the orchestrator requested a plan for GA4,
  Search Console, first-party reporting, and Sentry, provided each has a free
  starting point.
  - Confirmed GA4 is already bootstrapped behind Consent Mode v2 and the active
    privacy module's analytics category/GPC handling.
  - Confirmed no Search Console verification or public application sitemap is
    currently implemented.
  - Confirmed the normal-user purchasing dashboard and reporting module is
    already complete; this plan therefore scopes first-party analytics to an
    owner/admin business view rather than rebuilding the customer dashboard.
  - Confirmed Sentry Laravel, configuration, exception wiring, scrubber, and
    in-memory integration tests already exist under the production-observability
    module. Phase 4 is activation and hardening only.
  - Verified current official provider positioning: standard Google Analytics
    and Search Console start free; Sentry's Developer plan is $0 with one user
    and published usage quotas. Paid upgrades, pay-as-you-go, advertising tags,
    and session replay remain out of scope.
  - The worktree contains substantial uncommitted changes from other approved
    modules. This planning turn added only `ai/modules/analytics-measurement/`.
  - STOP. Await orchestrator approval of the overall plan.
