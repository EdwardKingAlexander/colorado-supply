# Phase 1 - Baseline and Issue Ledger

## Goal

Convert the screenshot-only audit into a reproducible, prioritized issue ledger
before changing application code.

## Scope

- Run a clean Lighthouse audit against the current target using a browser
  profile without extensions.
- Record exact baseline scores, URLs, failing audit IDs, and which findings are
  actionable.
- Separate app-owned issues from third-party or environment-only noise.
- Update `STATE.md` with a finding-by-finding ledger.

## Files to Create/Modify

- `ai/modules/lifehouse-audit-remediation/STATE.md`
  - Add baseline run date, target URL, scores, and issue status updates.
- Optional only if useful:
  - `ai/modules/lifehouse-audit-remediation/artifacts/`
    - Store exported Lighthouse JSON/HTML reports for comparison.

No production code changes in this phase.

## Issues to Confirm

- Missing cache lifetimes on first-party assets.
- Oversized/unoptimized JPGs:
  `construction-site.jpg`, `manufacturing.jpg`, `grinding.jpg`,
  `food-processing.jpg`, `pipeline.jpg`, `energy.jpg`, `automotive.jpg`,
  `aerospace.jpg`.
- Remote Unsplash images in `Compliance.vue`.
- Render-blocking Google Fonts CSS.
- Font-display/preconnect setup.
- LCP resource priority for hero imagery.
- Explicit width/height on logo and content images.
- Low contrast hero/footer/button text.
- Redundant logo alt text.
- Missing `/site.webmanifest`.
- Missing CSP, HSTS, COOP, and Trusted Types directive.
- reCAPTCHA third-party cookies and Chrome Issues panel entries.
- Chrome extension long-task/unused-JS noise.

## Tests / Verification

- Save or record clean Lighthouse results for desktop and mobile if feasible.
- Verify the Chrome extension findings disappear in the clean profile.
- Confirm the audited route maps to `resources/js/Pages/Welcome.vue`.

## Success Criteria

- `STATE.md` contains a baseline issue ledger with ownership and phase mapping.
- Non-actionable noise is explicitly labeled.
- No production code, assets, or config files are changed.

**STOP. Await phase approval.**
