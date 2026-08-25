---
plan: sam-fetch-repair
phase: 5
title: Filter fidelity
status: not_started
last_updated: 2026-08-24
depends_on: [phase-2, phase-3]
checkpoint: none
---

# Phase 5 — Filter Fidelity

## Goal

Make every filter the UI offers either actually work or disappear. Right now
two of them are decorative, and the "no results" fallback path cannot do what
its name says. Addresses L5 and L6.

## Background

### L5 — PSC and keyword filters are a no-op

```php
protected function applyFilters(array $opportunities, array $params): array
{
    // PSC and keyword filtering disabled per request—return deduped results as-is.
    return $opportunities;
}
```

Yet the control panel still presents **PSC Codes** and **Keywords** inputs,
`SamParameterResolver` still resolves and validates both, and
`toLegacyFormat()` still echoes them into `query.psc_codes` and
`query.keywords` in the state file — so the UI reports filters that were never
applied. An operator entering keywords gets unfiltered results that *claim* to
be filtered.

The comment says "disabled per request", so this may have been deliberate.
**Confirm the intent with the user before choosing a direction** — this is the
one open product question in the module.

### L6 — The fallback branch cannot work

```php
$fallbackParams['naics_override'] = []; // No NAICS
```

`resolveNaicsCodes()` tests `isset(...) && ! empty($params['naics_override'])`.
Since `empty([])` is `true`, the override is ignored and **all default NAICS
codes are used** — the opposite of the comment's intent. The fallback re-runs
substantially the same query it just failed.

It is also gated on `! empty($resolved['keywords'])`, and keywords default to
the full config list (~40 entries) when the user supplies none. So the fallback
fires on any empty primary result even when the user never asked for a keyword
search — and then, because of L5, the "keyword-only" search applies no keyword
filter at all.

## Sub-phases

### 5.1 — Decide the filter model — `checkpoint: filter-decision`

Ask the user which they want, then implement exactly one:

- **Option A — make them real.** Implement PSC and keyword filtering in
  `applyFilters()`: PSC as an exact-match set against `psc_code`; keywords as a
  case-insensitive substring match across `title` (and `description` once
  Phase 4.4 populates it). Server-side keyword filtering via SAM's `title`
  parameter is also possible — prefer post-fetch filtering so it composes with
  the per-NAICS cache.
- **Option B — remove them.** Delete the PSC and Keywords fields from the
  panel, drop them from `toLegacyFormat()`'s query echo, and strip the
  resolver's now-unused paths. Honest and smaller.

Recommendation: **A for keywords** (operators expect it and it is cheap
post-fetch), **B for PSC** unless the user actively uses PSC codes — the config
carries 30 of them and the panel field defaults to empty, suggesting it is
unused.

### 5.2 — Implement the decision — `checkpoint: filters-implemented`

Whichever option is chosen, the invariant is: **what the state file reports as
applied must equal what was actually applied.** If a filter is not applied, it
must not appear in `query.*`.

If Option A:
- Filter after deduplication, before sort/limit (the current call site is
  already correct).
- Match keywords case-insensitively; treat the keyword list as OR.
- Record `total_after_filters` accurately — `toLegacyFormat()` already has the
  plumbing for `filtered_out`, which is currently always 0.

### 5.3 — Fix or remove the fallback — `checkpoint: fallback-fixed`

The `search_logic.fallback` config key is `nationwide_keyword_only`. Make the
code match the config, or drop both.

If keeping it:
- Pass an explicit sentinel for "no NAICS filter" that survives
  `resolveNaicsCodes()` — `empty([])` cannot express it. Add a distinct flag
  (e.g. `naics_none => true`) rather than overloading the override array.
- Gate on **user-supplied** keywords, not resolved defaults. Distinguish
  "user passed keywords" from "config defaults filled them in" — the resolver
  currently erases that distinction, so it needs to expose it.
- Guard the recursion: `$isFallback` already prevents infinite depth; add a log
  line recording that the fallback fired and what it changed.

If dropping it: remove the branch and the `fallback` config key together.

### 5.4 — Align config defaults — `checkpoint: config-alignment`

`config/sam_opportunities.php` declares `search_logic.order`,
`output_requirements`, and `error_handling` blocks that **no code reads**.
Either wire them up or delete them — leaving them implies behaviour that does
not exist. Verify by grep before removing.

## Files

**Modify**
- `app/Mcp/Servers/Business/Tools/FetchSamOpportunitiesTool.php` (`applyFilters`, fallback)
- `app/Support/SamParameterResolver.php` (user-vs-default keyword provenance)
- `app/Filament/Pages/FetchSamControlPanel.php` (field removal, if Option B)
- `config/sam_opportunities.php` (dead keys)

**Tests**
- `tests/Feature/Mcp/FetchSamOpportunitiesToolFiltersTest.php` (new)
- `tests/Feature/Services/SamParameterResolverTest.php` (extend)

## Tests

- Keyword filter: given 3 opportunities, only titles matching the keyword
  survive; matching is case-insensitive.
- PSC filter (if kept): only listed PSC codes survive.
- `filtered_out` and `total_after_dedup` in the state file reflect the real
  post-filter counts.
- **Reported-equals-applied:** if a filter is not implemented, it does not
  appear in the state file's `query` block.
- Fallback fires only when the user explicitly supplied keywords.
- Fallback actually broadens the query (assert the second request differs from
  the first — the current bug is that it does not).
- Fallback recurses at most once.

## Success Criteria

1. Every filter in the UI demonstrably changes results, or is gone.
2. The state file never claims a filter that was not applied.
3. The fallback either broadens the search or no longer exists.
4. No unread config keys remain in `config/sam_opportunities.php`.
5. `php artisan test` green; `vendor/bin/pint --dirty` clean.

## Risks / Blockers

- **Open product question in 5.1** — needs the user's answer before coding.
  "Disabled per request" suggests filtering was intentionally turned off, so do
  not silently re-enable it.
- Re-enabling keyword filtering with the ~40 config defaults would filter
  aggressively by default and could look like results vanished. If Option A is
  chosen, default to **no keywords** unless the user supplies them.
- Depends on Phase 2 (NAICS filtering real) and Phase 3 (empty results reported
  honestly); filtering changes are hard to interpret without both.

STOP. Await phase approval.
