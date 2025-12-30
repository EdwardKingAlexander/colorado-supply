<?php

declare(strict_types=1);

use App\Support\SamOpportunityDeduplicator;
use Illuminate\Support\Facades\Log;

describe('merging NAICS results', function () {
    test('merges opportunities from multiple successful NAICS queries', function () {
        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '123456',
                'count' => 2,
                'opportunities' => [
                    ['notice_id' => 'A1', 'title' => 'Opportunity A1'],
                    ['notice_id' => 'A2', 'title' => 'Opportunity A2'],
                ],
            ],
            [
                'success' => true,
                'naics' => '234567',
                'count' => 2,
                'opportunities' => [
                    ['notice_id' => 'B1', 'title' => 'Opportunity B1'],
                    ['notice_id' => 'B2', 'title' => 'Opportunity B2'],
                ],
            ],
        ];

        $result = $deduplicator->merge($naicsResults);

        expect($result)
            ->toHaveKey('opportunities')
            ->toHaveKey('total_count')
            ->toHaveKey('count_before_dedup')
            ->toHaveKey('total_after_dedup')
            ->toHaveKey('duplicates_removed')
            ->and($result['opportunities'])->toHaveCount(4)
            ->and($result['total_count'])->toBe(4)
            ->and($result['duplicates_removed'])->toBe(0);
    });

    test('tags opportunities with source_naics', function () {
        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '123456',
                'count' => 1,
                'opportunities' => [
                    ['notice_id' => 'A1', 'title' => 'Test'],
                ],
            ],
        ];

        $result = $deduplicator->merge($naicsResults);

        expect($result['opportunities'][0])
            ->toHaveKey('source_naics')
            ->and($result['opportunities'][0]['source_naics'])->toBe('123456');
    });

    test('skips failed NAICS queries', function () {
        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '123456',
                'count' => 1,
                'opportunities' => [
                    ['notice_id' => 'A1', 'title' => 'Success'],
                ],
            ],
            [
                'success' => false,
                'naics' => '234567',
                'error' => 'Rate limit exceeded',
            ],
        ];

        $result = $deduplicator->merge($naicsResults);

        expect($result['opportunities'])->toHaveCount(1)
            ->and($result['naics_succeeded'])->toContain('123456')
            ->and($result['naics_failed'])->toContain('234567');
    });

    test('handles empty opportunities array', function () {
        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '123456',
                'count' => 0,
                'opportunities' => [],
            ],
        ];

        $result = $deduplicator->merge($naicsResults);

        expect($result['opportunities'])->toBe([])
            ->and($result['total_count'])->toBe(0);
    });

    test('tracks NAICS query statistics', function () {
        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            ['success' => true, 'naics' => '111111', 'count' => 1, 'opportunities' => [['notice_id' => 'A']]],
            ['success' => true, 'naics' => '222222', 'count' => 1, 'opportunities' => [['notice_id' => 'B']]],
            ['success' => false, 'naics' => '333333', 'error' => 'Failed'],
        ];

        $result = $deduplicator->merge($naicsResults);

        expect($result['naics_queried'])->toBe(['111111', '222222', '333333'])
            ->and($result['naics_succeeded'])->toBe(['111111', '222222'])
            ->and($result['naics_failed'])->toBe(['333333']);
    });
});

