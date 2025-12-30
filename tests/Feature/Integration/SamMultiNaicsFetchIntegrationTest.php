<?php

declare(strict_types=1);

use App\Mcp\Servers\Business\Tools\FetchSamOpportunitiesTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Set up test environment
    Config::set('services.sam.api_key', 'test-api-key');
    Storage::fake('local');
    Cache::flush();

    // IMPORTANT: Tests use naics_override parameter, so we don't need database defaults
    // This prevents GsaFilter seeder from running and interfering with test isolation
    $this->seed = false;
});

describe('complete success scenario', function () {
    test('fetches opportunities from multiple NAICS codes with deduplication', function () {
        // Mock SAM.gov API responses using pattern matching
        Http::fake([
            '*naics=236220*' => Http::response([
                'opportunitiesData' => [
                    [
                        'noticeId' => 'opp-001',
                        'title' => 'Construction Project',
                        'type' => 'Solicitation',
                        'postedDate' => '2025-01-10',
                        'responseDeadLine' => '2025-02-10',
                        'naicsCode' => '236220',
                        'department' => ['name' => 'Department of Defense'],
                    ],
                    [
                        'noticeId' => 'opp-002',
                        'title' => 'Building Renovation',
                        'type' => 'Presolicitation',
                        'postedDate' => '2025-01-12',
                        'responseDeadLine' => '2025-02-15',
                        'naicsCode' => '236220',
                        'department' => ['name' => 'GSA'],
                    ],
                    [
                        'noticeId' => 'opp-duplicate',
                        'title' => 'Shared Opportunity',
                        'type' => 'Solicitation',
                        'postedDate' => '2025-01-15',
                        'lastModifiedDate' => '2025-01-15T10:00:00Z',
                        'naicsCode' => '236220',
                        'department' => ['name' => 'Department of Defense'],
                    ],
                ],
            ], 200),
            '*naics=541330*' => Http::response([
                'opportunitiesData' => [
                    [
                        'noticeId' => 'opp-003',
                        'title' => 'Engineering Services',
                        'type' => 'Solicitation',
                        'postedDate' => '2025-01-11',
                        'responseDeadLine' => '2025-02-12',
                        'naicsCode' => '541330',
                        'department' => ['name' => 'Army Corps'],
                    ],
                    [
                        'noticeId' => 'opp-duplicate',
                        'title' => 'Shared Opportunity',
                        'type' => 'Solicitation',
                        'postedDate' => '2025-01-15',
                        'lastModifiedDate' => '2025-01-16T10:00:00Z', // More recent
                        'naicsCode' => '541330',
                        'department' => ['name' => 'Department of Defense'],
                    ],
                ],
            ], 200),
            '*naics=562910*' => Http::response([
                'opportunitiesData' => [
                    [
                        'noticeId' => 'opp-004',
                        'title' => 'Environmental Cleanup',
                        'type' => 'Solicitation',
                        'postedDate' => '2025-01-13',
                        'responseDeadLine' => '2025-02-13',
                        'naicsCode' => '562910',
                        'department' => ['name' => 'EPA'],
                    ],
                ],
            ], 200),
        ]);

        $tool = new FetchSamOpportunitiesTool;
        $result = $tool->fetch([
            'naics_override' => ['236220', '541330', '562910'],
            'days_back' => 30,
            'limit' => 10,
        ]);

        // Verify success response
        expect($result)->toHaveKey('success')
            ->and($result)->toHaveKey('fetched_at')
            ->and($result['success'])->toBeTrue()
            ->and($result['partial_success'])->toBeFalse()
            ->and($result['fetched_at'])->toBeString();

        // Verify opportunities (should have 5 unique after deduplication)
        expect($result['opportunities'])->toHaveCount(5);

        // Verify deduplication worked (6 total - 1 duplicate = 5 unique)
        expect($result['summary']['total_records'])->toBe(6)
            ->and($result['summary']['total_after_dedup'])->toBe(5)
            ->and($result['summary']['duplicates_removed'])->toBe(1)
            ->and($result['summary']['returned'])->toBe(5);

        // Verify NAICS query stats
        expect($result['summary']['successful_naics_count'])->toBe(3)
            ->and($result['summary']['failed_naics_count'])->toBe(0);

        // Verify cache stats (all cache misses on first run)
        expect($result['performance']['cache_hits'])->toBe(0)
            ->and($result['performance']['cache_misses'])->toBe(3);

        // Verify query metadata
        expect($result['query']['naics_codes'])->toBe(['236220', '541330', '562910'])
            ->and($result['query']['state_code'])->toBe('nationwide');

        // Verify state file was created
        $stateFiles = Storage::disk('local')->files('sam/state');
        expect($stateFiles)->toHaveCount(1);
    });

    test('uses cache on second identical request', function () {
        // First request - all cache misses
        Http::fake([
            '*naics=236220*' => Http::response([
                'opportunitiesData' => [
                    ['noticeId' => 'opp-001', 'title' => 'Test', 'naicsCode' => '236220'],
                ],
            ], 200),
            '*naics=541330*' => Http::response([
                'opportunitiesData' => [
                    ['noticeId' => 'opp-002', 'title' => 'Test 2', 'naicsCode' => '541330'],
                ],
            ], 200),
            '*naics=562910*' => Http::response([
                'opportunitiesData' => [],
            ], 200),
        ]);

        $tool = new FetchSamOpportunitiesTool;
        $result1 = $tool->fetch([
            'naics_override' => ['236220', '541330', '562910'],
            'days_back' => 30,
        ]);

        expect($result1['performance']['cache_hits'])->toBe(0)
            ->and($result1['performance']['cache_misses'])->toBe(3);

        // Second request - all cache hits
        $result2 = $tool->fetch([
            'naics_override' => ['236220', '541330', '562910'],
            'days_back' => 30,
        ]);

        expect($result2['performance']['cache_hits'])->toBe(3)
            ->and($result2['performance']['cache_misses'])->toBe(0)
            ->and($result2['summary']['cache_hit_rate'])->toBe('100%');

        // Should have same opportunities
        expect($result2['opportunities'])->toHaveCount(count($result1['opportunities']));
    });

    test('respects cache TTL', function () {
        // First request - cache miss
        Http::fake([
            '*naics=236220*' => Http::response(['opportunitiesData' => [
                ['noticeId' => 'opp-001', 'title' => 'Test', 'naicsCode' => '236220'],
            ]], 200),
            '*naics=541330*' => Http::response(['opportunitiesData' => []], 200),
            '*naics=562910*' => Http::response(['opportunitiesData' => []], 200),
        ]);

        $tool = new FetchSamOpportunitiesTool;
        $result1 = $tool->fetch([
            'naics_override' => ['236220', '541330', '562910'],
            'days_back' => 30,
        ]);

        expect($result1['opportunities'][0]['title'])->toBe('Test')
            ->and($result1['performance']['cache_hits'])->toBe(0)
            ->and($result1['performance']['cache_misses'])->toBe(3);

        // Second request - cache hit (same data)
        $result2 = $tool->fetch([
            'naics_override' => ['236220', '541330', '562910'],
            'days_back' => 30,
        ]);

        expect($result2['opportunities'][0]['title'])->toBe('Test')
            ->and($result2['performance']['cache_hits'])->toBe(3)
            ->and($result2['performance']['cache_misses'])->toBe(0);
    });
});

