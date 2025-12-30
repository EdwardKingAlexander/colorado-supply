<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Manages per-NAICS caching for SAM.gov opportunities with 15-minute TTL.
 *
 * This cache layer:
 * - Stores responses per-NAICS code to enable partial cache hits
 * - Uses 15-minute TTL to balance freshness with API rate limits
 * - Gracefully handles cache driver failures
 * - Generates cache keys from NAICS + query parameters
 */
class SamOpportunitiesCache
{
    /**
     * Cache TTL in seconds (15 minutes).
     */
    protected const TTL = 900;

    /**
     * Cache key prefix for SAM opportunities.
     */
    // Bump prefix to invalidate stale caches created before the NAICS param fix.
    protected const PREFIX = 'sam_opp_v3';

    /**
     * Get cached response for a NAICS code and query parameters.
     *
     * @param  string  $naicsCode  6-digit NAICS code
     * @param  array  $params  Resolved query parameters
     * @return array|null Cached response or null if cache miss
     */
    public function get(string $naicsCode, array $params): ?array
    {
        try {
            $cacheKey = $this->buildCacheKey($naicsCode, $params);

            $cached = Cache::get($cacheKey);

            if ($cached !== null) {
                Log::debug('SAM.gov cache hit', [
                    'cache' => 'SamOpportunitiesCache',
                    'naics' => $naicsCode,
                    'cache_key' => $cacheKey,
                ]);

                // Ensure cached flag is set to true when retrieved
                if (is_array($cached)) {
                    $cached['cached'] = true;
                }

                return $cached;
            }

            Log::debug('SAM.gov cache miss', [
                'cache' => 'SamOpportunitiesCache',
                'naics' => $naicsCode,
                'cache_key' => $cacheKey,
            ]);

            return null;
        } catch (\Exception $e) {
            // Gracefully handle cache failures - don't break the application
            Log::warning('SAM.gov cache get failed', [
                'cache' => 'SamOpportunitiesCache',
                'naics' => $naicsCode,
                'error' => $e->getMessage(),
                'error_category' => 'cache_error',
            ]);

            return null;
        }
    }

    /**
     * Store response in cache for a NAICS code and query parameters.
     *
     * @param  string  $naicsCode  6-digit NAICS code
     * @param  array  $params  Resolved query parameters
     * @param  array  $response  API response to cache
     * @return bool True if cached successfully, false otherwise
     */
    public function put(string $naicsCode, array $params, array $response): bool
    {
        try {
            $cacheKey = $this->buildCacheKey($naicsCode, $params);

            // Mark response as cached
            $response['cached'] = false; // Will be true when retrieved

            Cache::put($cacheKey, $response, self::TTL);

            Log::debug('SAM.gov response cached', [
                'cache' => 'SamOpportunitiesCache',
                'naics' => $naicsCode,
                'cache_key' => $cacheKey,
                'ttl_seconds' => self::TTL,
                'expires_at' => now()->addSeconds(self::TTL)->toDateTimeString(),
            ]);

            return true;
        } catch (\Exception $e) {
            // Gracefully handle cache failures - don't break the application
            Log::warning('SAM.gov cache put failed', [
                'cache' => 'SamOpportunitiesCache',
                'naics' => $naicsCode,
                'error' => $e->getMessage(),
                'error_category' => 'cache_error',
            ]);

            return false;
        }
    }

    /**
     * Check if a cached response exists for a NAICS code and query parameters.
     *
     * @param  string  $naicsCode  6-digit NAICS code
     * @param  array  $params  Resolved query parameters
     * @return bool True if cached response exists, false otherwise
     */
    public function has(string $naicsCode, array $params): bool
    {
        try {
            $cacheKey = $this->buildCacheKey($naicsCode, $params);

            return Cache::has($cacheKey);
        } catch (\Exception $e) {
            // Gracefully handle cache failures
            Log::warning('SAM.gov cache has check failed', [
                'cache' => 'SamOpportunitiesCache',
                'naics' => $naicsCode,
                'error' => $e->getMessage(),
                'error_category' => 'cache_error',
            ]);

            return false;
        }
    }

    /**
     * Clear cached response for a specific NAICS code and query parameters.
     *
     * @param  string  $naicsCode  6-digit NAICS code
     * @param  array  $params  Resolved query parameters
     * @return bool True if cleared successfully, false otherwise
     */
    public function forget(string $naicsCode, array $params): bool
    {
        try {
            $cacheKey = $this->buildCacheKey($naicsCode, $params);

            $result = Cache::forget($cacheKey);

            if ($result) {
                Log::debug('SAM.gov cache cleared', [
                    'cache' => 'SamOpportunitiesCache',
                    'naics' => $naicsCode,
                    'cache_key' => $cacheKey,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            // Gracefully handle cache failures
            Log::warning('SAM.gov cache forget failed', [
                'cache' => 'SamOpportunitiesCache',
                'naics' => $naicsCode,
                'error' => $e->getMessage(),
                'error_category' => 'cache_error',
            ]);

            return false;
        }
    }

    /**
     * Clear all cached responses for multiple NAICS codes.
     *
     * @param  array  $naicsCodes  Array of NAICS codes
     * @param  array  $params  Resolved query parameters
     * @return int Number of cache keys cleared
     */
    public function forgetMultiple(array $naicsCodes, array $params): int
    {
        $clearedCount = 0;

        foreach ($naicsCodes as $naicsCode) {
            if ($this->forget($naicsCode, $params)) {
                $clearedCount++;
            }
        }

        if ($clearedCount > 0) {
            Log::info('SAM.gov cache bulk clear completed', [
                'cache' => 'SamOpportunitiesCache',
                'cleared_count' => $clearedCount,
                'total_naics' => count($naicsCodes),
            ]);
        }

        return $clearedCount;
    }

    /**
     * Build cache key for a NAICS code and query parameters.
     *
     * Cache key format: sam_opp_v2_{naics}_{param_hash}
     *
     * The param hash includes:
     * - place (state code)
     * - days_back
     * - notice_type_codes
     * - posted_from
     * - posted_to
     *
     * Excluded from hash:
     * - clearCache (cache control flag)
     * - limit (pagination parameter)
     * - keywords (not consistently supported in v2)
     *
     * @param  string  $naicsCode  6-digit NAICS code
     * @param  array  $params  Resolved query parameters
     * @return string Cache key
     */
    protected function buildCacheKey(string $naicsCode, array $params): string
    {
        // Build hash from query parameters that affect results
        $paramHash = md5(json_encode([
            'place' => $params['place'] ?? null,
            'days_back' => $params['days_back'] ?? 30,
            'notice_type_codes' => $params['notice_type_codes'] ?? [],
            'posted_from' => $params['posted_from'] ?? null,
            'posted_to' => $params['posted_to'] ?? null,
        ]));

        return self::PREFIX."_{$naicsCode}_{$paramHash}";
    }

    /**
     * Get the cache TTL in seconds.
     *
     * @return int TTL in seconds (900 = 15 minutes)
     */
    public function getTtl(): int
    {
        return self::TTL;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string Cache key prefix
     */
    public function getPrefix(): string
    {
        return self::PREFIX;
    }
}