describe('deduplication by notice_id', function () {
    test('removes duplicate notice_ids', function () {
        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '123456',
                'count' => 2,
                'opportunities' => [
                    ['notice_id' => 'DUP-001', 'title' => 'Duplicate 1'],
                    ['notice_id' => 'UNIQUE-001', 'title' => 'Unique 1'],
                ],
            ],
            [
                'success' => true,
                'naics' => '234567',
                'count' => 2,
                'opportunities' => [
                    ['notice_id' => 'DUP-001', 'title' => 'Duplicate 2'], // Duplicate
                    ['notice_id' => 'UNIQUE-002', 'title' => 'Unique 2'],
                ],
            ],
        ];

        $result = $deduplicator->merge($naicsResults);

        expect($result['count_before_dedup'])->toBe(4)
            ->and($result['total_after_dedup'])->toBe(3)
            ->and($result['duplicates_removed'])->toBe(1);
    });

    test('prefers opportunity with most recent lastModifiedDate', function () {
        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '123456',
                'count' => 1,
                'opportunities' => [
                    [
                        'notice_id' => 'DUP-001',
                        'title' => 'Older Version',
                        'lastModifiedDate' => '2025-01-10T10:00:00Z',
                    ],
                ],
            ],
            [
                'success' => true,
                'naics' => '234567',
                'count' => 1,
                'opportunities' => [
                    [
                        'notice_id' => 'DUP-001',
                        'title' => 'Newer Version',
                        'lastModifiedDate' => '2025-01-15T10:00:00Z',
                    ],
                ],
            ],
        ];

        $result = $deduplicator->merge($naicsResults);

        expect($result['opportunities'])->toHaveCount(1)
            ->and($result['opportunities'][0]['title'])->toBe('Newer Version');
    });

    test('keeps first occurrence when no lastModifiedDate', function () {
        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '123456',
                'count' => 1,
                'opportunities' => [
                    ['notice_id' => 'DUP-001', 'title' => 'First Occurrence'],
                ],
            ],
            [
                'success' => true,
                'naics' => '234567',
                'count' => 1,
                'opportunities' => [
                    ['notice_id' => 'DUP-001', 'title' => 'Second Occurrence'],
                ],
            ],
        ];

        $result = $deduplicator->merge($naicsResults);

        expect($result['opportunities'])->toHaveCount(1)
            ->and($result['opportunities'][0]['title'])->toBe('First Occurrence');
    });

    test('prefers opportunity with date over one without', function () {
        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '123456',
                'count' => 1,
                'opportunities' => [
                    ['notice_id' => 'DUP-001', 'title' => 'No Date'],
                ],
            ],
            [
                'success' => true,
                'naics' => '234567',
                'count' => 1,
                'opportunities' => [
                    [
                        'notice_id' => 'DUP-001',
                        'title' => 'Has Date',
                        'lastModifiedDate' => '2025-01-15T10:00:00Z',
                    ],
                ],
            ],
        ];

        $result = $deduplicator->merge($naicsResults);

        expect($result['opportunities'])->toHaveCount(1)
            ->and($result['opportunities'][0]['title'])->toBe('Has Date');
    });

    test('keeps opportunities without notice_id', function () {
        Log::shouldReceive('debug')
            ->twice()
            ->withArgs(function ($message, $context) {
                return $message === 'SAM.gov opportunity without notice_id kept';
            });

        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '123456',
                'count' => 2,
                'opportunities' => [
                    ['title' => 'No Notice ID 1'], // Missing notice_id
                    ['title' => 'No Notice ID 2'], // Missing notice_id
                ],
            ],
        ];

        $result = $deduplicator->merge($naicsResults);

        expect($result['opportunities'])->toHaveCount(2)
            ->and($result['duplicates_removed'])->toBe(0);
    });

    test('keeps opportunities with empty string notice_id', function () {
        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '123456',
                'count' => 2,
                'opportunities' => [
                    ['notice_id' => '', 'title' => 'Empty Notice ID 1'],
                    ['notice_id' => '', 'title' => 'Empty Notice ID 2'],
                ],
            ],
        ];

        $result = $deduplicator->merge($naicsResults);

        expect($result['opportunities'])->toHaveCount(2)
            ->and($result['duplicates_removed'])->toBe(0);
    });

    test('handles null notice_id', function () {
        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '123456',
                'count' => 2,
                'opportunities' => [
                    ['notice_id' => null, 'title' => 'Null Notice ID 1'],
                    ['notice_id' => null, 'title' => 'Null Notice ID 2'],
                ],
            ],
        ];

        $result = $deduplicator->merge($naicsResults);

        expect($result['opportunities'])->toHaveCount(2);
    });
});

describe('high deduplication rate warning', function () {
    test('logs warning when deduplication rate exceeds 20%', function () {
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === 'High deduplication rate detected'
                    && $context['service'] === 'SamOpportunityDeduplicator'
                    && $context['deduplication_rate'] === '25%' // round(25.0, 2) concatenated = '25%'
                    && $context['duplicates_removed'] === 1
                    && $context['total_before_dedup'] === 4
                    && isset($context['suggestion']);
            });

        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '123456',
                'count' => 2,
                'opportunities' => [
                    ['notice_id' => 'A', 'title' => 'A'],
                    ['notice_id' => 'B', 'title' => 'B'],
                ],
            ],
            [
                'success' => true,
                'naics' => '234567',
                'count' => 2,
                'opportunities' => [
                    ['notice_id' => 'A', 'title' => 'A Duplicate'], // 25% duplication
                    ['notice_id' => 'C', 'title' => 'C'],
                ],
            ],
        ];

        $deduplicator->merge($naicsResults);
    });

    test('does not log warning when deduplication rate is below 20%', function () {
        Log::shouldReceive('debug')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->never();

        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '123456',
                'count' => 5,
                'opportunities' => [
                    ['notice_id' => 'A', 'title' => 'A'],
                    ['notice_id' => 'B', 'title' => 'B'],
                    ['notice_id' => 'C', 'title' => 'C'],
                    ['notice_id' => 'D', 'title' => 'D'],
                    ['notice_id' => 'E', 'title' => 'E'],
                ],
            ],
            [
                'success' => true,
                'naics' => '234567',
                'count' => 1,
                'opportunities' => [
                    ['notice_id' => 'A', 'title' => 'A Duplicate'], // Only 16.7% duplication
                ],
            ],
        ];

        $deduplicator->merge($naicsResults);
    });
});

