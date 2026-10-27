# Code review and source evidence

Reviewed 2026-10-04; static code inspection, filesystem checks and official web research. No production reproduction, application tests, catalog download or company contact performed.

## Existing flow and findings

- app/Filament/Pages/FetchNsnData.php calls NsnLookupService immediately or dispatches FetchNsnDataJob. Both page and MCP tool use fetchAndPersistNsnData.
- app/Services/NsnLookupService.php invokes scripts/nsn_lookup.py through a 60-second Python process. That file and scripts/requirements.txt do not exist in this checkout; .gitignore excludes scripts/. The missing-script branch returns failure, then fetchAndPersistNsnData returns null. This is a concrete local failure, not a verified diagnosis of every production failure.
- The page treats null as not found, conflating missing runtime/script, timeout, invalid output, invalid NSN and true no-match. Queue null results are logged without throwing, so such failures can appear completed and do not trigger retries. Queued work has no persistent user-visible completion state.
- resources/views/filament/pages/fetch-nsn-data.blade.php advertises NSNDEPOT.COM, NSNLOOKUP.COM and Crawl4AI; no PUB LOG integration is present in the reviewed path. Its FLIS wording is not proof of an official source.
- MilSpecPart stores one manufacturer_id and manufacturer_part_number per NSN. The service overwrites those on update and creates Unknown Manufacturer when identity is absent. Supplier/procurement-history relations exist, but the service does not populate them despite page/tool descriptions promising them.
- normalizeCageCode removes lowercase letters before uppercasing. Lowercase CAGE input can become invalid.
- NSN validation removes all nondigits rather than validating supported formats. UI actions only check empty input; the new contract needs shared validation.
- Manufacturer/Supplier/MilSpecPart have no explicit tenant ownership in reviewed models. Vendor already has VendorContact support; a future explicit association can reuse verified contact data without merging unrelated domain models.
- No NSN-specific tests were found by searching tests for NSN, Nsn, MilSpecPart and Manufacturer. Add regression coverage as implementation phases proceed.
- Existing uncommitted SAM/category/CRM/test changes predate this task and must be preserved.

## Official sources

1. [DLA PUB LOG](https://www.dla.mil/Information-Operations/Services/Applications/PUB-LOG/ProceduresManuals/): public downloadable logistics data, monthly updates, NSN/reference/CAGE cross-references, and excluded restricted data. Supports choosing PUB LOG as the catalog source, not current production evidence.
2. [DLA FLIS reading room](https://www.dla.mil/Information-Operations/FLIS-Data-Electronic-Reading-Room/PUBLOGFLISSearchDownloadInstr/): indexed official content lists CSV ZIP products and layouts for Identification, Reference and CAGE. Reference describes CAGE/part-number relationships. It also documents 2026 field removals. Direct page fetch returned 403 here; actual archives/layouts remain to validate in phase 1.
3. [DLA CAGE program description](https://www.dla.mil/Working-With-DLA/Applications/Details/Article/2920893/cage-code-commercial-and-government-entity-code/): identifies CAGE Search and Inquiry as the company-code search tool.
4. [CAGE Search](https://cage.dla.mil/Search): redirected to Home/UsageAgree with cookie/usage requirements on inspection. No supported programmatic lookup interface was verified.

Design inference: indexed local PUB LOG queries plus explicit CAGE verification and dated sales evidence satisfy the requested research chain while separating catalog facts from business confirmation.
