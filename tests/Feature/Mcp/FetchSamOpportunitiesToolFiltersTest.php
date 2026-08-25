<?php

declare(strict_types=1);

use App\Mcp\Servers\Business\Tools\FetchSamOpportunitiesTool;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Post-fetch filter fidelity
|--------------------------------------------------------------------------
|
| applyFilters() used to return its input unchanged while the control panel
| still offered PSC and Keyword fields and the state file reported them as
| applied. These tests pin the two properties that matter:
|
|   1. a filter that is offered actually filters, and
|   2. a filter that is not supplied does not narrow anything.
|
| The second is the dangerous direction: PSC, keywords, and set-asides all used
| to inherit large config defaults, which would have silently hidden most
| results the moment filtering started working.
|
*/

beforeEach(function () {
    Config::set('services.sam.api_key', 'test-api-key');
    Config::set('services.sam.base_url', 'https://sam.test/opportunities/v2/search');
});

function fakeCatalogue(): void
{
    Http::fake(['*' => Http::response([
        'totalRecords' => 4,
        'opportunitiesData' => [
            [
                'noticeId' => 'a', 'title' => 'Industrial Fasteners and Bolts',
                'solicitationNumber' => 'SPE-001', 'classificationCode' => '5340',
                'typeOfSetAside' => 'SBA', 'typeOfSetAsideDescription' => 'Total Small Business',
                'postedDate' => '2026-08-04',
            ],
            [
                'noticeId' => 'b', 'title' => 'Medical Stretchers',
                'solicitationNumber' => 'VA-002', 'classificationCode' => '6515',
                'typeOfSetAside' => 'SDVOSBC', 'typeOfSetAsideDescription' => 'Service-Disabled Veteran-Owned',
                'postedDate' => '2026-08-03',
            ],
            [
                'noticeId' => 'c', 'title' => 'Welding Supplies',
                'solicitationNumber' => 'DLA-003', 'classificationCode' => '65',
                'typeOfSetAside' => null, 'typeOfSetAsideDescription' => null,
                'postedDate' => '2026-08-02',
            ],
            [
                'noticeId' => 'd', 'title' => 'Janitorial Cleaning Supplies',
                'solicitationNumber' => 'GSA-004', 'classificationCode' => '7930',
                'typeOfSetAside' => 'WOSB', 'typeOfSetAsideDescription' => 'Women-Owned Small Business',
                'postedDate' => '2026-08-01',
            ],
        ],
    ], 200)]);
}

function fetchWith(array $params): array
{
    return (new FetchSamOpportunitiesTool)->fetch(array_merge([
        'naics_override' => ['423840'],
        'days_back' => 30,
        'limit' => 100,
        'clearCache' => true,
    ], $params));
}

function noticeIds(array $result): array
{
    return array_map(fn ($o) => $o['notice_id'], $result['opportunities']);
}

it('returns everything when no refining filter is supplied', function () {
    fakeCatalogue();

    expect(noticeIds(fetchWith([])))->toHaveCount(4);
});

it('filters by keyword against the title', function () {
    fakeCatalogue();

    expect(noticeIds(fetchWith(['keywords' => 'supplies'])))
        ->toEqualCanonicalizing(['c', 'd']);
});

it('matches keywords case-insensitively and ORs multiple terms', function () {
    fakeCatalogue();

    expect(noticeIds(fetchWith(['keywords' => 'BOLTS, stretchers'])))
        ->toEqualCanonicalizing(['a', 'b']);
});

it('matches keywords against the solicitation number too', function () {
    fakeCatalogue();

    expect(noticeIds(fetchWith(['keywords' => 'DLA-003'])))->toBe(['c']);
});

it('filters by PSC code', function () {
    fakeCatalogue();

    expect(noticeIds(fetchWith(['psc_override' => ['5340']])))->toBe(['a']);
});

it('treats PSC codes as hierarchical prefixes', function () {
    fakeCatalogue();

    // "65" is the family; "6515" is a child. SAM.gov returns both depths for
    // the same query, so a family-level filter must catch the child and a
    // child-level filter must not be dropped by a family-level record.
    expect(noticeIds(fetchWith(['psc_override' => ['65']])))
        ->toEqualCanonicalizing(['b', 'c']);
});

it('filters by set-aside using the machine code, not the description', function () {
    fakeCatalogue();

    expect(noticeIds(fetchWith(['set_asides' => ['SDVOSB']])))->toBe(['b']);
});

it('supports multiple set-asides, which the API parameter cannot', function () {
    fakeCatalogue();

    // typeOfSetAside is single-valued server-side; comma-joining returns zero.
    // Filtering post-fetch is what makes a union possible at all.
    expect(noticeIds(fetchWith(['set_asides' => ['SB', 'WOSB']])))
        ->toEqualCanonicalizing(['a', 'd']);
});

it('keeps full-and-open solicitations when no set-aside filter is asked for', function () {
    fakeCatalogue();

    // Record "c" has no set-aside. It must survive by default — the old config
    // default of six set-aside codes would have excluded it.
    expect(noticeIds(fetchWith([])))->toContain('c');
});

it('applies small_business_only as an SBA set-aside filter', function () {
    fakeCatalogue();

    expect(noticeIds(fetchWith(['small_business_only' => true])))->toBe(['a']);
});

it('combines filters with AND', function () {
    fakeCatalogue();

    expect(noticeIds(fetchWith(['keywords' => 'supplies', 'set_asides' => ['WOSB']])))
        ->toBe(['d']);
});

it('reports the filtered counts it actually applied', function () {
    fakeCatalogue();

    $result = fetchWith(['keywords' => 'supplies']);

    expect($result['summary']['returned'])->toBe(2)
        ->and($result['summary']['dedup_before_filters'])->toBe(4)
        ->and($result['summary']['filtered_out'])->toBe(2)
        ->and($result['query']['keywords'])->toBe(['supplies']);
});

it('does not send set-asides to the API, keeping cached responses reusable', function () {
    fakeCatalogue();

    fetchWith(['set_asides' => ['SBA']]);

    Http::assertSent(function ($request) {
        return ! isset($request['typeOfSetAside']) && ! isset($request['setAsideCode']);
    });
});