describe('partial success scenario', function () {
    test('returns results from successful NAICS when some fail', function () {
        Http::fake([
            '*naics=236220*' => Http::response([
                'opportunitiesData' => [
                    ['noticeId' => 'opp-001', 'title' => 'Success 1', 'naicsCode' => '236220'],
                    ['noticeId' => 'opp-002', 'title' => 'Success 2', 'naicsCode' => '236220'],
                ],
            ], 200),
            '*naics=541330*' => Http::response([
                'opportunitiesData' => [
                    ['noticeId' => 'opp-003', 'title' => 'Success 3', 'naicsCode' => '541330'],
                ],
            ], 200),
            '*naics=562910*' => Http::response([
                'error' => 'Unauthorized',
            ], 401),
        ]);

        $tool = new FetchSamOpportunitiesTool;
        $result = $tool->fetch([
            'naics_override' => ['236220', '541330', '562910'],
            'days_back' => 30,
        ]);

        // Verify partial success
        expect($result['success'])->toBeFalse()
            ->and($result['partial_success'])->toBeTrue();

        // Should still have opportunities from successful NAICS
        expect($result['opportunities'])->toHaveCount(3);

        // Verify success/failure counts
        expect($result['summary']['successful_naics_count'])->toBe(2)
            ->and($result['summary']['failed_naics_count'])->toBe(1);

        // Verify failed NAICS details are present
        expect($result['summary'])->toHaveKey('failed_naics')
            ->and($result['summary']['failed_naics'])->toHaveCount(1)
            ->and($result['summary']['failed_naics'][0]['naics'])->toBe('562910');
    });

    test('handles network timeout for one NAICS', function () {
        Http::fake([
            '*naics=236220*' => Http::response([
                'opportunitiesData' => [
                    ['noticeId' => 'opp-001', 'title' => 'Success', 'naicsCode' => '236220'],
                ],
            ], 200),
            '*naics=541330*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            },
            '*naics=562910*' => Http::response([
                'opportunitiesData' => [
                    ['noticeId' => 'opp-002', 'title' => 'Success 2', 'naicsCode' => '562910'],
                ],
            ], 200),
        ]);

        $tool = new FetchSamOpportunitiesTool;
        $result = $tool->fetch([
            'naics_override' => ['236220', '541330', '562910'],
            'days_back' => 30,
        ]);

        expect($result['partial_success'])->toBeTrue()
            ->and($result['opportunities'])->toHaveCount(2)
            ->and($result['summary']['successful_naics_count'])->toBe(2)
            ->and($result['summary']['failed_naics_count'])->toBe(1);
    });
});

