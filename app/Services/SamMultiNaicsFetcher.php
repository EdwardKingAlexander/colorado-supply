<?php

namespace App\Services;

use App\Support\SamOpportunitiesCache;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates sequential fetching of SAM.gov opportunities across multiple NAICS codes.
 *
 * This service:
 * - Iterates through NAICS codes sequentially (v2 API limitation)
 * - Checks cache before making API calls
 * - Handles rate limiting with delays between requests
 * - Tolerates partial failures (some NAICS succeed, others fail)
 * - Tracks performance metrics (cache hits, API calls, timing)
 * - Returns array of per-NAICS results for deduplication
 */
class SamMultiNaicsFetcher
{
    /**
     * Base delay between NAICS queries in microseconds (500ms).
     */
    protected const BASE_DELAY_MICROSECONDS = 500000;

    /**
     * Maximum random jitter to add to delay in microseconds (200ms).
     */
    protected const JITTER_MICROSECONDS = 200000;

    /**
     * Cache manager for per-NAICS responses.
     */
    protected SamOpportunitiesCache $cache;

    /**
     * API client for SAM.gov v2.
     */
    protected SamApiClient $apiClient;

    /**
     * Create a new multi-NAICS fetcher instance.
     */
    public function __construct(?SamOpportunitiesCache $cache = null, ?SamApiClient $apiClient = null)
    {
        $this->cache = $cache ?? new SamOpportunitiesCache;
        $this->apiClient = $apiClient ?? new SamApiClient;
    }

    /**
     * Fetch opportunities for multiple NAICS codes sequentially.
     *
     * @param  array  $naicsCodes  Array of 6-digit NAICS codes
     * @param  array  $params  Resolved query parameters from SamParameterResolver
     * @param  string  $apiKey  SAM.gov API key
     * @return array Array of per-NAICS results with performance metrics
     */
    public function fetchAll(array $naicsCodes, array $params, string $apiKey): array
    {
        $startTime = microtime(true);

        $results = [];
        $performanceMetrics = [];
        $cacheHits = 0;
        $cacheMisses = 0;

        $totalNaics = count($naicsCodes);

        foreach ($naicsCodes as $index => $naicsCode) {
            $naicsStartTime = microtime(true);

            // Allow callers to force a fresh pull
            $skipCache = $params['clearCache'] ?? false;

            // If clearCache is requested, remove any existing entry before checking
            if ($skipCache) {
                $this->cache->forget($naicsCode, $params);
            }

            // Check cache first (unless skipping)
            $cachedResult = $skipCache ? null : $this->cache->get($naicsCode, $params);

            if ($cachedResult !== null) {
                // Cache hit
                $results[] = $cachedResult;
                $cacheHits++;

                $duration = round((microtime(true) - $naicsStartTime) * 1000);

                $performanceMetrics[] = [
                    'naics' => $naicsCode,
                    'duration_ms' => $duration,
                    'count' => $cachedResult['count'] ?? 0,
                    'cached' => true,
                ];

                Log::debug('SAM.gov NAICS query (cache hit)', [
                    'service' => 'SamMultiNaicsFetcher',
                    'naics' => $naicsCode,
                    'duration_ms' => $duration,
                    'count' => $cachedResult['count'] ?? 0,
                    'cached' => true,
                ]);
            } else {
                // Cache miss - fetch from API
                $cacheMisses++;

                $result = $this->apiClient->fetch($naicsCode, $params, $apiKey);

                $duration = round((microtime(true) - $naicsStartTime) * 1000);

                // Store successful results in cache
                if ($result['success'] ?? false) {
                    $this->cache->put($naicsCode, $params, $result);

                    $performanceMetrics[] = [
                        'naics' => $naicsCode,
                        'duration_ms' => $duration,
                        'count' => $result['count'] ?? 0,
                        'cached' => false,
                    ];

                    Log::debug('SAM.gov NAICS query succeeded', [
                        'service' => 'SamMultiNaicsFetcher',
                        'naics' => $naicsCode,
                        'duration_ms' => $duration,
                        'count' => $result['count'] ?? 0,
                        'cached' => false,
                    ]);
                } else {
                    // Log failure
                    Log::warning('SAM.gov query failed for NAICS', [
                        'service' => 'SamMultiNaicsFetcher',
                        'naics' => $naicsCode,
                        'error' => $result['error'] ?? 'Unknown error',
                        'status_code' => $result['status_code'] ?? null,
                        'error_category' => $this->categorizeError($result),
                    ]);
                }

                $results[] = $result;
            }

            // Add delay between requests (except after last one)
            if ($index < $totalNaics - 1) {
                $this->addRateLimitDelay();
            }
        }

        $totalDuration = round((microtime(true) - $startTime) * 1000);

        return [
            'results' => $results,
            'performance' => [
                'total_duration_ms' => $totalDuration,
                'cache_hits' => $cacheHits,
                'cache_misses' => $cacheMisses,
                'per_naics' => $performanceMetrics,
            ],
        ];
    }

