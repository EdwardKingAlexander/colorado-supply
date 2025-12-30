<?php

declare(strict_types=1);

use App\Support\SamPerformanceLogger;
use Illuminate\Support\Facades\Log;

describe('performance logging', function () {
    test('logs performance metrics with structured data', function () {
        Log::shouldReceive('info')
            ->once()
            ->with('SAM.gov fetch performance', \Mockery::on(function ($data) {
                return $data['service'] === 'SamPerformanceLogger'
                    && isset($data['total_duration_ms'])
                    && isset($data['cache_hit_rate'])
                    && isset($data['naics_queried']);
            }));

        $logger = new SamPerformanceLogger;

        $logger->log([
            'total_duration_ms' => 1500,
            'cache_hits' => 3,
            'cache_misses' => 2,
            'naics_queried' => 5,
            'naics_succeeded' => 5,
            'naics_failed' => 0,
        ]);
    });

    test('logs warning for poor performance', function () {
        Log::shouldReceive('warning')
            ->once()
            ->with('SAM.gov fetch performance warning', \Mockery::on(function ($data) {
                return $data['warning_reason'] === 'Low cache hit rate'
                    && isset($data['service']);
            }));

        $logger = new SamPerformanceLogger;

        $logger->logWarning([
            'total_duration_ms' => 1500,
            'cache_hits' => 0,
            'cache_misses' => 5,
        ], 'Low cache hit rate');
    });

    test('logs daily summary with aggregated metrics', function () {
        Log::shouldReceive('info')
            ->once()
            ->with('SAM.gov daily performance summary', \Mockery::on(function ($data) {
                return $data['period'] === 'daily'
                    && $data['total_fetches'] === 10
                    && $data['total_opportunities'] === 500;
            }));

        $logger = new SamPerformanceLogger;

        $logger->logDailySummary([
            'total_fetches' => 10,
            'total_opportunities' => 500,
            'total_api_calls' => 25,
            'total_cache_hits' => 15,
            'average_duration_ms' => 1234,
            'average_cache_hit_rate' => '60%',
            'total_failures' => 2,
        ]);
    });
});

describe('cache hit rate calculation', function () {
    test('calculates cache hit rate correctly', function () {
        $logger = new SamPerformanceLogger;

        $summary = $logger->getSummary([
            'cache_hits' => 7,
            'cache_misses' => 3,
            'total_duration_ms' => 1000,
            'naics_queried' => 10,
        ]);

        expect($summary['cache']['hit_rate'])->toBe('70%');
    });

    test('handles zero total queries gracefully', function () {
        $logger = new SamPerformanceLogger;

        $summary = $logger->getSummary([
            'cache_hits' => 0,
            'cache_misses' => 0,
        ]);

        expect($summary['cache']['hit_rate'])->toBe('0%');
    });

    test('rounds cache hit rate to one decimal place', function () {
        $logger = new SamPerformanceLogger;

        $summary = $logger->getSummary([
            'cache_hits' => 2,
            'cache_misses' => 1,
            'total_duration_ms' => 1000,
            'naics_queried' => 3,
        ]);

        expect($summary['cache']['hit_rate'])->toBe('66.7%');
    });
});

describe('deduplication rate calculation', function () {
    test('calculates deduplication rate correctly', function () {
        $logger = new SamPerformanceLogger;

        $summary = $logger->getSummary([
            'count_before_dedup' => 100,
            'total_after_dedup' => 85,
            'duplicates_removed' => 15,
        ]);

        expect($summary['opportunities']['deduplication_rate'])->toBe('15%');
    });

    test('handles zero opportunities before dedup gracefully', function () {
        $logger = new SamPerformanceLogger;

        $summary = $logger->getSummary([
            'count_before_dedup' => 0,
            'duplicates_removed' => 0,
        ]);

        expect($summary['opportunities']['deduplication_rate'])->toBe('0%');
    });

    test('rounds deduplication rate to one decimal place', function () {
        $logger = new SamPerformanceLogger;

        $summary = $logger->getSummary([
            'count_before_dedup' => 100,
            'duplicates_removed' => 33,
        ]);

        expect($summary['opportunities']['deduplication_rate'])->toBe('33%');
    });
});

