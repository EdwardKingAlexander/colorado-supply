<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Centralized performance logging and metrics collection for SAM.gov fetch operations.
 *
 * This service:
 * - Logs performance metrics to structured log format
 * - Tracks cache efficiency (hit rate, miss rate)
 * - Tracks API call patterns (total calls, failures, retries)
 * - Tracks deduplication stats (duplicates removed, rate)
 * - Query duration tracking
 * - Provides summary statistics
 */
class SamPerformanceLogger
{
    /**
     * Log performance metrics for a SAM.gov fetch operation.
     *
     * @param  array  $metrics  Performance metrics
     */
    public function log(array $metrics): void
    {
        $logData = $this->formatMetrics($metrics);

        Log::info('SAM.gov fetch performance', $logData);
    }

    /**
     * Log a warning for poor performance.
     *
     * @param  array  $metrics  Performance metrics
     * @param  string  $reason  Reason for warning
     */
    public function logWarning(array $metrics, string $reason): void
    {
        $logData = $this->formatMetrics($metrics);
        $logData['warning_reason'] = $reason;

        Log::warning('SAM.gov fetch performance warning', $logData);
    }

    /**
     * Log a daily summary of performance metrics.
     *
     * @param  array  $dailyMetrics  Aggregated daily metrics
     */
    public function logDailySummary(array $dailyMetrics): void
    {
        $summary = [
            'service' => 'SamPerformanceLogger',
            'period' => 'daily',
            'total_fetches' => $dailyMetrics['total_fetches'] ?? 0,
            'total_opportunities' => $dailyMetrics['total_opportunities'] ?? 0,
            'total_api_calls' => $dailyMetrics['total_api_calls'] ?? 0,
            'total_cache_hits' => $dailyMetrics['total_cache_hits'] ?? 0,
            'average_duration_ms' => $dailyMetrics['average_duration_ms'] ?? 0,
            'average_cache_hit_rate' => $dailyMetrics['average_cache_hit_rate'] ?? '0%',
            'total_failures' => $dailyMetrics['total_failures'] ?? 0,
        ];

        Log::info('SAM.gov daily performance summary', $summary);
    }

    /**
     * Format metrics into structured log data.
     *
     * @param  array  $metrics  Raw metrics
     * @return array Formatted log data
     */
    protected function formatMetrics(array $metrics): array
    {
        return [
            'service' => 'SamPerformanceLogger',
            'total_duration_ms' => $metrics['total_duration_ms'] ?? 0,
            'cache_hits' => $metrics['cache_hits'] ?? 0,
            'cache_misses' => $metrics['cache_misses'] ?? 0,
            'cache_hit_rate' => $this->calculateCacheHitRate($metrics),
            'api_calls' => $metrics['cache_misses'] ?? 0, // cache misses = API calls
            'total_opportunities_before_dedup' => $metrics['count_before_dedup'] ?? 0,
            'total_opportunities_after_dedup' => $metrics['total_after_dedup'] ?? 0,
            'duplicates_removed' => $metrics['duplicates_removed'] ?? 0,
            'deduplication_rate' => $this->calculateDeduplicationRate($metrics),
            'naics_queried' => $metrics['naics_queried'] ?? 0,
            'naics_succeeded' => $metrics['naics_succeeded'] ?? 0,
            'naics_failed' => $metrics['naics_failed'] ?? 0,
        ];
    }

    /**
     * Calculate cache hit rate as percentage string.
     *
     * @param  array  $metrics  Metrics containing cache_hits and cache_misses
     * @return string Cache hit rate (e.g., "75.5%")
     */
    protected function calculateCacheHitRate(array $metrics): string
    {
        $hits = $metrics['cache_hits'] ?? 0;
        $misses = $metrics['cache_misses'] ?? 0;
        $total = $hits + $misses;

        if ($total === 0) {
            return '0%';
        }

        $rate = ($hits / $total) * 100;

        return round($rate, 1).'%';
    }

    /**
     * Calculate deduplication rate as percentage string.
     *
     * @param  array  $metrics  Metrics containing duplicates_removed and count_before_dedup
     * @return string Deduplication rate (e.g., "15.2%")
     */
    protected function calculateDeduplicationRate(array $metrics): string
    {
        $removed = $metrics['duplicates_removed'] ?? 0;
        $before = $metrics['count_before_dedup'] ?? 0;

        if ($before === 0) {
            return '0%';
        }

        $rate = ($removed / $before) * 100;

        return round($rate, 1).'%';
    }

