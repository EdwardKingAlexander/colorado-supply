<?php

declare(strict_types=1);

use App\Services\SamApiClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
|
| limit caps at 1000 and offset was never sent, so any NAICS matching more than
| 1000 records silently lost the remainder. totalRecords was parsed and thrown
| away rather than used to page.
|
*/

beforeEach(function () {
    Config::set('services.sam.base_url', 'https://sam.test/opportunities/v2/search');
    Config::set('services.sam.max_pages', 10);
});

/** Build a page of $count synthetic records, unique across the whole set. */
function samPage(int $count, int $startIndex, int $totalRecords): array
{
    $data = [];

    for ($i = 0; $i < $count; $i++) {
        $n = $startIndex + $i;
        $data[] = ['noticeId' => 'notice-'.$n, 'title' => 'Opportunity '.$n];
    }

    return ['totalRecords' => $totalRecords, 'opportunitiesData' => $data];
}

function paginationParams(): array
{
    return [
        'posted_from' => '08/01/2026',
        'posted_to' => '08/24/2026',
        'notice_type_codes' => ['o'],
        'place' => null,
        'set_aside_codes' => [],
    ];
}

it('pages through a result set larger than one page', function () {
    Http::fakeSequence()
        ->push(samPage(1000, 0, 2500), 200)
        ->push(samPage(1000, 1000, 2500), 200)
        ->push(samPage(500, 2000, 2500), 200);

    $result = (new SamApiClient)->fetch('423840', paginationParams(), 'key');

    expect($result['success'])->toBeTrue()
        ->and($result['count'])->toBe(2500)
        ->and($result['pages_fetched'])->toBe(3)
        ->and($result['truncated'])->toBeFalse();

    $offsets = [];
    Http::assertSent(function ($request) use (&$offsets) {
        $offsets[] = (int) $request['offset'];

        return true;
    });

    expect($offsets)->toBe([0, 1000, 2000]);
});

it('does not make a second request when the first page covers everything', function () {
    Http::fake(['*' => Http::response(samPage(12, 0, 12), 200)]);

    $result = (new SamApiClient)->fetch('423840', paginationParams(), 'key');

    expect($result['count'])->toBe(12)
        ->and($result['pages_fetched'])->toBe(1);

    Http::assertSentCount(1);
});

it('stops at the page cap and flags the result as truncated', function () {
    Config::set('services.sam.max_pages', 2);

    Http::fake(['*' => Http::response(samPage(1000, 0, 50000), 200)]);

    $result = (new SamApiClient)->fetch('423840', paginationParams(), 'key');

    expect($result['success'])->toBeTrue()
        ->and($result['pages_fetched'])->toBe(2)
        ->and($result['truncated'])->toBeTrue();

    Http::assertSentCount(2);
});

it('keeps earlier pages when a later page fails', function () {
    Http::fakeSequence()
        ->push(samPage(1000, 0, 2500), 200)
        ->push('Internal Server Error', 500);

    $result = (new SamApiClient)->fetch('423840', paginationParams(), 'key');

    // Page one's records must survive the failure of page two.
    expect($result['success'])->toBeTrue()
        ->and($result['count'])->toBe(1000)
        ->and($result['truncated'])->toBeTrue()
        ->and($result['page_error'])->not->toBeNull();
});

it('fails outright when the very first page fails', function () {
    Http::fake(['*' => Http::response('Internal Server Error', 500)]);

    $result = (new SamApiClient)->fetch('423840', paginationParams(), 'key');

    expect($result['success'])->toBeFalse()
        ->and($result['status_code'])->toBe(500);
});

it('stops when a page returns no records even if totalRecords disagrees', function () {
    // Guards against an infinite loop if SAM.gov reports a total it cannot serve.
    Http::fakeSequence()
        ->push(samPage(1000, 0, 9999), 200)
        ->push(samPage(0, 0, 9999), 200);

    $result = (new SamApiClient)->fetch('423840', paginationParams(), 'key');

    expect($result['count'])->toBe(1000)
        ->and($result['pages_fetched'])->toBe(2);

    Http::assertSentCount(2);
});