describe('complete failure scenario', function () {
    test('returns failure response when all NAICS queries fail', function () {
        Http::fake([
            '*naics=*' => Http::response([
                'error' => 'Invalid API key',
            ], 401),
        ]);

        $tool = new FetchSamOpportunitiesTool;
        $result = $tool->fetch([
            'naics_override' => ['236220', '541330', '562910'],
            'days_back' => 30,
        ]);

        // Verify failure response
        expect($result['success'])->toBeFalse()
            ->and($result['partial_success'])->toBeFalse()
            ->and($result)->toHaveKey('error');

        // Should have no opportunities
        expect($result['opportunities'])->toBeEmpty();

        // All NAICS should have failed
        expect($result['summary']['successful_naics_count'])->toBe(0)
            ->and($result['summary']['failed_naics_count'])->toBe(3)
            ->and($result['summary']['failed_naics'])->toHaveCount(3);

        // Verify cache stats
        expect($result['performance']['cache_hits'])->toBe(0)
            ->and($result['performance']['cache_misses'])->toBe(3);
    });

    test('returns failure when API key is missing', function () {
        Config::set('services.sam.api_key', null);

        $tool = new FetchSamOpportunitiesTool;
        $result = $tool->fetch([
            'naics_override' => ['236220'],
        ]);

        expect($result['success'])->toBeFalse()
            ->and($result['partial_success'])->toBeFalse()
            ->and($result['error'])->toContain('API key not configured');
    });
});

describe('parameter override scenarios', function () {
    test('uses overridden NAICS codes instead of database defaults', function () {
        Http::fake([
            '*naics=999999*' => Http::response([
                'opportunitiesData' => [
                    ['noticeId' => 'opp-custom', 'title' => 'Custom NAICS', 'naicsCode' => '999999'],
                ],
            ], 200),
        ]);

        $tool = new FetchSamOpportunitiesTool;
        $result = $tool->fetch([
            'naics_override' => ['999999'],
            'days_back' => 30,
        ]);

        expect($result['success'])->toBeTrue()
            ->and($result['opportunities'])->toHaveCount(1)
            ->and($result['query']['naics_codes'])->toBe(['999999'])
            ->and($result['summary']['successful_naics_count'])->toBe(1);
    });

    test('applies limit correctly', function () {
        Http::fake([
            '*naics=236220*' => Http::response([
                'opportunitiesData' => array_map(fn ($i) => [
                    'noticeId' => "opp-{$i}",
                    'title' => "Opportunity {$i}",
                    'naicsCode' => '236220',
                    'postedDate' => '2025-01-10',
                ], range(1, 100)),
            ], 200),
            '*naics=541330*' => Http::response(['opportunitiesData' => []], 200),
            '*naics=562910*' => Http::response(['opportunitiesData' => []], 200),
        ]);

        $tool = new FetchSamOpportunitiesTool;
        $result = $tool->fetch([
            'naics_override' => ['236220', '541330', '562910'],
            'limit' => 10,
            'days_back' => 30,
        ]);

        expect($result['opportunities'])->toHaveCount(10)
            ->and($result['summary']['total_records'])->toBe(100)
            ->and($result['summary']['returned'])->toBe(10);
    });

    test('applies place filter correctly', function () {
        Http::fake([
            '*naics=*' => Http::response(['opportunitiesData' => []], 200),
        ]);

        $tool = new FetchSamOpportunitiesTool;
        $tool->fetch([
            'naics_override' => ['236220', '541330', '562910'],
            'place' => 'TX',
            'days_back' => 30,
        ]);

        // Verify requests included place parameter
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'state=TX');
        });
    });
});

