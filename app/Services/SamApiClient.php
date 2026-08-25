<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client for SAM.gov v2 API opportunities endpoint.
 *
 * This client:
 * - Handles single-NAICS queries (v2 API limitation)
 * - Implements retry logic with exponential backoff for rate limits
 * - Maps API responses to standardized 12-field schema
 * - Returns consistent success/error response format
 * - Does NOT handle caching (that's SamOpportunitiesCache's job)
 */
class SamApiClient
{
    /**
     * Fallback base URL, used only if config is missing.
     *
     * @see config('services.sam.base_url') for the authoritative value and the
     *      reason it no longer points at api.sam.gov.
     */
    protected const API_BASE_URL = 'https://sam.gov/api/prod/opportunities/v2/search';

    /**
     * HTTP request timeout in seconds.
     * Increased to 30s to handle slow SAM.gov API responses.
     */
    protected const TIMEOUT = 30;

    /**
     * Resolve the SAM.gov search endpoint.
     */
    protected function baseUrl(): string
    {
        return config('services.sam.base_url') ?: self::API_BASE_URL;
    }

    /**
     * Resolve the HTTP timeout.
     */
    protected function timeout(): int
    {
        return (int) (config('services.sam.timeout') ?: self::TIMEOUT);
    }

    /**
     * Maximum retry attempts for rate limiting.
     */
    protected const MAX_RETRIES = 3;

    /**
     * Exponential backoff delays in seconds.
     */
    protected const BACKOFF_DELAYS = [1, 2, 4, 8];

    /**
     * Default limit for API requests (SAM.gov API v2 maximum is 1000).
     * Using maximum to ensure we get all available records per NAICS code.
     */
    protected const DEFAULT_LIMIT = 1000;

    /**
     * Default cap on pages fetched per NAICS code.
     *
     * At the 1000-record page size this is 10,000 records for a single NAICS,
     * which is far beyond any realistic query. The cap exists so a runaway
     * result set cannot stall the queue worker.
     */
    protected const DEFAULT_MAX_PAGES = 10;

    /**
     * Fetch opportunities from SAM.gov v2 API for a single NAICS code.
     *
     * @param  string  $naicsCode  6-digit NAICS code
     * @param  array  $params  Resolved query parameters from SamParameterResolver
     * @param  string  $apiKey  SAM.gov API key
     * @return array Standardized response with success/error status
     */
    public function fetch(string $naicsCode, array $params, string $apiKey): array
    {
        $maxPages = max(1, (int) (config('services.sam.max_pages') ?: self::DEFAULT_MAX_PAGES));

        $opportunities = [];
        $totalRecords = 0;
        $pagesFetched = 0;
        $truncated = false;
        $noData = false;

        while (true) {
            $result = $this->fetchPage($naicsCode, $params, $apiKey, $pagesFetched * self::DEFAULT_LIMIT);

            if (! ($result['success'] ?? false)) {
                // Never discard pages already gathered because a later one
                // failed — return them as a partial result instead.
                if ($opportunities !== []) {
                    return [
                        'success' => true,
                        'naics' => $naicsCode,
                        'count' => count($opportunities),
                        'total_records' => $totalRecords,
                        'opportunities' => $opportunities,
                        'cached' => false,
                        'pages_fetched' => $pagesFetched,
                        'truncated' => true,
                        'page_error' => $result['error'] ?? 'Unknown error',
                    ];
                }

                return $result;
            }

            $opportunities = array_merge($opportunities, $result['opportunities'] ?? []);
            $totalRecords = $result['total_records'] ?? 0;
            $pagesFetched++;

            // Only meaningful when the very first page came back empty: it
            // records that SAM.gov explicitly said "no records matched" rather
            // than us having exhausted a larger set.
            if ($pagesFetched === 1) {
                $noData = (bool) ($result['no_data'] ?? false);
            }

            // No progress: stop rather than loop forever on an empty page.
            if (empty($result['opportunities'])) {
                break;
            }

            if (count($opportunities) >= $totalRecords) {
                break;
            }

            if ($pagesFetched >= $maxPages) {
                $truncated = true;

                Log::warning('SAM.gov pagination cap reached, results truncated', [
                    'service' => 'SamApiClient',
                    'naics' => $naicsCode,
                    'pages_fetched' => $pagesFetched,
                    'fetched' => count($opportunities),
                    'total_records' => $totalRecords,
                ]);

                break;
            }

            $this->addPageDelay();
        }

        return [
            'success' => true,
            'naics' => $naicsCode,
            'count' => count($opportunities),
            'total_records' => $totalRecords,
            'opportunities' => $opportunities,
            'cached' => false,
            'pages_fetched' => $pagesFetched,
            'truncated' => $truncated,
            'no_data' => $noData,
        ];
    }

