# Phase 3 - Replace the scraper lookup with clear catalog results

- Lifecycle: BLOCKED (verification) - implementation authorized 2026-10-09; PHP runtime access denied.
- Objective: Replace the scraper lookup with clear catalog results.
- Dependencies: Local phase-2 import and fixture validation complete. User explicitly deferred production storage/retention gates and authorized phase 3 on 2026-10-09.
- Entry criteria: Working catalog fixture/import and approved result contract.

## Scoped tasks

- [ ] Implement explicit input type selection for NSN, NIIN, part number or CAGE with shared strict validation and conservative part-number matching. Paginate ambiguous results and preserve all references.
- [ ] Replace NsnLookupService Python path with local catalog queries and structured outcomes: invalid input, catalog unavailable, no match, match and operational failure; stale is a separate data-quality flag.
- [ ] Update Filament page/view to display item, every part/CAGE relationship, source release and company snapshot, with Open CAGE Search/copy-code action. Replace misleading FLIS/supplier/history claims.
- [ ] Display source-code validity explicitly. Nonstandard imported CAGE values remain visible as source anomalies; do not present them as valid CAGE identities or enable the normal CAGE Search handoff. User-entered CAGE validation remains strict. Honor model code_valid / cage_code_valid fields and import warning counts.
- [ ] Update queue/MCP contracts and every caller together. Local lookup should be immediate; retain legacy queued entry point if needed with persisted request ID/status/owner and correct retries for operational errors.
- [ ] Separate search from explicit save/link to a curated MilSpecPart. Do not auto-create Unknown Manufacturer or overwrite existing selections. Define compatibility for legacy MCP response fields and existing records.

## Files to create or modify

Modify app/Services/NsnLookupService.php, app/Filament/Pages/FetchNsnData.php, resources/views/filament/pages/fetch-nsn-data.blade.php, app/Jobs/FetchNsnDataJob.php, app/Mcp/Servers/Business/Tools/FetchNsnDataTool.php, relevant MilSpecParts schemas/tables; add result/request objects and policies as needed.

## Verification

Service, Livewire, job and MCP tests: invalid formats, lowercase CAGE, leading zeros, NSN/NIIN equivalence, ambiguous part numbers, multi-reference pagination, no catalog/no match/failure distinction, stale results, explicit save preservation, request ownership and retry semantics. Run full suite and dirty formatter; visually verify desktop/mobile UI.

## Exit criteria

Every entry point uses the same verified catalog contract, no Python runtime needed, errors are truthful, existing curated data is preserved, and verification evidence plus user validation recorded.

## Risks

Legacy MCP consumers and mandatory manufacturer foreign key require deliberate compatibility handling.

## Out of scope

CAGE automation, supplier confirmation, procurement-history import and unrelated visual redesign.

STOP. Await phase approval.

Implementation refinement: retire queue UI and fail old serialized scraper jobs explicitly; no new asynchronous request workflow is needed for immediate indexed searches. Curated save uses the existing create form with server-validated catalog-reference prefill, explicit manufacturer selection, and existing-record preservation. No auto-save or inferred manufacturer identity.


Implementation written for scoped tasks; checklists remain open until runtime tests and visual verification pass. See phase-3-verification.md. No phase completion claimed.

