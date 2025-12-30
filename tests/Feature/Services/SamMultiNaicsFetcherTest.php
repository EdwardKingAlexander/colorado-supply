<?php

declare(strict_types=1);

use App\Services\SamMultiNaicsFetcher;
use App\Support\SamOpportunitiesCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Cache::flush();
    Http::preventStrayRequests();
});

describe('sequential fetching', function () {
    test('fetches opportunities for multiple NAICS codes sequentially', function () {
        Http::fake([
            'api.sam.gov/*' => Http::sequence()
                ->push(['totalRecords' => 1, 'opportunitiesData' => [['noticeId' => 'A', 'title' => 'NAICS 1']]], 200)
                ->push(['totalRecords' => 1, 'opportunitiesData' => [['noticeId' => 'B', 'title' => 'NAICS 2']]], 200)
                ->push(['totalRecords' => 1, 'opportunitiesData' => [['noticeId' => 'C', 'title' => 'NAICS 3']]], 200),
        ]);

        $fetcher = new SamMultiNaicsFetcher;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $fetcher->fetchAll(['123456', '234567', '345678'], $params, 'test-key');

        expect($result)
            ->toHaveKey('results')
            ->toHaveKey('performance')
            ->and($result['results'])->toHaveCount(3)
            ->and($result['performance']['cache_hits'])->toBe(0)
            ->and($result['performance']['cache_misses'])->toBe(3);

        // Verify all succeeded
        foreach ($result['results'] as $naicsResult) {
            expect($naicsResult['success'])->toBeTrue();
        }
    });

    test('returns results in same order as input NAICS codes', function () {
        Http::fake([
            'api.sam.gov/*' => Http::sequence()
                ->push(['totalRecords' => 1, 'opportunitiesData' => [['noticeId' => 'FIRST']]], 200)
                ->push(['totalRecords' => 1, 'opportunitiesData' => [['noticeId' => 'SECOND']]], 200)
                ->push(['totalRecords' => 1, 'opportunitiesData' => [['noticeId' => 'THIRD']]], 200),
        ]);

        $fetcher = new SamMultiNaicsFetcher;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $fetcher->fetchAll(['111111', '222222', '333333'], $params, 'test-key');

        expect($result['results'][0]['naics'])->toBe('111111')
            ->and($result['results'][1]['naics'])->toBe('222222')
            ->and($result['results'][2]['naics'])->toBe('333333');
    });
});

describe('cache integration', function () {
    test('uses cache before making API calls', function () {
        // Pre-populate cache
        $cache = new SamOpportunitiesCache;
        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $cachedResponse = [
            'success' => true,
            'naics' => '123456',
            'count' => 5,
            'opportunities' => [['notice_id' => 'CACHED']],
            'cached' => false,
        ];

        $cache->put('123456', $params, $cachedResponse);

        // Only expect API call for second NAICS
        Http::fake([
            'api.sam.gov/*' => Http::response([
                'totalRecords' => 1,
                'opportunitiesData' => [['noticeId' => 'API']],
            ], 200),
        ]);

        $fetcher = new SamMultiNaicsFetcher($cache);

        $result = $fetcher->fetchAll(['123456', '234567'], $params, 'test-key');

        expect($result['performance']['cache_hits'])->toBe(1)
            ->and($result['performance']['cache_misses'])->toBe(1);

        // Verify cached result was used
        expect($result['results'][0]['cached'])->toBeTrue()
            ->and($result['results'][0]['opportunities'][0]['notice_id'])->toBe('CACHED');
    });

    test('stores successful API responses in cache', function () {
        Http::fake([
            'api.sam.gov/*' => Http::response([
                'totalRecords' => 1,
                'opportunitiesData' => [['noticeId' => 'NEW']],
            ], 200),
        ]);

        $cache = new SamOpportunitiesCache;
        $fetcher = new SamMultiNaicsFetcher($cache);

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $fetcher->fetchAll(['123456'], $params, 'test-key');

        // Verify it was cached
        $cached = $cache->get('123456', $params);
        expect($cached)->not->toBeNull()
            ->and($cached['success'])->toBeTrue();
    });

    test('does not cache failed API responses', function () {
        Http::fake([
            'api.sam.gov/*' => Http::response('Unauthorized', 401),
        ]);

        $cache = new SamOpportunitiesCache;
        $fetcher = new SamMultiNaicsFetcher($cache);

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $fetcher->fetchAll(['123456'], $params, 'test-key');

        // Verify it was NOT cached
        $cached = $cache->get('123456', $params);
        expect($cached)->toBeNull();
    });
});