    /**
     * Fetch a single page of results for one NAICS code.
     *
     * @param  int  $offset  Record offset for pagination
     * @return array Standardized per-page response
     */
    protected function fetchPage(string $naicsCode, array $params, string $apiKey, int $offset): array
    {
        $queryParams = $this->buildQueryParams($naicsCode, $params, $apiKey);
        $queryParams['offset'] = $offset;

        // Log query parameters for debugging (excluding API key)
        $debugParams = $queryParams;
        $debugParams['api_key'] = '***';
        Log::debug('SAM.gov API request', [
            'service' => 'SamApiClient',
            'naics' => $naicsCode,
            'endpoint' => $this->baseUrl(),
            'params' => $debugParams,
        ]);

        $retryCount = 0;

        while ($retryCount <= self::MAX_RETRIES) {
            try {
                $response = Http::timeout($this->timeout())
                    ->withHeaders(['Accept' => 'application/json'])
                    ->get($this->baseUrl(), $queryParams);

                // Handle rate limiting with retry logic
                if ($response->status() === 429) {
                    if ($retryCount < self::MAX_RETRIES) {
                        $this->handleRateLimitRetry($naicsCode, $retryCount);
                        $retryCount++;

                        continue;
                    } else {
                        return $this->buildRateLimitExhaustedResponse($naicsCode);
                    }
                }

                // 404 is ambiguous on SAM.gov and must not be collapsed into one
                // outcome — see handleNotFound().
                if ($response->status() === 404) {
                    return $this->handleNotFound($naicsCode, $response);
                }

                // Handle other HTTP errors
                if (! $response->successful()) {
                    return $this->buildErrorResponse($naicsCode, $response);
                }

                // Parse and validate response
                return $this->parseSuccessResponse($naicsCode, $response);
            } catch (ConnectionException $e) {
                return $this->buildNetworkErrorResponse($naicsCode, $e);
            } catch (\Exception $e) {
                return $this->buildUnexpectedErrorResponse($naicsCode, $e);
            }
        }

        // Safety fallback (should never reach here)
        return [
            'success' => false,
            'naics' => $naicsCode,
            'error' => 'Query failed after retries',
            'status_code' => null,
        ];
    }

    /**
     * Disambiguate SAM.gov's two very different 404s.
     *
     * GSA documents 404 as "no data" for the opportunities endpoint, so an
     * empty result set legitimately arrives as a 404 with a JSON body. But the
     * api.sam.gov gateway outage that began around 2026-07-09 also answered
     * every path with 404 — with a zero-length body.
     *
     * Treating both as fatal reports "no matching opportunities" as a total
     * system failure. Treating both as success hides a real outage behind a
     * plausible-looking zero. The body is what separates them.
     */
    protected function handleNotFound(string $naicsCode, $response): array
    {
        $body = trim($response->body());

        if ($body === '') {
            Log::error('SAM.gov returned an empty-bodied 404 — endpoint unreachable', [
                'service' => 'SamApiClient',
                'naics' => $naicsCode,
                'endpoint' => $this->baseUrl(),
                'error_category' => 'endpoint_unreachable',
            ]);

            return [
                'success' => false,
                'naics' => $naicsCode,
                'error' => 'SAM.gov returned 404 with an empty body for '.$this->baseUrl()
                    .' — the endpoint is unreachable or has moved. Run: php artisan sam:diagnose',
                'status_code' => 404,
                'error_type' => 'endpoint_unreachable',
                'response_body' => null,
            ];
        }

        // A JSON body on a 404 is SAM.gov's documented "no records found".
        if (json_decode($body, true) !== null || $body === 'null') {
            Log::info('SAM.gov reported no matching records', [
                'service' => 'SamApiClient',
                'naics' => $naicsCode,
            ]);

            return [
                'success' => true,
                'naics' => $naicsCode,
                'count' => 0,
                'total_records' => 0,
                'opportunities' => [],
                'cached' => false,
                'no_data' => true,
            ];
        }

        // Non-empty but not JSON: genuinely unexpected, keep it loud.
        return $this->buildErrorResponse($naicsCode, $response);
    }