describe('statistics', function () {
    test('calculates deduplication statistics correctly', function () {
        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '123456',
                'count' => 3,
                'opportunities' => [
                    ['notice_id' => 'A', 'title' => 'A'],
                    ['notice_id' => 'B', 'title' => 'B'],
                    ['notice_id' => 'C', 'title' => 'C'],
                ],
            ],
            [
                'success' => true,
                'naics' => '234567',
                'count' => 2,
                'opportunities' => [
                    ['notice_id' => 'A', 'title' => 'A Duplicate'],
                    ['notice_id' => 'D', 'title' => 'D'],
                ],
            ],
        ];

        $result = $deduplicator->merge($naicsResults);
        $stats = $deduplicator->getStats($result);

        expect($stats)
            ->toHaveKey('total_before_dedup')
            ->toHaveKey('total_after_dedup')
            ->toHaveKey('duplicates_removed')
            ->toHaveKey('deduplication_rate')
            ->toHaveKey('naics_queried_count')
            ->toHaveKey('naics_succeeded_count')
            ->toHaveKey('naics_failed_count')
            ->and($stats['total_before_dedup'])->toBe(5)
            ->and($stats['total_after_dedup'])->toBe(4)
            ->and($stats['duplicates_removed'])->toBe(1)
            ->and($stats['deduplication_rate'])->toBe(20.0)
            ->and($stats['naics_queried_count'])->toBe(2)
            ->and($stats['naics_succeeded_count'])->toBe(2)
            ->and($stats['naics_failed_count'])->toBe(0);
    });

    test('handles zero opportunities gracefully', function () {
        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [];

        $result = $deduplicator->merge($naicsResults);
        $stats = $deduplicator->getStats($result);

        expect($stats['deduplication_rate'])->toBe(0.0);
    });
});

describe('complex scenarios', function () {
    test('handles multiple duplicates of same notice_id', function () {
        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '111111',
                'count' => 1,
                'opportunities' => [
                    [
                        'notice_id' => 'TRIPLE',
                        'title' => 'Version 1',
                        'lastModifiedDate' => '2025-01-10T10:00:00Z',
                    ],
                ],
            ],
            [
                'success' => true,
                'naics' => '222222',
                'count' => 1,
                'opportunities' => [
                    [
                        'notice_id' => 'TRIPLE',
                        'title' => 'Version 2',
                        'lastModifiedDate' => '2025-01-12T10:00:00Z',
                    ],
                ],
            ],
            [
                'success' => true,
                'naics' => '333333',
                'count' => 1,
                'opportunities' => [
                    [
                        'notice_id' => 'TRIPLE',
                        'title' => 'Version 3 (Newest)',
                        'lastModifiedDate' => '2025-01-15T10:00:00Z',
                    ],
                ],
            ],
        ];

        $result = $deduplicator->merge($naicsResults);

        expect($result['opportunities'])->toHaveCount(1)
            ->and($result['opportunities'][0]['title'])->toBe('Version 3 (Newest)')
            ->and($result['duplicates_removed'])->toBe(2);
    });

    test('preserves array keys after deduplication', function () {
        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '123456',
                'count' => 2,
                'opportunities' => [
                    ['notice_id' => 'A', 'title' => 'A'],
                    ['notice_id' => 'B', 'title' => 'B'],
                ],
            ],
        ];

        $result = $deduplicator->merge($naicsResults);

        // Array should be re-indexed (0, 1, 2, ...) not (0, 2, 5, ...)
        expect(array_keys($result['opportunities']))->toBe([0, 1]);
    });

    test('handles mixed scenarios with duplicates and unique opportunities', function () {
        $deduplicator = new SamOpportunityDeduplicator;

        $naicsResults = [
            [
                'success' => true,
                'naics' => '123456',
                'count' => 4,
                'opportunities' => [
                    ['notice_id' => 'UNIQUE-1', 'title' => 'Unique 1'],
                    ['notice_id' => 'DUP-1', 'title' => 'Dup 1 Old', 'lastModifiedDate' => '2025-01-10'],
                    ['notice_id' => 'UNIQUE-2', 'title' => 'Unique 2'],
                    ['notice_id' => null, 'title' => 'No ID'],
                ],
            ],
            [
                'success' => false,
                'naics' => '234567',
                'error' => 'Failed',
            ],
            [
                'success' => true,
                'naics' => '345678',
                'count' => 2,
                'opportunities' => [
                    ['notice_id' => 'DUP-1', 'title' => 'Dup 1 New', 'lastModifiedDate' => '2025-01-15'],
                    ['notice_id' => 'UNIQUE-3', 'title' => 'Unique 3'],
                ],
            ],
        ];

        $result = $deduplicator->merge($naicsResults);

        expect($result['opportunities'])->toHaveCount(5)
            ->and($result['duplicates_removed'])->toBe(1)
            ->and($result['naics_succeeded'])->toHaveCount(2)
            ->and($result['naics_failed'])->toHaveCount(1);

        // Verify the newer duplicate was kept
        $dupOpp = collect($result['opportunities'])->firstWhere('notice_id', 'DUP-1');
        expect($dupOpp['title'])->toBe('Dup 1 New');
    });
});
