<?php

declare(strict_types=1);

use App\Support\SamOpportunitiesCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Clear cache before each test
    Cache::flush();
});

describe('cache get operations', function () {
    test('returns null on cache miss', function () {
        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        $result = $cache->get('123456', $params);

        expect($result)->toBeNull();
    });

    test('returns cached response on cache hit', function () {
        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        $response = [
            'success' => true,
            'naics' => '123456',
            'count' => 5,
            'opportunities' => [
                ['notice_id' => 'ABC123', 'title' => 'Test'],
            ],
        ];

        // Store in cache
        $cache->put('123456', $params, $response);

        // Retrieve from cache
        $cached = $cache->get('123456', $params);

        expect($cached)
            ->not->toBeNull()
            ->toHaveKey('success')
            ->toHaveKey('naics')
            ->toHaveKey('cached')
            ->and($cached['success'])->toBeTrue()
            ->and($cached['naics'])->toBe('123456')
            ->and($cached['cached'])->toBeTrue()
            ->and($cached['count'])->toBe(5);
    });

    test('sets cached flag to true when retrieving', function () {
        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        $response = [
            'success' => true,
            'naics' => '123456',
        ];

        $cache->put('123456', $params, $response);
        $cached = $cache->get('123456', $params);

        expect($cached['cached'])->toBeTrue();
    });

    test('returns null and logs warning on cache driver failure', function () {
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'SAM.gov cache get failed'
                    && $context['cache'] === 'SamOpportunitiesCache'
                    && $context['naics'] === '123456'
                    && $context['error_category'] === 'cache_error';
            });

        Cache::shouldReceive('get')
            ->once()
            ->andThrow(new Exception('Cache driver unavailable'));

        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        $result = $cache->get('123456', $params);

        expect($result)->toBeNull();
    });
});

describe('cache put operations', function () {
    test('stores response in cache successfully', function () {
        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        $response = [
            'success' => true,
            'naics' => '123456',
            'count' => 10,
        ];

        $result = $cache->put('123456', $params, $response);

        expect($result)->toBeTrue();

        // Verify it was stored
        $cached = $cache->get('123456', $params);
        expect($cached)->not->toBeNull();
    });

    test('uses correct TTL of 15 minutes', function () {
        $cache = new SamOpportunitiesCache;

        expect($cache->getTtl())->toBe(900); // 15 minutes = 900 seconds
    });

    test('returns false and logs warning on cache driver failure', function () {
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'SAM.gov cache put failed'
                    && $context['cache'] === 'SamOpportunitiesCache'
                    && $context['naics'] === '123456'
                    && $context['error_category'] === 'cache_error';
            });

        Cache::shouldReceive('put')
            ->once()
            ->andThrow(new Exception('Cache driver unavailable'));

        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        $response = ['success' => true];

        $result = $cache->put('123456', $params, $response);

        expect($result)->toBeFalse();
    });
});

describe('cache has operations', function () {
    test('returns false when cache entry does not exist', function () {
        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        $result = $cache->has('123456', $params);

        expect($result)->toBeFalse();
    });

    test('returns true when cache entry exists', function () {
        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        $response = ['success' => true, 'naics' => '123456'];

        $cache->put('123456', $params, $response);

        $result = $cache->has('123456', $params);

        expect($result)->toBeTrue();
    });

    test('returns false and logs warning on cache driver failure', function () {
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'SAM.gov cache has check failed';
            });

        Cache::shouldReceive('has')
            ->once()
            ->andThrow(new Exception('Cache driver unavailable'));

        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        $result = $cache->has('123456', $params);

        expect($result)->toBeFalse();
    });
});

describe('cache forget operations', function () {
    test('clears cached entry successfully', function () {
        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        $response = ['success' => true, 'naics' => '123456'];

        // Store in cache
        $cache->put('123456', $params, $response);
        expect($cache->has('123456', $params))->toBeTrue();

        // Clear cache
        $result = $cache->forget('123456', $params);

        expect($result)->toBeTrue();
        expect($cache->has('123456', $params))->toBeFalse();
    });

    test('returns true even when clearing non-existent cache entry', function () {
        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        // Laravel's cache forget returns true even if key didn't exist
        $result = $cache->forget('123456', $params);

        expect($result)->toBeTrue();
    });

    test('returns false and logs warning on cache driver failure', function () {
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'SAM.gov cache forget failed';
            });

        Cache::shouldReceive('forget')
            ->once()
            ->andThrow(new Exception('Cache driver unavailable'));

        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        $result = $cache->forget('123456', $params);

        expect($result)->toBeFalse();
    });
});

