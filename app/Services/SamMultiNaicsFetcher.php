<?php

namespace App\Services;

use App\Support\SamOpportunitiesCache;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates sequential fetching of SAM.gov opportunities across multiple NAICS codes
 * and, optionally, PSC codes (one query per code).
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
     * Fetch opportunities for multiple NAICS codes, and optionally PSC codes, sequentially.
     *
     * Each code is one search term ("naics:332919", "psc:53") and one SAM.gov
     * query: NAICS via `ncode`, PSC via `ccode`. A notice found by several terms
     * appears in several results; the deduplicator collapses it and records
     * every term that found it.
     *
     * @param  array  $naicsCodes  Array of 6-digit NAICS codes
     * @param  array  $params  Resolved query parameters from SamParameterResolver
     * @param  string  $apiKey  SAM.gov API key
     * @param  array  $pscCodes  2- or 4-character PSC codes to search as well
     * @return array Array of per-term results with performance metrics
     */
    public function fetchAll(array $naicsCodes, array $params, string $apiKey, array $pscCodes = []): array
    {
        $startTime = microtime(true);

        $results = [];
        $performanceMetrics = [];
        $cacheHits = 0;
        $cacheMisses = 0;

        $terms = array_merge(
            array_map(fn ($code) => ['dimension' => 'naics', 'code' => (string) $code], array_values($naicsCodes)),
            array_map(fn ($code) => ['dimension' => 'psc', 'code' => (string) $code], array_values($pscCodes)),
        );

        $totalTerms = count($terms);

        foreach ($terms as $index => ['dimension' => $dimension, 'code' => $code]) {
            $termStartTime = microtime(true);
            $term = $dimension.':'.$code;

            // Allow callers to force a fresh pull
            $skipCache = $params['clearCache'] ?? false;

            // If clearCache is requested, remove any existing entry before checking
            if ($skipCache) {
                $this->cache->forget($code, $params, $dimension);
            }

            // Check cache first (unless skipping)
            $cachedResult = $skipCache ? null : $this->cache->get($code, $params, $dimension);

            if ($cachedResult !== null) {
                // Cache hit
                $results[] = $cachedResult;
                $cacheHits++;

                $duration = round((microtime(true) - $termStartTime) * 1000);

                $performanceMetrics[] = [
                    $dimension => $code,
                    'term' => $term,
                    'duration_ms' => $duration,
                    'count' => $cachedResult['count'] ?? 0,
                    'cached' => true,
                ];

                Log::debug('SAM.gov query (cache hit)', [
                    'service' => 'SamMultiNaicsFetcher',
                    $dimension => $code,
                    'duration_ms' => $duration,
                    'count' => $cachedResult['count'] ?? 0,
                    'cached' => true,
                ]);
            } else {
                // Cache miss - fetch from API
                $cacheMisses++;

                $result = $dimension === 'psc'
                    ? $this->apiClient->fetch($code, $params, $apiKey, 'psc')
                    : $this->apiClient->fetch($code, $params, $apiKey);

                $duration = round((microtime(true) - $termStartTime) * 1000);

                if ($result['success'] ?? false) {
                    // Keep metrics for partial responses, but retry them next time.
                    if (! ($result['truncated'] ?? false)) {
                        $this->cache->put($code, $params, $result, $dimension);
                    }

                    $performanceMetrics[] = [
                        $dimension => $code,
                        'term' => $term,
                        'duration_ms' => $duration,
                        'count' => $result['count'] ?? 0,
                        'cached' => false,
                    ];

                    Log::debug('SAM.gov query returned results', [
                        'service' => 'SamMultiNaicsFetcher',
                        $dimension => $code,
                        'duration_ms' => $duration,
                        'count' => $result['count'] ?? 0,
                        'cached' => false,
                        'truncated' => $result['truncated'] ?? false,
                    ]);
                } else {
                    // One failed term never aborts the run (partial success).
                    Log::warning('SAM.gov query failed for '.strtoupper($dimension), [
                        'service' => 'SamMultiNaicsFetcher',
                        $dimension => $code,
                        'error' => $result['error'] ?? 'Unknown error',
                        'status_code' => $result['status_code'] ?? null,
                        'error_category' => $this->categorizeError($result),
                    ]);
                }

                $results[] = $result;

                // A refused key fails every term the same way: stop instead of
                // spending ~100 more requests (and minutes) on the same answer.
                if (in_array($result['error_type'] ?? null, SamApiClient::CREDENTIAL_ERROR_TYPES, true)) {
                    Log::warning('SAM.gov fetch stopped: API key refused', [
                        'service' => 'SamMultiNaicsFetcher',
                        'term' => $term,
                        'error_type' => $result['error_type'],
                        'terms_skipped' => $totalTerms - $index - 1,
                    ]);

                    break;
                }
            }

            // Add delay between requests (except after last one)
            if ($index < $totalTerms - 1) {
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
                // Per search term; the key predates PSC terms.
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
            400 => 'bad_request',
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

        $isPsc = fn ($r) => ($r['dimension'] ?? 'naics') === 'psc';
        $succeeded = fn ($r) => $r['success'] ?? false;

        $naicsResults = array_filter($results, fn ($r) => ! $isPsc($r));
        $pscResults = array_filter($results, $isPsc);

        $totalQueries = $performance['cache_hits'] + $performance['cache_misses'];
        $cacheHitRate = $totalQueries > 0
            ? round(($performance['cache_hits'] / $totalQueries) * 100, 1)
            : 0;

        return [
            // NAICS-only counts, as before; PSC and whole-run counts sit alongside.
            'total_naics_queried' => count($naicsResults),
            'successful_naics' => count(array_filter($naicsResults, $succeeded)),
            'failed_naics' => count(array_filter($naicsResults, fn ($r) => ! $succeeded($r))),
            'total_psc_queried' => count($pscResults),
            'successful_psc' => count(array_filter($pscResults, $succeeded)),
            'failed_psc' => count(array_filter($pscResults, fn ($r) => ! $succeeded($r))),
            'total_terms_queried' => count($results),
            'failed_terms' => count(array_filter($results, fn ($r) => ! $succeeded($r))),
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
                    // PSC failures report their term ("psc:53") so the panel's failure list still names them.
                    'naics' => $result['naics'] ?? $result['term'] ?? 'unknown',
                    'term' => $result['term'] ?? null,
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