describe('average per NAICS calculation', function () {
    test('calculates average duration per NAICS', function () {
        $logger = new SamPerformanceLogger;

        $summary = $logger->getSummary([
            'total_duration_ms' => 5000,
            'naics_queried' => 5,
        ]);

        expect($summary['duration']['average_per_naics_ms'])->toBe(1000);
    });

    test('handles zero NAICS queried gracefully', function () {
        $logger = new SamPerformanceLogger;

        $summary = $logger->getSummary([
            'total_duration_ms' => 5000,
            'naics_queried' => 0,
        ]);

        expect($summary['duration']['average_per_naics_ms'])->toBe(0);
    });

    test('rounds average duration to nearest integer', function () {
        $logger = new SamPerformanceLogger;

        $summary = $logger->getSummary([
            'total_duration_ms' => 1234,
            'naics_queried' => 3,
        ]);

        expect($summary['duration']['average_per_naics_ms'])->toBe(411);
    });
});

describe('performance analysis', function () {
    test('detects low cache hit rate warning', function () {
        $logger = new SamPerformanceLogger;

        $warnings = $logger->analyzePerformance([
            'cache_hits' => 1,
            'cache_misses' => 9,
            'total_duration_ms' => 1000,
            'naics_queried' => 10,
            'naics_succeeded' => 10,
            'naics_failed' => 0,
            'count_before_dedup' => 100,
            'duplicates_removed' => 5,
        ]);

        expect($warnings)->toHaveCount(1)
            ->and($warnings[0])->toContain('Low cache hit rate');
    });

    test('does not warn for low cache hit rate with few queries', function () {
        $logger = new SamPerformanceLogger;

        $warnings = $logger->analyzePerformance([
            'cache_hits' => 0,
            'cache_misses' => 2, // Only 2 total queries
            'total_duration_ms' => 500,
            'naics_queried' => 2,
            'naics_succeeded' => 2,
            'naics_failed' => 0,
            'count_before_dedup' => 10,
            'duplicates_removed' => 0,
        ]);

        expect($warnings)->toBeEmpty();
    });

    test('detects high deduplication rate warning', function () {
        $logger = new SamPerformanceLogger;

        $warnings = $logger->analyzePerformance([
            'cache_hits' => 5,
            'cache_misses' => 5,
            'total_duration_ms' => 1000,
            'naics_queried' => 10,
            'naics_succeeded' => 10,
            'naics_failed' => 0,
            'count_before_dedup' => 100,
            'duplicates_removed' => 40, // 40% deduplication
        ]);

        expect($warnings)->toHaveCount(1)
            ->and($warnings[0])->toContain('High deduplication rate');
    });

    test('detects slow queries warning', function () {
        $logger = new SamPerformanceLogger;

        $warnings = $logger->analyzePerformance([
            'cache_hits' => 0,
            'cache_misses' => 5,
            'total_duration_ms' => 20000, // 20 seconds for 5 NAICS = 4000ms each
            'naics_queried' => 5,
            'naics_succeeded' => 5,
            'naics_failed' => 0,
            'count_before_dedup' => 100,
            'duplicates_removed' => 10,
        ]);

        expect($warnings)->toHaveCount(1)
            ->and($warnings[0])->toContain('Slow queries detected');
    });

    test('detects high failure rate warning', function () {
        $logger = new SamPerformanceLogger;

        $warnings = $logger->analyzePerformance([
            'cache_hits' => 5,
            'cache_misses' => 5,
            'total_duration_ms' => 2000, // 200ms per NAICS - not slow
            'naics_queried' => 10,
            'naics_succeeded' => 7,
            'naics_failed' => 3, // 30% failure rate
            'count_before_dedup' => 70,
            'duplicates_removed' => 5, // <30% dedup - OK
        ]);

        expect($warnings)->toHaveCount(1)
            ->and($warnings[0])->toContain('High failure rate');
    });

    test('detects multiple warnings when multiple issues exist', function () {
        $logger = new SamPerformanceLogger;

        $warnings = $logger->analyzePerformance([
            'cache_hits' => 1,
            'cache_misses' => 9, // 10% cache hit rate
            'total_duration_ms' => 40000, // 4000ms per NAICS
            'naics_queried' => 10,
            'naics_succeeded' => 7,
            'naics_failed' => 3, // 30% failure rate
            'count_before_dedup' => 100,
            'duplicates_removed' => 40, // 40% deduplication
        ]);

        $warningsString = implode(' ', $warnings);

        expect($warnings)->toHaveCount(4)
            ->and($warningsString)->toContain('Low cache hit rate')
            ->and($warningsString)->toContain('High deduplication rate')
            ->and($warningsString)->toContain('Slow queries')
            ->and($warningsString)->toContain('High failure rate');
    });

    test('returns empty array when no performance issues detected', function () {
        $logger = new SamPerformanceLogger;

        $warnings = $logger->analyzePerformance([
            'cache_hits' => 8,
            'cache_misses' => 2,
            'total_duration_ms' => 2000,
            'naics_queried' => 10,
            'naics_succeeded' => 10,
            'naics_failed' => 0,
            'count_before_dedup' => 100,
            'duplicates_removed' => 10,
        ]);

        expect($warnings)->toBeEmpty();
    });
});

