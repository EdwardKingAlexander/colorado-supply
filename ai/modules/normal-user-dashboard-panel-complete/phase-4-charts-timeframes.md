# Phase 4 - Charts and Interactive Time-Frame Filtering

## Goal

Add interactive spending charts and robust time-frame filtering so users can
analyze purchases over any supported period.

## Scope

- Add chart components.
- Add date range controls.
- Add location/sublocation filters.
- Use Inertia partial reloads or query-string visits for filter changes.
- Keep all aggregation server-side.

## Files to Create

- `resources/js/Components/Dashboard/DateRangeControl.vue`
- `resources/js/Components/Dashboard/LocationFilter.vue`
- `resources/js/Components/Dashboard/SpendOverTimeChart.vue`
- `resources/js/Components/Dashboard/SpendByLocationChart.vue`
- `resources/js/Components/Dashboard/PaymentStatusChart.vue`
- `tests/Feature/Dashboard/DashboardFilterTest.php`

## Files to Modify

- `resources/js/Pages/Dashboard.vue`
- `app/Services/Dashboard/UserDashboardDataService.php`
- `app/Http/Requests/Dashboard/DashboardFilterRequest.php`
- `tests/Feature/Dashboard/UserDashboardDataTest.php`

## Charting Approach

Use the existing frontend stack and add the smallest reasonable charting
dependency only if no charting library already exists. Candidate libraries
should be evaluated during implementation, but the preferred approach is:

- Reuse an existing dependency if already installed.
- Otherwise use a Vue-friendly chart library with good accessibility and small
  integration surface.
- Keep chart input data simple arrays from the backend.

## Charts

1. Spend over time
   - Grouping:
     - Daily for short ranges.
     - Weekly or monthly for longer ranges.
   - Shows total spend per bucket.

2. Spend by location
   - Parent-location rollup.
   - Shows "Unassigned" if applicable.

3. Spend by sublocation
   - Enabled after a parent location is selected or when data has child
     locations.

4. Payment status breakdown
   - Paid, unpaid, pending, failed, refunded.

## Filter Behavior

- Query string is the source of truth.
- Changing range/location updates dashboard props through Inertia.
- Custom date range validates both dates.
- Invalid filter combinations fall back safely with validation errors or a
  clear empty state.

## Tests to Write

- Date range filters change aggregation results.
- Custom date validation rejects invalid ranges.
- Location filter excludes other locations.
- Sublocation filter only includes matching child location data.
- Spend-over-time bucket output is stable for day/month boundaries.
- Cross-company location IDs are rejected or ignored.

## Success Criteria

- Users can choose time frames and see dashboard data update.
- Charts render from backend data.
- Location and sublocation charts are accurate.
- Filter state is shareable/bookmarkable through the URL.
- Targeted backend tests pass.
- Frontend build passes.
- `vendor/bin/pint --dirty` passes.

**STOP. Await phase approval before implementation.**
