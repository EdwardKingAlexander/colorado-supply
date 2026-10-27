<?php

declare(strict_types=1);

use App\Mcp\Servers\Business\Tools\FetchSamOpportunitiesTool;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

/*
| NAICS OR PSC discovery end to end through the tool (ai/modules/sam-psc-discovery).
| The fake answers per query parameter: ncode=332722 finds a and b; ccode=53
| finds b and c. So c is reachable only through PSC search, and b through both.
*/

beforeEach(function () {
    Config::set('services.sam.api_key', 'test-api-key');
    Config::set('services.sam.base_url', 'https://sam.test/opportunities/v2/search');
    Cache::flush();
    Http::preventStrayRequests();
});

function fakeSamByTerm(array $byTerm): void
{
    Http::fake(function (Request $request) use ($byTerm) {
        parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
        $term = isset($query['ccode']) ? 'psc:'.$query['ccode'] : 'naics:'.($query['ncode'] ?? '');
        $answer = $byTerm[$term] ?? [];

        if (is_int($answer)) {
            return Http::response(['error' => 'status '.$answer], $answer);
        }

        return Http::response([
            'totalRecords' => count($answer),
            'opportunitiesData' => array_map(fn (string $id) => [
                'noticeId' => $id,
                'title' => "Notice {$id}",
                'classificationCode' => '53',
                'postedDate' => '2026-10-01',
            ], $answer),
        ]);
    });
}

function discover(array $params): array
{
    return (new FetchSamOpportunitiesTool)->fetch(array_merge([
        'naics_override' => ['332722'],
        'days_back' => 14,
        'limit' => 100,
        'clearCache' => true,
    ], $params));
}

function byNotice(array $result): array
{
    return collect($result['opportunities'])->keyBy('notice_id')->all();
}

it('finds notices that only a PSC query returns, once each, with every term that found them', function () {
    fakeSamByTerm(['naics:332722' => ['a', 'b'], 'psc:53' => ['b', 'c']]);

    $result = discover(['psc_search' => ['53']]);
    $notices = byNotice($result);

    expect(array_keys($notices))->toEqualCanonicalizing(['a', 'b', 'c'])
        ->and($notices['a']['matched_terms'])->toBe(['naics:332722'])
        ->and($notices['b']['matched_terms'])->toEqualCanonicalizing(['naics:332722', 'psc:53'])
        ->and($notices['c']['matched_terms'])->toBe(['psc:53'])
        ->and($result['summary']['found_only_via_psc'])->toBe(1)
        ->and($result['summary']['successful_psc_count'])->toBe(1)
        ->and($result['success'])->toBeTrue();
});

it('stays NAICS-only when no PSC codes are supplied', function () {
    fakeSamByTerm(['naics:332722' => ['a', 'b'], 'psc:53' => ['b', 'c']]);

    $result = discover([]);

    expect(array_keys(byNotice($result)))->toEqualCanonicalizing(['a', 'b'])
        ->and($result['summary']['found_only_via_psc'])->toBe(0);

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'ccode='));
});

it('keeps the rest of the run when one PSC term fails', function () {
    fakeSamByTerm(['naics:332722' => ['a'], 'psc:53' => ['c'], 'psc:59' => 500]);

    $result = discover(['psc_search' => ['53', '59']]);

    expect(array_keys(byNotice($result)))->toEqualCanonicalizing(['a', 'c'])
        ->and($result['partial_success'])->toBeTrue()
        ->and($result['summary']['failed_psc_count'])->toBe(1)
        ->and(collect($result['summary']['failed_naics'] ?? [])->pluck('naics')->all())->toContain('psc:59');
});

it('reports success when only PSC terms return anything', function () {
    // Status used to be judged from NAICS counts alone.
    fakeSamByTerm(['naics:332722' => [], 'psc:53' => ['c']]);

    $result = discover(['psc_search' => ['53']]);

    expect(array_keys(byNotice($result)))->toBe(['c'])
        ->and($result['success'])->toBeTrue()
        ->and($result['summary']['found_only_via_psc'])->toBe(1);
});