    /**
     * Small delay between pages of the same NAICS query.
     */
    protected function addPageDelay(): void
    {
        if (! app()->environment('testing')) {
            usleep(250000);
        }
    }

    /**
     * Build query parameters for SAM.gov v2 API.
     *
     * @param  string  $naicsCode  6-digit NAICS code
     * @param  array  $params  Resolved parameters
     * @param  string  $apiKey  API key
     * @return array Query parameters
     */
    protected function buildQueryParams(string $naicsCode, array $params, string $apiKey): array
    {
        $queryParams = [
            'api_key' => $apiKey,
            'postedFrom' => $params['posted_from'],
            'postedTo' => $params['posted_to'],
            // NAICS filter. This was 'naics' for months, which SAM.gov silently
            // ignores — it is not a recognised parameter, and unknown parameters
            // are dropped rather than rejected. Every per-NAICS query therefore
            // returned the same unfiltered result set, which is why a "working"
            // run on 2026-06-12 pulled 9 x 1000 records and deduplicated 8000 of
            // them down to the same 1000. Measured 2026-08-24 over 08/01-08/24:
            //   (no filter) -> 24084 | naics=423840 -> 24084 | ncode=423840 -> 2
            'ncode' => $naicsCode,
            'ptype' => implode(',', $params['notice_type_codes']),
            'limit' => self::DEFAULT_LIMIT,
            'offset' => $params['offset'] ?? 0,
        ];

        // Add optional state filter
        if (! empty($params['place'])) {
            $queryParams['state'] = $params['place'];
        }

        // Set-asides are deliberately NOT sent to the API.
        //
        // SAM.gov's real parameter is 'typeOfSetAside' (the long-standing
        // 'setAsideCode' was silently ignored), but it is single-valued —
        // comma-joining the six configured codes returns 0 records rather than
        // their union. Measured 2026-08-24 over 08/01-08/24: setAsideCode=SBA
        // -> 24084 (ignored), typeOfSetAside=SBA -> 7845, six codes joined -> 0.
        //
        // Every record carries its `typeOfSetAside` code, so filtering happens
        // post-fetch instead. That supports multiple codes, and it keeps cached
        // responses set-aside-agnostic — the cache key cannot go stale on a
        // dimension the request never varied by.

        return $queryParams;
    }

    /**
     * Handle rate limit retry with exponential backoff.
     *
     * @param  string  $naicsCode  NAICS code being queried
     * @param  int  $retryCount  Current retry attempt
     */
    protected function handleRateLimitRetry(string $naicsCode, int $retryCount): void
    {
        $delay = self::BACKOFF_DELAYS[$retryCount] ?? 8;

        Log::warning('SAM.gov rate limit detected, retrying with backoff', [
            'service' => 'SamApiClient',
            'naics' => $naicsCode,
            'retry_count' => $retryCount + 1,
            'backoff_seconds' => $delay,
            'error_category' => 'rate_limit',
        ]);

        if (! app()->environment('testing')) {
            sleep($delay);
        }
    }

    /**
     * Build response for rate limit exhaustion.
     *
     * @param  string  $naicsCode  NAICS code
     * @return array Error response
     */
    protected function buildRateLimitExhaustedResponse(string $naicsCode): array
    {
        Log::error('SAM.gov rate limit exceeded after max retries', [
            'service' => 'SamApiClient',
            'naics' => $naicsCode,
            'retry_count' => self::MAX_RETRIES,
            'error_category' => 'rate_limit_exhausted',
        ]);

        return [
            'success' => false,
            'naics' => $naicsCode,
            'error' => 'Rate limit exceeded after '.self::MAX_RETRIES.' retries',
            'status_code' => 429,
        ];
    }

