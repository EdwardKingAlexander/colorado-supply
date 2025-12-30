<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
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
     * SAM.gov v2 API base URL.
     */
    protected const API_BASE_URL = 'https://api.sam.gov/opportunities/v2/search';

    /**
     * HTTP request timeout in seconds.
     * Increased to 30s to handle slow SAM.gov API responses.
     */
    protected const TIMEOUT = 30;

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
     * Fetch opportunities from SAM.gov v2 API for a single NAICS code.
     *
     * @param  string  $naicsCode  6-digit NAICS code
     * @param  array  $params  Resolved query parameters from SamParameterResolver
     * @param  string  $apiKey  SAM.gov API key
     * @return array Standardized response with success/error status
     */
    public function fetch(string $naicsCode, array $params, string $apiKey): array
    {
        $queryParams = $this->buildQueryParams($naicsCode, $params, $apiKey);

        // Log query parameters for debugging (excluding API key)
        $debugParams = $queryParams;
        $debugParams['api_key'] = '***';
        Log::debug('SAM.gov API request', [
            'service' => 'SamApiClient',
            'naics' => $naicsCode,
            'endpoint' => self::API_BASE_URL,
            'params' => $debugParams,
        ]);

        $retryCount = 0;

        while ($retryCount <= self::MAX_RETRIES) {
            try {
                $response = Http::timeout(self::TIMEOUT)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->get(self::API_BASE_URL, $queryParams);

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
            // SAM.gov v2 search expects "naics" for the NAICS filter (older docs show ncode, which returns empty)
            'naics' => $naicsCode,
            'ptype' => implode(',', $params['notice_type_codes']),
            'limit' => self::DEFAULT_LIMIT,
        ];

        // Add optional state filter
        if (! empty($params['place'])) {
            $queryParams['state'] = $params['place'];
        }

        // Add optional set-aside filter (supports multiple codes)
        if (! empty($params['set_aside_codes']) && is_array($params['set_aside_codes'])) {
            $queryParams['setAsideCode'] = implode(',', $params['set_aside_codes']);
        }

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

        sleep($delay);
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
     * @param  \Illuminate\Http\Client\Response  $response  HTTP response
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
     * @param  \Illuminate\Http\Client\Response  $response  HTTP response
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
                'state_code' => $this->extractStateCode($opp),
                'agency_name' => $this->extractAgencyName($opp),
                'set_aside_type' => $opp['typeOfSetAsideDescription'] ?? $opp['typeOfSetAside'] ?? null,
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