describe('deduplication behavior', function () {
    test('removes exact duplicates by notice_id', function () {
        Http::fake([
            '*naics=236220*' => Http::response([
                'opportunitiesData' => [
                    ['noticeId' => 'opp-001', 'title' => 'Test 1', 'naicsCode' => '236220'],
                    ['noticeId' => 'opp-002', 'title' => 'Test 2', 'naicsCode' => '236220'],
                ],
            ], 200),
            '*naics=541330*' => Http::response([
                'opportunitiesData' => [
                    ['noticeId' => 'opp-001', 'title' => 'Test 1 Duplicate', 'naicsCode' => '541330'],
                    ['noticeId' => 'opp-003', 'title' => 'Test 3', 'naicsCode' => '541330'],
                ],
            ], 200),
            '*naics=562910*' => Http::response(['opportunitiesData' => []], 200),
        ]);

        $tool = new FetchSamOpportunitiesTool;
        $result = $tool->fetch([
            'naics_override' => ['236220', '541330', '562910'],
            'days_back' => 30,
        ]);

        expect($result['summary']['total_records'])->toBe(4)
            ->and($result['summary']['total_after_dedup'])->toBe(3)
            ->and($result['summary']['duplicates_removed'])->toBe(1)
            ->and($result['opportunities'])->toHaveCount(3);
    });

    test('prefers most recent lastModifiedDate when deduplicating', function () {
        Http::fake([
            '*naics=236220*' => Http::response([
                'opportunitiesData' => [
                    [
                        'noticeId' => 'opp-001',
                        'title' => 'Older Version',
                        'lastModifiedDate' => '2025-01-10T10:00:00Z',
                        'naicsCode' => '236220',
                    ],
                ],
            ], 200),
            '*naics=541330*' => Http::response([
                'opportunitiesData' => [
                    [
                        'noticeId' => 'opp-001',
                        'title' => 'Newer Version',
                        'lastModifiedDate' => '2025-01-15T10:00:00Z',
                        'naicsCode' => '541330',
                    ],
                ],
            ], 200),
            '*naics=562910*' => Http::response(['opportunitiesData' => []], 200),
        ]);

        $tool = new FetchSamOpportunitiesTool;
        $result = $tool->fetch([
            'naics_override' => ['236220', '541330', '562910'],
            'days_back' => 30,
        ]);

        expect($result['opportunities'])->toHaveCount(1)
            ->and($result['opportunities'][0]['title'])->toBe('Newer Version');
    });
});