    /**
     * Build error response for HTTP errors.
     *
     * @param  string  $naicsCode  NAICS code
     * @param  Response  $response  HTTP response
     * @return array Error response
     */
    protected function buildErrorResponse(string $naicsCode, $response): array
    {
        $statusCode = $response->status();
        $errorCategory = $this->categorizeHttpError($statusCode);
        $responseBody = $response->body();
        $headers = $response->headers();

        Log::warning('SAM.gov API returned error status', [
            'service' => 'SamApiClient',
            'naics' => $naicsCode,
            'status_code' => $statusCode,
            'error_category' => $errorCategory,
            'response_body' => substr($responseBody, 0, 500),
            'content_type' => $headers['Content-Type'][0] ?? null,
            'response_size' => strlen($responseBody),
        ]);

        // Build user-friendly error message based on status code
        $errorMessage = match ($statusCode) {
            401 => 'Authentication failed - check SAM.gov API key',
            403 => 'Access forbidden - API key may lack permissions',
            404 => 'API endpoint not found',
            429 => 'Rate limit exceeded - too many requests',
            500, 502, 503, 504 => 'SAM.gov server error - try again later',
            default => 'SAM.gov API request failed',
        };

        return [
            'success' => false,
            'naics' => $naicsCode,
            'error' => $errorMessage,
            'status_code' => $statusCode,
            'error_type' => $errorCategory,
            'response_body' => substr($responseBody, 0, 1000), // First 1000 chars for debugging
        ];
    }

    /**
     * Categorize HTTP error status codes.
     *
     * @param  int  $statusCode  HTTP status code
     * @return string Error category
     */
    protected function categorizeHttpError(int $statusCode): string
    {
        return match (true) {
            $statusCode === 401 => 'authentication',
            $statusCode === 404 => 'endpoint_not_found',
            $statusCode >= 500 => 'server_error',
            default => 'api_error',
        };
    }

    /**
     * Build response for network errors.
     *
     * @param  string  $naicsCode  NAICS code
     * @param  ConnectionException  $e  Exception
     * @return array Error response
     */
    protected function buildNetworkErrorResponse(string $naicsCode, ConnectionException $e): array
    {
        Log::warning('SAM.gov network error', [
            'service' => 'SamApiClient',
            'naics' => $naicsCode,
            'error' => $e->getMessage(),
            'error_category' => 'network_error',
        ]);

        return [
            'success' => false,
            'naics' => $naicsCode,
            'error' => 'Network error: '.$e->getMessage(),
            'status_code' => null,
            'error_type' => 'network_error',
            'response_body' => null,
        ];
    }

    /**
     * Build response for unexpected exceptions.
     *
     * @param  string  $naicsCode  NAICS code
     * @param  \Exception  $e  Exception
     * @return array Error response
     */
    protected function buildUnexpectedErrorResponse(string $naicsCode, \Exception $e): array
    {
        Log::error('Unexpected exception in SAM.gov query', [
            'service' => 'SamApiClient',
            'naics' => $naicsCode,
            'error' => $e->getMessage(),
            'error_category' => 'unexpected_exception',
            'exception_class' => get_class($e),
        ]);

        return [
            'success' => false,
            'naics' => $naicsCode,
            'error' => 'Unexpected error: '.$e->getMessage(),
            'status_code' => null,
            'error_type' => 'unexpected_exception',
            'response_body' => null,
        ];
    }

    /**
     * Parse and validate successful API response.
     *
     * @param  string  $naicsCode  NAICS code
     * @param  Response  $response  HTTP response
     * @return array Success response with mapped opportunities
     */
    protected function parseSuccessResponse(string $naicsCode, $response): array
    {
        $data = $response->json();

        // Validate response structure
        if (! is_array($data)) {
            Log::warning('SAM.gov API returned unexpected response structure', [
                'service' => 'SamApiClient',
                'naics' => $naicsCode,
                'error_category' => 'data_error',
                'response_type' => gettype($data),
            ]);

            return [
                'success' => false,
                'naics' => $naicsCode,
                'error' => 'Unexpected response structure from API',
                'status_code' => $response->status(),
            ];
        }

        // Map to standardized 12-field schema
        $opportunities = $this->mapOpportunities($data['opportunitiesData'] ?? []);

        return [
            'success' => true,
            'naics' => $naicsCode,
            'count' => count($opportunities),
            'total_records' => $data['totalRecords'] ?? 0,
            'opportunities' => $opportunities,
            'cached' => false,
        ];
    }