describe('summary generation', function () {
    test('generates complete summary with all metrics', function () {
        $logger = new SamPerformanceLogger;

        $summary = $logger->getSummary([
            'total_duration_ms' => 5000,
            'cache_hits' => 7,
            'cache_misses' => 3,
            'naics_queried' => 10,
            'naics_succeeded' => 9,
            'naics_failed' => 1,
            'count_before_dedup' => 120,
            'total_after_dedup' => 100,
            'duplicates_removed' => 20,
        ]);

        expect($summary)->toHaveKeys(['duration', 'cache', 'naics', 'opportunities'])
            ->and($summary['duration']['total_ms'])->toBe(5000)
            ->and($summary['duration']['average_per_naics_ms'])->toBe(500)
            ->and($summary['cache']['hits'])->toBe(7)
            ->and($summary['cache']['misses'])->toBe(3)
            ->and($summary['cache']['hit_rate'])->toBe('70%')
            ->and($summary['naics']['queried'])->toBe(10)
            ->and($summary['naics']['succeeded'])->toBe(9)
            ->and($summary['naics']['failed'])->toBe(1)
            ->and($summary['opportunities']['before_dedup'])->toBe(120)
            ->and($summary['opportunities']['after_dedup'])->toBe(100)
            ->and($summary['opportunities']['duplicates_removed'])->toBe(20)
            ->and($summary['opportunities']['deduplication_rate'])->toBe('16.7%');
    });

    test('handles missing metrics gracefully with defaults', function () {
        $logger = new SamPerformanceLogger;

        $summary = $logger->getSummary([]);

        expect($summary['duration']['total_ms'])->toBe(0)
            ->and($summary['duration']['average_per_naics_ms'])->toBe(0)
            ->and($summary['cache']['hits'])->toBe(0)
            ->and($summary['cache']['misses'])->toBe(0)
            ->and($summary['cache']['hit_rate'])->toBe('0%')
            ->and($summary['naics']['queried'])->toBe(0)
            ->and($summary['naics']['succeeded'])->toBe(0)
            ->and($summary['naics']['failed'])->toBe(0)
            ->and($summary['opportunities']['before_dedup'])->toBe(0)
            ->and($summary['opportunities']['after_dedup'])->toBe(0)
            ->and($summary['opportunities']['duplicates_removed'])->toBe(0)
            ->and($summary['opportunities']['deduplication_rate'])->toBe('0%');
    });
});

describe('formatted metrics', function () {
    test('includes all required fields in formatted output', function () {
        Log::shouldReceive('info')
            ->once()
            ->with('SAM.gov fetch performance', \Mockery::on(function ($data) {
                return $data['service'] === 'SamPerformanceLogger'
                    && isset($data['total_duration_ms'])
                    && isset($data['cache_hits'])
                    && isset($data['cache_misses'])
                    && isset($data['cache_hit_rate'])
                    && isset($data['api_calls'])
                    && isset($data['total_opportunities_before_dedup'])
                    && isset($data['total_opportunities_after_dedup'])
                    && isset($data['duplicates_removed'])
                    && isset($data['deduplication_rate'])
                    && isset($data['naics_queried'])
                    && isset($data['naics_succeeded'])
                    && isset($data['naics_failed']);
            }));

        $logger = new SamPerformanceLogger;

        $logger->log([
            'total_duration_ms' => 1500,
            'cache_hits' => 3,
            'cache_misses' => 2,
            'naics_queried' => 5,
            'naics_succeeded' => 5,
            'naics_failed' => 0,
            'count_before_dedup' => 100,
            'total_after_dedup' => 95,
            'duplicates_removed' => 5,
        ]);
    });

    test('api_calls equals cache_misses', function () {
        Log::shouldReceive('info')
            ->once()
            ->with('SAM.gov fetch performance', \Mockery::on(function ($data) {
                return $data['api_calls'] === 7
                    && $data['cache_misses'] === 7;
            }));

        $logger = new SamPerformanceLogger;

        $logger->log([
            'cache_hits' => 3,
            'cache_misses' => 7,
        ]);
    });
});