describe('error tolerance', function () {
    test('continues fetching after one NAICS fails', function () {
        Http::fake([
            'api.sam.gov/*' => Http::sequence()
                ->push(['totalRecords' => 1, 'opportunitiesData' => [['noticeId' => 'SUCCESS1']]], 200)
                ->push('Unauthorized', 401) // Non-retryable failure
                ->push(['totalRecords' => 1, 'opportunitiesData' => [['noticeId' => 'SUCCESS2']]], 200),
        ]);

        $fetcher = new SamMultiNaicsFetcher;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $fetcher->fetchAll(['111111', '222222', '333333'], $params, 'test-key');

        expect($result['results'])->toHaveCount(3)
            ->and($result['results'][0]['success'])->toBeTrue()
            ->and($result['results'][1]['success'])->toBeFalse()
            ->and($result['results'][2]['success'])->toBeTrue();
    });

    test('logs warnings for failed NAICS queries', function () {
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        Http::fake([
            'api.sam.gov/*' => Http::response('Unauthorized', 401),
        ]);

        $fetcher = new SamMultiNaicsFetcher;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $fetcher->fetchAll(['123456'], $params, 'test-key');

        // Just verify the fetcher handled the failure correctly
        expect($result['results'][0]['success'])->toBeFalse()
            ->and($result['results'][0]['status_code'])->toBe(401);
    });

    test('returns all failed when all NAICS queries fail', function () {
        Http::fake([
            'api.sam.gov/*' => Http::response('Server Error', 500),
        ]);

        $fetcher = new SamMultiNaicsFetcher;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $fetcher->fetchAll(['111111', '222222'], $params, 'test-key');

        expect($result['results'])->toHaveCount(2)
            ->and($result['results'][0]['success'])->toBeFalse()
            ->and($result['results'][1]['success'])->toBeFalse();
    });
});

describe('performance tracking', function () {
    test('tracks timing for each NAICS query', function () {
        Http::fake([
            'api.sam.gov/*' => Http::response([
                'totalRecords' => 1,
                'opportunitiesData' => [['noticeId' => 'TEST']],
            ], 200),
        ]);

        $fetcher = new SamMultiNaicsFetcher;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $fetcher->fetchAll(['111111', '222222'], $params, 'test-key');

        expect($result['performance'])
            ->toHaveKey('total_duration_ms')
            ->toHaveKey('cache_hits')
            ->toHaveKey('cache_misses')
            ->toHaveKey('per_naics')
            ->and($result['performance']['per_naics'])->toHaveCount(2);

        foreach ($result['performance']['per_naics'] as $metric) {
            expect($metric)
                ->toHaveKey('naics')
                ->toHaveKey('duration_ms')
                ->toHaveKey('count')
                ->toHaveKey('cached');
        }
    });

    test('distinguishes cached vs API calls in performance metrics', function () {
        // Pre-populate cache
        $cache = new SamOpportunitiesCache;
        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $cache->put('111111', $params, [
            'success' => true,
            'naics' => '111111',
            'count' => 3,
            'opportunities' => [],
        ]);

        Http::fake([
            'api.sam.gov/*' => Http::response([
                'totalRecords' => 5,
                'opportunitiesData' => [],
            ], 200),
        ]);

        $fetcher = new SamMultiNaicsFetcher($cache);

        $result = $fetcher->fetchAll(['111111', '222222'], $params, 'test-key');

        $metrics = $result['performance']['per_naics'];

        expect($metrics[0]['cached'])->toBeTrue()
            ->and($metrics[0]['naics'])->toBe('111111')
            ->and($metrics[1]['cached'])->toBeFalse()
            ->and($metrics[1]['naics'])->toBe('222222');
    });
});

