# Site Navigation Restructure — State

Status values: `Not Started`, `Awaiting Plan Approval`, `Awaiting Phase Approval`,
`In Progress`, `Blocked`, `Awaiting Validation`, `Complete`.

| # | Phase | Status | Last Updated | Notes |
|---|-------|--------|--------------|-------|
| - | Module plan (`00-overview.md`) | Awaiting Plan Approval | 2026-07-03 | Drafted from user request + follow-up Q&A |
| 1 | Dedicated pages for About, Capabilities, Contact | Not Started | 2026-07-03 | |
| 2 | Homepage teaser consolidation (`SectionTeaser.vue`) | Not Started | 2026-07-03 | |
| 3 | Navigation & Footer routing + Store link | Not Started | 2026-07-03 | |
| 4 | Admin sidebar store-availability toggle | Not Started | 2026-07-03 | |
| 5 | Testing & hardening | Not Started | 2026-07-03 | |

## Log

- 2026-07-03 — Plan drafted. Key decisions from user Q&A:
  - Homepage becomes a landing page with short teasers linking to full
    dedicated pages (not full-content duplication) — user asked for my
    recommendation, which was to avoid duplicate content for SEO/maintenance
    reasons and to fix the existing cross-page anchor-link bug.
  - "Store" nav link should be visible to **all** visitors, not just
    authenticated ones; logged-out clicks redirect to `/login` (existing
    `auth.web_or_admin` middleware behavior, confirmed, no changes needed).
  - The store on/off toggle currently lives at Filament admin → Automation
    → MCP Control Panel (`McpDashboard`'s `toggleStore` header action) —
    the user found this non-obvious and wants it relocated to an
    always-visible switch in the admin panel's own sidebar navigation
    (Phase 4), removing the old header action once the new switch lands.
  - Confirmed via `vendor/filament/filament/src/View/PanelsRenderHook.php`
    that `SIDEBAR_FOOTER` is the right render hook for a persistent sidebar
    control in the installed Filament version.
  - This module is independent of the in-progress `repair-services` module
    (all 4 phases implemented, awaiting final sign-off + a pending
    Gemini-CLI-informed hero-copy revision) — no phase here touches
    `repair-services` files except linking *to* `/repair-services` from the
    homepage teaser and nav.
  - Awaiting orchestrator approval of the overall plan before Phase 1
    begins.
