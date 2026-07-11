# Phase 3 — Dashboard Location Management and History UI

## Goal

Give eligible normal users a clear, accessible dashboard interface for managing
their location hierarchy and reviewing their organization's immutable change
history.

## Scope

- Add a Locations entry to normal-user dashboard navigation.
- Add a location-management page showing roots and nested sublocations.
- Add create/edit/move/archive/restore forms and confirmation dialogs.
- Display permission/domain readiness states instead of showing actions that
  will fail.
- Add a paginated organization history page or integrated history panel.
- Show actor, timestamp, action, affected record, and a human-readable
  before/after diff.
- Keep history read-only for all company members.
- Do not expose unrelated global admin activities.

## Expected Files to Create

- `resources/js/Pages/Dashboard/Locations/Index.vue`
- `resources/js/Pages/Dashboard/OrganizationHistory.vue`
- `resources/js/Components/Locations/LocationTree.vue`
- `resources/js/Components/Locations/LocationForm.vue`
- `resources/js/Components/Locations/ArchiveLocationDialog.vue`
- `resources/js/Components/Locations/LocationPermissionState.vue`
- `resources/js/Components/Organizations/ActivityHistoryTable.vue`
- `tests/Feature/Dashboard/LocationManagementPageTest.php`
- `tests/Feature/Dashboard/OrganizationHistoryPageTest.php`
- Playwright coverage for the primary CRUD/history journey if browser tests are
  operational

## Expected Files to Modify

- `resources/js/Layouts/AuthenticatedLayout.vue`
- `resources/js/Pages/Dashboard.vue` for a concise management entry point
- Dashboard controller/data service only if shared account state is the cleanest
  way to expose domain/access readiness
- `routes/web.php` if Phase 2 left page routes pending

## UX Requirements

- Clearly distinguish locations from sublocations.
- Provide an explicit parent selector and show the resulting hierarchy before
  save.
- Do not expose company selectors to normal users.
- Archive confirmations explain effects on children and historical reports.
- Locked users retain view/history access but see who/when locked access and how
  to contact/request a supervisor decision.
- Companies awaiting domain setup receive a clear administrator-contact state.
- Empty companies receive a useful first-location call to action.
- Mutation success/error feedback is keyboard accessible and screen-reader
  announced.
- History diffs use labels such as “Name: Denver → Aurora” instead of raw JSON.

## Tests to Write

- Eligible member sees create/edit/archive controls.
- Locked or domain-ineligible member sees read-only state and cannot recover
  controls through crafted requests.
- Root and child records render in the correct hierarchy.
- Forms submit only allowlisted fields and preserve validation errors.
- Archive/restore UI reflects backend outcomes.
- Every company member can see only their organization history.
- Diff renderer escapes user-controlled values and handles create/delete/null.
- Mobile and keyboard navigation remain usable.

## Success Criteria

- Normal users can complete location CRUD from their dashboard without admin
  assistance when eligible.
- The organization history answers who changed what and when.
- Read-only/locked/domain-setup states are understandable and secure.
- Targeted PHP/browser tests, `npm run build`, `php artisan test`, and
  `vendor/bin/pint --dirty` pass.

**STOP. Await phase approval before implementation.**