    /**
     * Analyze metrics and detect performance issues.
     *
     * Returns array of warnings if any performance issues detected.
     *
     * @param  array  $metrics  Performance metrics
     * @return array Array of warning messages
     */
    public function analyzePerformance(array $metrics): array
    {
        $warnings = [];

        // Check for low cache hit rate
        $cacheHitRate = $this->parseCacheHitRate($metrics);
        if ($cacheHitRate < 30 && ($metrics['cache_hits'] ?? 0) + ($metrics['cache_misses'] ?? 0) > 5) {
            $warnings[] = 'Low cache hit rate ('.round($cacheHitRate, 1).'%) - consider reviewing cache TTL or query patterns';
        }

        // Check for high deduplication rate
        $dedupRate = $this->parseDeduplicationRate($metrics);
        if ($dedupRate > 30) {
            $warnings[] = 'High deduplication rate ('.round($dedupRate, 1).'%) - consider reviewing NAICS code selection for overlap';
        }

        // Check for slow queries
        $duration = $metrics['total_duration_ms'] ?? 0;
        $naicsCount = $metrics['naics_queried'] ?? 1;
        $avgPerNaics = $naicsCount > 0 ? $duration / $naicsCount : $duration;
        if ($avgPerNaics > 3000) {
            $warnings[] = 'Slow queries detected (avg '.round($avgPerNaics).'ms per NAICS) - check network or API performance';
        }

        // Check for high failure rate
        $succeeded = $metrics['naics_succeeded'] ?? 0;
        $failed = $metrics['naics_failed'] ?? 0;
        $total = $succeeded + $failed;
        if ($total > 0 && ($failed / $total) > 0.2) {
            $failureRate = round(($failed / $total) * 100, 1);
            $warnings[] = 'High failure rate ('.$failureRate.'%) - check API key and network connectivity';
        }

        return $warnings;
    }

    /**
     * Parse cache hit rate from metrics.
     *
     * @param  array  $metrics  Metrics
     * @return float Cache hit rate (0-100)
     */
    protected function parseCacheHitRate(array $metrics): float
    {
        $hits = $metrics['cache_hits'] ?? 0;
        $misses = $metrics['cache_misses'] ?? 0;
        $total = $hits + $misses;

        if ($total === 0) {
            return 0.0;
        }

        return ($hits / $total) * 100;
    }

    /**
     * Parse deduplication rate from metrics.
     *
     * @param  array  $metrics  Metrics
     * @return float Deduplication rate (0-100)
     */
    protected function parseDeduplicationRate(array $metrics): float
    {
        $removed = $metrics['duplicates_removed'] ?? 0;
        $before = $metrics['count_before_dedup'] ?? 0;

        if ($before === 0) {
            return 0.0;
        }

        return ($removed / $before) * 100;
    }

    /**
     * Get formatted summary statistics from metrics.
     *
     * @param  array  $metrics  Performance metrics
     * @return array Formatted summary
     */
    public function getSummary(array $metrics): array
    {
        return [
            'duration' => [
                'total_ms' => $metrics['total_duration_ms'] ?? 0,
                'average_per_naics_ms' => $this->calculateAveragePerNaics($metrics),
            ],
            'cache' => [
                'hits' => $metrics['cache_hits'] ?? 0,
                'misses' => $metrics['cache_misses'] ?? 0,
                'hit_rate' => $this->calculateCacheHitRate($metrics),
            ],
            'naics' => [
                'queried' => $metrics['naics_queried'] ?? 0,
                'succeeded' => $metrics['naics_succeeded'] ?? 0,
                'failed' => $metrics['naics_failed'] ?? 0,
            ],
            'opportunities' => [
                'before_dedup' => $metrics['count_before_dedup'] ?? 0,
                'after_dedup' => $metrics['total_after_dedup'] ?? 0,
                'duplicates_removed' => $metrics['duplicates_removed'] ?? 0,
                'deduplication_rate' => $this->calculateDeduplicationRate($metrics),
            ],
        ];
    }

    /**
     * Calculate average duration per NAICS.
     *
     * @param  array  $metrics  Metrics
     * @return int Average milliseconds per NAICS
     */
    protected function calculateAveragePerNaics(array $metrics): int
    {
        $duration = $metrics['total_duration_ms'] ?? 0;
        $naicsCount = $metrics['naics_queried'] ?? 0;

        if ($naicsCount === 0) {
            return 0;
        }

        return (int) round($duration / $naicsCount);
    }
}
