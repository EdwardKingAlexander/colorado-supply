<?php

declare(strict_types=1);

use App\Jobs\FetchSamOpportunitiesJob;
use App\Services\SamApiClient;
use App\Support\SamOpportunitiesCache;
use App\Support\SamParameterResolver;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('services.sam.base_url', 'https://sam.test/opportunities/v2/search');
    Http::preventStrayRequests();
});

describe('resolver: psc_search', function () {
    test('accepts 2-digit DLA groups and 4-character PSCs, normalized and deduplicated', function () {
        $resolved = (new SamParameterResolver)->resolve(['psc_search' => ['53', ' j053 ', '5305', '53', '']]);

        expect($resolved['psc_codes'])->toBe(['53', 'J053', '5305']);
    });

    test('rejects lengths SAM.gov never matches', function (string $code) {
        (new SamParameterResolver)->resolve(['psc_search' => [$code]]);
    })->with(['530', '5', '53051', '5-30'])->throws(InvalidArgumentException::class);

    test('rejects a non-array psc_search', function () {
        (new SamParameterResolver)->resolve(['psc_search' => '53']);
    })->throws(InvalidArgumentException::class);

    test('prefers psc_search over the deprecated psc_override alias', function () {
        $resolved = (new SamParameterResolver)->resolve(['psc_search' => ['59'], 'psc_override' => ['53']]);

        expect($resolved['psc_codes'])->toBe(['59']);
    });

    test('ships a configured default group list the panel can offer', function () {
        expect(config('sam_opportunities.psc_groups'))->toContain('53', '59', '48')
            ->and(config('sam_opportunities.psc_groups'))->not->toContain('43', '25')
            ->and(config('sam_opportunities.psc_groups_optional'))->toBe(['43', '25']);
    });
});

describe('client: dimension-aware requests', function () {
    test('a PSC term sends ccode and never ncode, and is tagged as a psc term', function () {
        Http::fake(['*' => Http::response(['totalRecords' => 1, 'opportunitiesData' => [
            ['noticeId' => 'n1', 'title' => '53--BOLT,MACHINE', 'classificationCode' => '53'],
        ]])]);

        $result = (new SamApiClient)->fetch('53', samTestParams(), 'key', 'psc');

        Http::assertSent(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['ccode'] ?? null) === '53' && ! array_key_exists('ncode', $query);
        });

        expect($result)
            ->toMatchArray(['success' => true, 'dimension' => 'psc', 'code' => '53', 'term' => 'psc:53', 'psc' => '53'])
            ->not->toHaveKey('naics');
    });

    test('a NAICS term still sends ncode and keeps its naics key', function () {
        Http::fake(['*' => Http::response(['totalRecords' => 0, 'opportunitiesData' => []])]);

        $result = (new SamApiClient)->fetch('332722', samTestParams(), 'key');

        Http::assertSent(function (Request $request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return ($query['ncode'] ?? null) === '332722' && ! array_key_exists('ccode', $query);
        });

        expect($result)->toMatchArray(['naics' => '332722', 'term' => 'naics:332722', 'dimension' => 'naics']);
    });
});

describe('cache: dimension-aware keys', function () {
    test('a PSC and a NAICS entry with the same code never collide', function () {
        Cache::flush();
        $cache = new SamOpportunitiesCache;
        $params = samTestParams();

        $cache->put('5305', $params, ['from' => 'psc'], 'psc');

        expect($cache->get('5305', $params, 'psc'))->toMatchArray(['from' => 'psc'])
            ->and($cache->get('5305', $params))->toBeNull();
    });
});

test('the fetch job allows enough time for NAICS + PSC discovery', function () {
    expect((new FetchSamOpportunitiesJob)->timeout)->toBe(600);
});

function samTestParams(): array
{
    return (new SamParameterResolver)->resolve(['naics_override' => ['332722'], 'days_back' => 7]);
}