describe('cache forgetMultiple operations', function () {
    test('clears multiple cache entries successfully', function () {
        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        // Store multiple entries
        $cache->put('123456', $params, ['success' => true]);
        $cache->put('234567', $params, ['success' => true]);
        $cache->put('345678', $params, ['success' => true]);

        // Clear all
        $clearedCount = $cache->forgetMultiple(['123456', '234567', '345678'], $params);

        expect($clearedCount)->toBe(3);
        expect($cache->has('123456', $params))->toBeFalse();
        expect($cache->has('234567', $params))->toBeFalse();
        expect($cache->has('345678', $params))->toBeFalse();
    });

    test('returns total count when clearing entries (Laravel cache always returns true)', function () {
        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        // Only store one entry
        $cache->put('123456', $params, ['success' => true]);

        // Try to clear three (Laravel cache forget returns true even for non-existent)
        $clearedCount = $cache->forgetMultiple(['123456', '234567', '345678'], $params);

        expect($clearedCount)->toBe(3); // All return true in array cache driver
    });

    test('returns count when no entries exist (Laravel cache behavior)', function () {
        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        // Laravel cache forget returns true even for non-existent keys
        $clearedCount = $cache->forgetMultiple(['123456', '234567'], $params);

        expect($clearedCount)->toBe(2); // Both return true in array cache driver
    });
});

describe('cache key generation', function () {
    test('generates consistent cache keys for same parameters', function () {
        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        // Store with one instance
        $cache->put('123456', $params, ['test' => 'data']);

        // Retrieve with another instance
        $cache2 = new SamOpportunitiesCache;
        $result = $cache2->get('123456', $params);

        expect($result)->not->toBeNull();
    });

    test('generates different cache keys for different NAICS codes', function () {
        $cache = new SamOpportunitiesCache;

        $params = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        $cache->put('123456', $params, ['naics' => '123456']);
        $cache->put('234567', $params, ['naics' => '234567']);

        $result1 = $cache->get('123456', $params);
        $result2 = $cache->get('234567', $params);

        expect($result1['naics'])->toBe('123456')
            ->and($result2['naics'])->toBe('234567');
    });

    test('generates different cache keys for different parameters', function () {
        $cache = new SamOpportunitiesCache;

        $params1 = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        $params2 = [
            'place' => 'CA', // Different state
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
        ];

        $cache->put('123456', $params1, ['state' => 'CO']);
        $cache->put('123456', $params2, ['state' => 'CA']);

        $result1 = $cache->get('123456', $params1);
        $result2 = $cache->get('123456', $params2);

        expect($result1['state'])->toBe('CO')
            ->and($result2['state'])->toBe('CA');
    });

    test('cache key excludes limit parameter', function () {
        $cache = new SamOpportunitiesCache;

        $params1 = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
            'limit' => 50,
        ];

        $params2 = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
            'limit' => 100, // Different limit
        ];

        $cache->put('123456', $params1, ['test' => 'data']);

        // Should hit cache even with different limit
        $result = $cache->get('123456', $params2);

        expect($result)->not->toBeNull()
            ->and($result['test'])->toBe('data');
    });

    test('cache key excludes clearCache parameter', function () {
        $cache = new SamOpportunitiesCache;

        $params1 = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
            'clearCache' => false,
        ];

        $params2 = [
            'place' => 'CO',
            'days_back' => 30,
            'notice_type_codes' => ['o', 'p'],
            'posted_from' => '10/20/2025',
            'posted_to' => '11/19/2025',
            'clearCache' => true, // Different clearCache
        ];

        $cache->put('123456', $params1, ['test' => 'data']);

        // Should hit cache even with different clearCache flag
        $result = $cache->get('123456', $params2);

        expect($result)->not->toBeNull()
            ->and($result['test'])->toBe('data');
    });

    test('uses correct cache key prefix', function () {
        $cache = new SamOpportunitiesCache;

        expect($cache->getPrefix())->toBe('sam_opp_v2');
    });
});