describe('performance metrics and warnings', function () {
    test('calculates performance metrics correctly', function () {
        Http::fake([
            '*naics=236220*' => Http::response(['opportunitiesData' => [
                ['noticeId' => 'opp-001', 'title' => 'Test', 'naicsCode' => '236220'],
            ]], 200),
            '*naics=541330*' => Http::response(['opportunitiesData' => []], 200),
            '*naics=562910*' => Http::response(['opportunitiesData' => []], 200),
        ]);

        $tool = new FetchSamOpportunitiesTool;
        $result = $tool->fetch([
            'naics_override' => ['236220', '541330', '562910'],
            'days_back' => 30,
        ]);

        expect($result['performance'])->toHaveKeys(['total_duration_ms', 'cache_hits', 'cache_misses'])
            ->and($result['performance']['total_duration_ms'])->toBeGreaterThan(0)
            ->and($result['summary']['cache_hit_rate'])->toBe('0%');
    });

    test('detects high deduplication rate warning', function () {
        // Create many duplicates
        $opportunities = array_merge(
            array_map(fn ($i) => ['noticeId' => "unique-{$i}", 'title' => "Unique {$i}", 'naicsCode' => '236220'], range(1, 30)),
            array_map(fn ($i) => ['noticeId' => "dup-{$i}", 'title' => "Dup {$i}", 'naicsCode' => '236220'], range(1, 40))
        );

        Http::fake([
            '*naics=236220*' => Http::response(['opportunitiesData' => $opportunities], 200),
            '*naics=541330*' => Http::response(['opportunitiesData' => array_map(
                fn ($i) => ['noticeId' => "dup-{$i}", 'title' => "Dup {$i}", 'naicsCode' => '541330'],
                range(1, 40)
            )], 200),
            '*naics=562910*' => Http::response(['opportunitiesData' => []], 200),
        ]);

        $tool = new FetchSamOpportunitiesTool;
        $result = $tool->fetch([
            'naics_override' => ['236220', '541330', '562910'],
            'days_back' => 30,
        ]);

        // Should have deduplication rate warning
        expect($result['summary']['duplicates_removed'])->toBeGreaterThan(30);
    });
});

describe('state file management', function () {
    test('saves state file after successful fetch', function () {
        Http::fake([
            '*naics=236220*' => Http::response(['opportunitiesData' => [
                ['noticeId' => 'opp-001', 'title' => 'Test', 'naicsCode' => '236220'],
            ]], 200),
            '*naics=541330*' => Http::response(['opportunitiesData' => []], 200),
            '*naics=562910*' => Http::response(['opportunitiesData' => []], 200),
        ]);

        $tool = new FetchSamOpportunitiesTool;
        $tool->fetch([
            'naics_override' => ['236220', '541330', '562910'],
            'days_back' => 30,
        ]);

        // Verify state file was created
        $stateFiles = Storage::disk('local')->files('sam/state');
        expect($stateFiles)->toHaveCount(1);

        // Verify state file contents
        $stateContent = Storage::disk('local')->get($stateFiles[0]);
        $state = json_decode($stateContent, true);

        expect($state)->toHaveKeys(['timestamp', 'params', 'summary', 'failed_naics'])
            ->and($state['params']['naics_codes'])->toBe(['236220', '541330', '562910'])
            ->and($state['summary']['total_after_dedup'])->toBeInt();
    });

    test('rotates old state files', function () {
        Http::fake([
            '*naics=*' => Http::response(['opportunitiesData' => []], 200),
        ]);

        $tool = new FetchSamOpportunitiesTool;

        // Create 12 state files
        for ($i = 0; $i < 12; $i++) {
            $tool->fetch([
                'naics_override' => ['236220'],
                'days_back' => 30,
            ]);
            usleep(100000); // 100ms to ensure unique timestamps
        }

        // Should only keep 10 most recent
        $stateFiles = Storage::disk('local')->files('sam/state');
        expect(count($stateFiles))->toBeLessThanOrEqual(10);
    });
});

describe('sorting and ordering', function () {
    test('sorts opportunities by posted_date descending', function () {
        Http::fake([
            '*naics=236220*' => Http::response([
                'opportunitiesData' => [
                    ['noticeId' => 'opp-001', 'title' => 'Oldest', 'postedDate' => '2025-01-05', 'naicsCode' => '236220'],
                    ['noticeId' => 'opp-002', 'title' => 'Middle', 'postedDate' => '2025-01-10', 'naicsCode' => '236220'],
                    ['noticeId' => 'opp-003', 'title' => 'Newest', 'postedDate' => '2025-01-15', 'naicsCode' => '236220'],
                ],
            ], 200),
            '*naics=541330*' => Http::response(['opportunitiesData' => []], 200),
            '*naics=562910*' => Http::response(['opportunitiesData' => []], 200),
        ]);

        $tool = new FetchSamOpportunitiesTool;
        $result = $tool->fetch([
            'naics_override' => ['236220', '541330', '562910'],
            'days_back' => 30,
        ]);

        // Verify newest first
        expect($result['opportunities'][0]['title'])->toBe('Newest')
            ->and($result['opportunities'][1]['title'])->toBe('Middle')
            ->and($result['opportunities'][2]['title'])->toBe('Oldest');
    });
});