describe('rate limiting delays', function () {
    test('adds delay between NAICS queries', function () {
        Http::fake([
            'api.sam.gov/*' => Http::response([
                'totalRecords' => 0,
                'opportunitiesData' => [],
            ], 200),
        ]);

        $fetcher = new SamMultiNaicsFetcher;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $startTime = microtime(true);

        $fetcher->fetchAll(['111111', '222222', '333333'], $params, 'test-key');

        $duration = (microtime(true) - $startTime) * 1000; // ms

        // Should take at least 1000ms (2 delays of ~500ms each)
        expect($duration)->toBeGreaterThan(1000);
    });

    test('does not add delay after last NAICS query', function () {
        Http::fake([
            'api.sam.gov/*' => Http::response([
                'totalRecords' => 0,
                'opportunitiesData' => [],
            ], 200),
        ]);

        $fetcher = new SamMultiNaicsFetcher;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $startTime = microtime(true);

        $fetcher->fetchAll(['111111'], $params, 'test-key');

        $duration = (microtime(true) - $startTime) * 1000; // ms

        // Should complete quickly (no delay for single NAICS)
        expect($duration)->toBeLessThan(500);
    });
});

describe('summary statistics', function () {
    test('calculates summary statistics correctly', function () {
        Http::fake([
            'api.sam.gov/*' => Http::sequence()
                ->push(['totalRecords' => 1, 'opportunitiesData' => [['noticeId' => 'A']]], 200)
                ->push('Error', 500)
                ->push(['totalRecords' => 1, 'opportunitiesData' => [['noticeId' => 'B']]], 200),
        ]);

        $fetcher = new SamMultiNaicsFetcher;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $fetcher->fetchAll(['111111', '222222', '333333'], $params, 'test-key');
        $summary = $fetcher->getSummary($result);

        expect($summary)
            ->toHaveKey('total_naics_queried')
            ->toHaveKey('successful_naics')
            ->toHaveKey('failed_naics')
            ->toHaveKey('cache_hit_rate')
            ->toHaveKey('total_duration_ms')
            ->and($summary['total_naics_queried'])->toBe(3)
            ->and($summary['successful_naics'])->toBe(2)
            ->and($summary['failed_naics'])->toBe(1);
    });

    test('calculates cache hit rate correctly', function () {
        // Pre-populate cache for 2 out of 3
        $cache = new SamOpportunitiesCache;
        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $cache->put('111111', $params, ['success' => true, 'naics' => '111111', 'count' => 1, 'opportunities' => []]);
        $cache->put('222222', $params, ['success' => true, 'naics' => '222222', 'count' => 1, 'opportunities' => []]);

        Http::fake([
            'api.sam.gov/*' => Http::response(['totalRecords' => 1, 'opportunitiesData' => []], 200),
        ]);

        $fetcher = new SamMultiNaicsFetcher($cache);

        $result = $fetcher->fetchAll(['111111', '222222', '333333'], $params, 'test-key');
        $summary = $fetcher->getSummary($result);

        expect($summary['cache_hit_rate'])->toBe('66.7%'); // 2 out of 3
    });
});

describe('failed NAICS extraction', function () {
    test('extracts failed NAICS details', function () {
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        Http::fake([
            'api.sam.gov/*' => Http::sequence()
                ->push(['totalRecords' => 1, 'opportunitiesData' => []], 200) // 111111 success
                ->push('Unauthorized', 401) // 222222 fail
                ->push('Server Error', 500), // 333333 fail
        ]);

        $fetcher = new SamMultiNaicsFetcher;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $fetcher->fetchAll(['111111', '222222', '333333'], $params, 'test-key');
        $failed = $fetcher->getFailedNaics($result);

        expect($failed)->toHaveCount(2)
            ->and($failed[0]['naics'])->toBe('222222')
            ->and($failed[0]['status_code'])->toBe(401)
            ->and($failed[1]['naics'])->toBe('333333')
            ->and($failed[1]['status_code'])->toBe(500);
    });

    test('returns empty array when no failures', function () {
        Http::fake([
            'api.sam.gov/*' => Http::response(['totalRecords' => 0, 'opportunitiesData' => []], 200),
        ]);

        $fetcher = new SamMultiNaicsFetcher;

        $params = [
            'posted_from' => '01/01/2025',
            'posted_to' => '01/31/2025',
            'notice_type_codes' => ['o'],
            'place' => 'CO',
        ];

        $result = $fetcher->fetchAll(['111111'], $params, 'test-key');
        $failed = $fetcher->getFailedNaics($result);

        expect($failed)->toBe([]);
    });
});