    /**
     * Add rate-limiting delay between NAICS queries.
     *
     * Uses base delay + random jitter to prevent thundering herd.
     */
    protected function addRateLimitDelay(): void
    {
        $baseDelay = self::BASE_DELAY_MICROSECONDS;
        $jitter = rand(0, self::JITTER_MICROSECONDS);
        $totalDelay = $baseDelay + $jitter;

        usleep($totalDelay);
    }

    /**
     * Categorize error from API response.
     *
     * @param  array  $result  API response
     * @return string Error category
     */
    protected function categorizeError(array $result): string
    {
        $statusCode = $result['status_code'] ?? null;

        return match ($statusCode) {
            429 => 'rate_limit',
            401 => 'authentication',
            404 => 'endpoint_not_found',
            500, 502, 503, 504 => 'server_error',
            null => 'network_error',
            default => 'api_error',
        };
    }

    /**
     * Get summary statistics from fetch results.
     *
     * @param  array  $fetchResults  Results from fetchAll()
     * @return array Summary statistics
     */
    public function getSummary(array $fetchResults): array
    {
        $results = $fetchResults['results'] ?? [];
        $performance = $fetchResults['performance'] ?? [];

        $successfulResults = array_filter($results, fn ($r) => $r['success'] ?? false);
        $failedResults = array_filter($results, fn ($r) => ! ($r['success'] ?? false));

        $totalQueries = $performance['cache_hits'] + $performance['cache_misses'];
        $cacheHitRate = $totalQueries > 0
            ? round(($performance['cache_hits'] / $totalQueries) * 100, 1)
            : 0;

        return [
            'total_naics_queried' => count($results),
            'successful_naics' => count($successfulResults),
            'failed_naics' => count($failedResults),
            'cache_hit_rate' => $cacheHitRate.'%',
            'total_duration_ms' => $performance['total_duration_ms'] ?? 0,
            'cache_hits' => $performance['cache_hits'] ?? 0,
            'cache_misses' => $performance['cache_misses'] ?? 0,
        ];
    }

    /**
     * Extract failed NAICS details from fetch results.
     *
     * @param  array  $fetchResults  Results from fetchAll()
     * @return array Array of failed NAICS with error details
     */
    public function getFailedNaics(array $fetchResults): array
    {
        $results = $fetchResults['results'] ?? [];

        $failed = [];

        foreach ($results as $result) {
            if (! ($result['success'] ?? false)) {
                $failed[] = [
                    'naics' => $result['naics'] ?? 'unknown',
                    'error' => $result['error'] ?? 'Unknown error',
                    'status_code' => $result['status_code'] ?? null,
                    'error_type' => $result['error_type'] ?? null,
                    'response_body' => $result['response_body'] ?? null,
                ];
            }
        }

        return $failed;
    }
}
