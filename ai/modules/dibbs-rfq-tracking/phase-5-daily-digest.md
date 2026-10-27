---
plan: dibbs-rfq-tracking
phase: 5
title: Daily digest email
status: completed
last_updated: 2026-10-04
depends_on: [phase-4]
checkpoint: 5.2-delivery
---

# Phase 5 — Daily digest email

## Goal

Each business morning, email the operator(s) the new RFQs that matter, so
nothing in our lane closes unseen.

## Sub-phases

### 5.1 Digest content
Steps:
1. Query: RFQs first seen in the latest imported file, with a match (catalog,
   approved source, or FSC per settings), not closed, not marked no-bid.
2. Sections: catalog matches, approved-source matches, FSC matches (capped,
   with "N more" link), plus "closing in ≤3 days and still `new`/`reviewing`".
3. Each row: NSN, nomenclature, qty, return-by, set-aside, link to the admin
   RFQ page and to DIBBS.
DoD: notification test renders expected sections from fixtures.

### 5.2 Delivery and settings
Steps:
1. `DibbsDailyDigestNotification` (mail), sent after the import job succeeds
   (chained), or at a fixed time if no import (with "no new file" note).
2. Recipients and minimum match level in `config/dibbs.php` (env-driven);
   empty digest is not sent unless "closing soon" has items.
DoD: tests for send/no-send rules and chaining.

## Files

Create: notification + mail view. Modify: `DibbsDownloadJob`/importer chain,
`config/dibbs.php`.

## Tests

`tests/Feature/Dibbs/DibbsDailyDigestTest.php`. Full suite + pint.

## Success criteria

- A morning digest arrives after a real import (verified in production or
  staging mail; local Mailgun failures are expected per project memory).

## Risks / blockers

- Mail deliverability depends on the production Mailgun domain being active.

STOP. Await phase approval.