    /**
     * Map SAM.gov API response to standardized 13-field schema.
     *
     * Schema fields:
     * - notice_id
     * - solicitation_number
     * - title
     * - notice_type
     * - posted_date (YYYY-MM-DD)
     * - response_deadline (YYYY-MM-DD)
     * - naics_code
     * - psc_code
     * - state_code
     * - agency_name
     * - set_aside_type
     * - sam_url
     * - lastModifiedDate (ISO 8601 timestamp for deduplication)
     *
     * @param  array  $apiData  Raw opportunities from API
     * @return array Mapped opportunities
     */
    protected function mapOpportunities(array $apiData): array
    {
        $mapped = [];

        foreach ($apiData as $opp) {
            $mapped[] = [
                'notice_id' => $opp['noticeId'] ?? null,
                'solicitation_number' => $opp['solicitationNumber'] ?? null,
                'title' => $opp['title'] ?? 'Untitled',
                'notice_type' => $opp['type'] ?? 'Unknown',
                'posted_date' => $this->formatDate($opp['postedDate'] ?? null),
                'response_deadline' => $this->formatDate($opp['responseDeadLine'] ?? null),
                'naics_code' => $opp['naics'] ?? $opp['naicsCode'] ?? null,
                'psc_code' => $opp['psc'] ?? $opp['classificationCode'] ?? null,
                // SAM.gov v2 returns `description` as a link to the description
                // resource rather than inline text; store whatever it gives us
                // instead of dropping the field entirely as we used to.
                'description' => $opp['description'] ?? null,
                'state_code' => $this->extractStateCode($opp),
                'agency_name' => $this->extractAgencyName($opp),
                'set_aside_type' => $opp['typeOfSetAsideDescription'] ?? $opp['typeOfSetAside'] ?? null,
                // The machine code (e.g. SDVOSBC) alongside the human
                // description, so set-aside filtering matches exactly instead of
                // pattern-matching prose that SAM.gov is free to reword.
                'set_aside_code' => $opp['typeOfSetAside'] ?? null,
                'sam_url' => $opp['url'] ?? $opp['uiLink'] ?? null,
                'lastModifiedDate' => $opp['lastModifiedDate'] ?? null,
            ];
        }

        return $mapped;
    }

    /**
     * Format date to ISO 8601 (YYYY-MM-DD).
     *
     * @param  string|null  $date  Date string from API
     * @return string|null Formatted date or null
     */
    protected function formatDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return $date; // Return as-is if parsing fails
        }
    }

    /**
     * Extract state code from place of performance.
     *
     * @param  array  $opp  Opportunity data
     * @return string|null State code or null
     */
    protected function extractStateCode(array $opp): ?string
    {
        // Try nested structure
        if (isset($opp['placeOfPerformance']['state']['code'])) {
            return $opp['placeOfPerformance']['state']['code'];
        }

        // Try place string (e.g., "Colorado Springs, CO")
        if (isset($opp['place']) && preg_match('/\b([A-Z]{2})\b/', $opp['place'], $matches)) {
            return $matches[1];
        }

        // Try direct field
        if (isset($opp['stateCode'])) {
            return $opp['stateCode'];
        }

        return null;
    }

    /**
     * Extract agency name from various possible fields.
     *
     * @param  array  $opp  Opportunity data
     * @return string|null Agency name or null
     */
    protected function extractAgencyName(array $opp): ?string
    {
        // Try multiple possible fields in priority order
        return $opp['department']['name']
            ?? $opp['agency']
            ?? $opp['fullParentPathName']
            ?? $opp['organizationType']
            ?? null;
    }
}
