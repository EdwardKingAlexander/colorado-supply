<?php

namespace App\Mcp\Servers\Business\Tools;

use App\Mcp\Servers\Tool;
use App\Services\SamMultiNaicsFetcher;
use App\Support\SamOpportunityDeduplicator;
use App\Support\SamParameterResolver;
use App\Support\SamPerformanceLogger;
use App\Support\SamResponseBuilder;
use App\Support\SamStateFileManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FetchSamOpportunitiesTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'fetch-sam-opportunities';

    /**
     * The tool's description.
     */
    protected string $description = 'Fetch federal contract opportunities from SAM.gov with dynamic filtering by NAICS, PSC, keywords, location, and date ranges';

    /**
     * SAM.gov API configuration - using v2 (v1 endpoint returns 404).
     */
    protected string $apiBaseUrl = 'https://api.sam.gov/opportunities/v2/search';

    /**
     * Define the tool's input schema.
     */
    protected function inputSchema(): array
    {
        return [
            'naics_override' => [
                'type' => 'array',
                'description' => 'Override default NAICS codes (array of strings). Queries all NAICS codes sequentially with per-NAICS caching.',
                'required' => false,
            ],
            'psc_override' => [
                'type' => 'array',
                'description' => 'Override default PSC codes (array of strings).',
                'required' => false,
            ],
            'notice_type' => [
                'type' => 'array',
                'description' => 'Notice types to include (default: Presolicitation, Solicitation, Combined Synopsis/Solicitation, Sources Sought)',
                'required' => false,
            ],
            'place' => [
                'type' => 'string',
                'description' => 'State code for place of performance (e.g., "CO"). Default is nationwide (no state filter). Provide state code to limit to specific state.',
                'required' => false,
            ],
            'days_back' => [
                'type' => 'integer',
                'description' => 'Number of days to look back for opportunities (default: 30)',
                'required' => false,
            ],
            'limit' => [
                'type' => 'integer',
                'description' => 'Maximum number of opportunities to return after deduplication (default: 50)',
                'required' => false,
            ],
            'keywords' => [
                'type' => 'string',
                'description' => 'Free-text keyword filter to search in opportunity titles and descriptions',
                'required' => false,
            ],
            'small_business_only' => [
                'type' => 'boolean',
                'description' => 'When true, filters results to Small Business set-asides (setAsideCode=SBA)',
                'required' => false,
            ],
            'clearCache' => [
                'type' => 'boolean',
                'description' => 'Force refresh by clearing all cached NAICS results (default: false)',
                'required' => false,
            ],
        ];
    }

    /**
     * Get the output schema documentation.
     *
     * This method documents the response format returned by the tool.
     * The response format is designed for backward compatibility with legacy consumers.
     *
     * @return array Output schema structure
     */
    protected function outputSchema(): array
    {
        return [
            'success' => [
                'type' => 'boolean',
                'description' => 'True if all NAICS queries succeeded, false if any failed',
            ],
            'partial_success' => [
                'type' => 'boolean',
                'description' => 'True if some (but not all) NAICS queries succeeded',
            ],
            'fetched_at' => [
                'type' => 'string',
                'description' => 'ISO 8601 timestamp when opportunities were fetched',
            ],
            'opportunities' => [
                'type' => 'array',
                'description' => 'Array of opportunity objects (empty on complete failure)',
                'items' => [
                    'notice_id' => 'Unique SAM.gov notice identifier',
                    'solicitation_number' => 'Agency solicitation number',
                    'title' => 'Opportunity title',
                    'notice_type' => 'Type of notice (e.g., Presolicitation, Solicitation)',
                    'posted_date' => 'Date opportunity was posted (YYYY-MM-DD)',
                    'response_deadline' => 'Deadline for responses (YYYY-MM-DD)',
                    'naics_code' => 'NAICS classification code',
                    'psc_code' => 'Product Service Code',
                    'state_code' => 'Two-letter state code for place of performance',
                    'agency_name' => 'Name of contracting agency',
                    'set_aside_type' => 'Set-aside designation (e.g., Small Business, 8(a))',
                    'sam_url' => 'Direct link to opportunity on SAM.gov',
                ],
            ],
            'query' => [
                'type' => 'object',
                'description' => 'Query parameters used for this fetch',
                'fields' => [
                    'date_range' => 'Date range searched',
                    'naics_codes' => 'Array of NAICS codes queried',
                    'psc_codes' => 'Array of PSC codes filtered',
                    'state_code' => 'State code or "nationwide"',
                    'notice_types' => 'Array of notice types included',
                    'keywords' => 'Keyword filter applied (if any)',
                ],
            ],
            'summary' => [
                'type' => 'object',
                'description' => 'Summary statistics about the query results',
                'fields' => [
                    'total_records' => 'Total opportunities before deduplication',
                    'total_after_dedup' => 'Total opportunities after deduplication',
                    'duplicates_removed' => 'Number of duplicate opportunities removed',
                    'returned' => 'Number of opportunities returned (limited by limit parameter)',
                    'limit' => 'Maximum number of opportunities requested',
                    'successful_naics_count' => 'Number of NAICS queries that succeeded',
                    'failed_naics_count' => 'Number of NAICS queries that failed',
                    'cache_hit_rate' => 'Percentage of NAICS queries served from cache (e.g., "66.7%")',
                    'failed_naics' => 'Array of failed NAICS with error details (only present if failures occurred)',
                ],
            ],
            'performance' => [
                'type' => 'object',
                'description' => 'Performance metrics for this query',
                'fields' => [
                    'total_duration_ms' => 'Total query duration in milliseconds',
                    'cache_hits' => 'Number of NAICS queries served from cache',
                    'cache_misses' => 'Number of NAICS queries that required API calls',
                ],
            ],
            'error' => [
                'type' => 'string',
                'description' => 'Error message (only present on complete failure)',
            ],
        ];
    }

    /**
     * Example responses for different scenarios.
     *
     * @return array Example response structures
     */
    protected function exampleResponses(): array
    {
        return [
            'success' => [
                'success' => true,
                'partial_success' => false,
                'fetched_at' => '2025-01-15T10:30:00+00:00',
                'opportunities' => [
                    [
                        'notice_id' => '12345abcde',
                        'solicitation_number' => 'ABC-2025-001',
                        'title' => 'Construction Services',
                        'notice_type' => 'Solicitation',
                        'posted_date' => '2025-01-10',
                        'response_deadline' => '2025-02-10',
                        'naics_code' => '236220',
                        'psc_code' => 'Z1AA',
                        'state_code' => 'CO',
                        'agency_name' => 'Department of Defense',
                        'set_aside_type' => 'Small Business',
                        'sam_url' => 'https://sam.gov/opp/12345abcde/view',
                    ],
                ],
                'query' => [
                    'date_range' => '2024-12-16 to 2025-01-15',
                    'naics_codes' => ['236220', '541330'],
                    'psc_codes' => [],
                    'state_code' => 'CO',
                    'notice_types' => ['Presolicitation', 'Solicitation'],
                    'keywords' => null,
                ],
                'summary' => [
                    'total_records' => 150,
                    'total_after_dedup' => 120,
                    'duplicates_removed' => 30,
                    'returned' => 50,
                    'limit' => 50,
                    'successful_naics_count' => 2,
                    'failed_naics_count' => 0,
                    'cache_hit_rate' => '50.0%',
                ],
                'performance' => [
                    'total_duration_ms' => 1250,
                    'cache_hits' => 1,
                    'cache_misses' => 1,
                ],
            ],
            'partial_success' => [
                'success' => false,
                'partial_success' => true,
                'fetched_at' => '2025-01-15T10:30:00+00:00',
                'opportunities' => [
                    // Opportunities from successful NAICS queries
                ],
                'query' => [
                    'date_range' => '2024-12-16 to 2025-01-15',
                    'naics_codes' => ['236220', '541330', '562910'],
                    'psc_codes' => [],
                    'state_code' => 'CO',
                    'notice_types' => ['Presolicitation', 'Solicitation'],
                    'keywords' => null,
                ],
                'summary' => [
                    'total_records' => 100,
                    'total_after_dedup' => 85,
                    'duplicates_removed' => 15,
                    'returned' => 50,
                    'limit' => 50,
                    'successful_naics_count' => 2,
                    'failed_naics_count' => 1,
                    'cache_hit_rate' => '66.7%',
                    'failed_naics' => [
                        [
                            'message' => 'API request timed out',
                            'naics' => '562910',
                            'type' => 'timeout',
                        ],
                    ],
                ],
                'performance' => [
                    'total_duration_ms' => 2500,
                    'cache_hits' => 2,
                    'cache_misses' => 1,
                ],
            ],
            'failure' => [
                'success' => false,
                'partial_success' => false,
                'fetched_at' => '2025-01-15T10:30:00+00:00',
                'opportunities' => [],
                'query' => [
                    'date_range' => '2024-12-16 to 2025-01-15',
                    'naics_codes' => ['236220', '541330'],
                    'psc_codes' => [],
                    'state_code' => 'CO',
                    'notice_types' => ['Presolicitation', 'Solicitation'],
                    'keywords' => null,
                ],
                'summary' => [
                    'total_records' => 0,
                    'total_after_dedup' => 0,
                    'duplicates_removed' => 0,
                    'returned' => 0,
                    'limit' => 50,
                    'successful_naics_count' => 0,
                    'failed_naics_count' => 2,
                    'cache_hit_rate' => '0%',
                    'failed_naics' => [
                        [
                            'message' => 'API key invalid',
                            'naics' => '236220',
                            'type' => 'authentication',
                        ],
                        [
                            'message' => 'API key invalid',
                            'naics' => '541330',
                            'type' => 'authentication',
                        ],
                    ],
                ],
                'performance' => [
                    'total_duration_ms' => 500,
                    'cache_hits' => 0,
                    'cache_misses' => 2,
                ],
                'error' => 'API key invalid',
            ],
        ];
    }

    /**
     * Execute the tool.
     */
    public function execute(array $inputs): string
    {
        $result = $this->runWorkflow($inputs);

        return json_encode($result, JSON_PRETTY_PRINT);
    }

    public function fetch(array $params = []): array
    {
        return $this->runWorkflow($params);
    }

    protected function runWorkflow(array $params = [], bool $isFallback = false): array
    {
        $startTime = microtime(true);

        // Determine trigger context for logging
        $trigger = 'unknown';
        if (app()->runningInConsole()) {
            $trigger = 'scheduled_task';
        } elseif (request()->header('X-MCP-Request')) {
            $trigger = 'mcp_agent';
        } else {
            $trigger = 'filament_ui';
        }

        // Check API key configuration
        $apiKey = config('services.sam.api_key');
        if (empty($apiKey)) {
            Log::error('SAM.gov API key not configured', [
                'tool' => 'FetchSamOpportunitiesTool',
                'trigger' => $trigger,
                'error_category' => 'configuration',
            ]);

            $responseBuilder = new SamResponseBuilder;

            $response = $responseBuilder->failure(
                'SAM.gov API key not configured. Set SAM_API_KEY in .env file.'
            );

            return $this->toLegacyFormat($response, []);
        }

        try {
            // Initialize services
            $paramResolver = new SamParameterResolver;
            $fetcher = new SamMultiNaicsFetcher;
            $deduplicator = new SamOpportunityDeduplicator;
            $responseBuilder = new SamResponseBuilder;
            $performanceLogger = new SamPerformanceLogger;
            $stateManager = new SamStateFileManager;

            // Resolve parameters
            $resolved = $paramResolver->resolve($params);

            Log::debug('SAM.gov multi-NAICS query starting', [
                'tool' => 'FetchSamOpportunitiesTool',
                'trigger' => $trigger,
                'user_id' => auth()->id(),
                'naics_count' => count($resolved['naics_codes']),
                'query_params' => [
                    'place' => $resolved['place'],
                    'days_back' => $resolved['days_back'],
                    'limit' => $resolved['limit'],
                    'clearCache' => $resolved['clearCache'],
                ],
            ]);

            // Fetch opportunities from all NAICS codes
            $fetchResponse = $fetcher->fetchAll($resolved['naics_codes'], $resolved, $apiKey);
            $fetchResults = $fetchResponse['results'];

            // Merge and deduplicate results
            $merged = $deduplicator->merge($fetchResults);
            $dedupStats = $deduplicator->getStats($merged);

            // Apply post-fetch filters
            $filteredOpportunities = $this->applyFilters($merged['opportunities'], $resolved);
            $filteredCount = count($filteredOpportunities);

            // Sort by posted_date DESC and apply limit
            usort($filteredOpportunities, function ($a, $b) {
                return strcmp($b['posted_date'] ?? '', $a['posted_date'] ?? '');
            });

            $limitedOpportunities = array_slice($filteredOpportunities, 0, $resolved['limit']);
            $returnedCount = count($limitedOpportunities);

            // Fallback logic
            // Only trigger fallback if user didn't explicitly provide NAICS override
            // If they specified NAICS codes, respect their choice even with zero results
            $hasExplicitNaicsOverride = isset($params['naics_override']) && ! empty($params['naics_override']);

            if (empty($limitedOpportunities) && ! $isFallback && ! empty($resolved['keywords']) && ! $hasExplicitNaicsOverride) {
                Log::info('Primary search yielded no results, triggering fallback search.');
                $fallbackParams = $params;
                $fallbackParams['place'] = null; // Nationwide
                $fallbackParams['naics_override'] = []; // No NAICS
                $fallbackParams['psc_override'] = []; // No PSC

                return $this->runWorkflow($fallbackParams, true);
            }

            // Calculate total duration
            $totalDuration = round((microtime(true) - $startTime) * 1000);

            // Get summary from fetcher
            $fetchSummary = $fetcher->getSummary($fetchResponse);

            // Build metadata
            $metadata = [
                'total_duration_ms' => $totalDuration,
                'cache_hits' => $fetchResponse['performance']['cache_hits'] ?? 0,
                'cache_misses' => $fetchResponse['performance']['cache_misses'] ?? 0,
                'naics_queried' => $fetchSummary['total_naics_queried'] ?? 0,
                'naics_succeeded' => $fetchSummary['successful_naics'] ?? 0,
                'naics_failed' => $fetchSummary['failed_naics'] ?? 0,
                'count_before_dedup' => $dedupStats['total_before_dedup'] ?? 0,
                'total_after_dedup' => $dedupStats['total_after_dedup'] ?? 0,
                'total_after_filters' => $filteredCount,
                'returned_count' => $returnedCount,
                'duplicates_removed' => $dedupStats['duplicates_removed'] ?? 0,
            ];

            // Add deduplication stats to metadata
            $metadata = $responseBuilder->addDeduplicationStats($metadata, $dedupStats);

            // Extract errors
            $errors = $responseBuilder->extractErrors($fetchResults);

            // Analyze performance and add warnings
            $warnings = $performanceLogger->analyzePerformance($metadata);
            if (! empty($warnings)) {
                $metadata = $responseBuilder->addWarnings($metadata, $warnings);
            }

            // Build standardized response
            $response = $responseBuilder->build($limitedOpportunities, $metadata, $errors);

            // Log performance metrics
            $performanceLogger->log($metadata);

            // Log warnings if any
            foreach ($warnings as $warning) {
                Log::warning($warning, [
                    'tool' => 'FetchSamOpportunitiesTool',
                    'trigger' => $trigger,
                ]);
            }

            // Save state file
            $stateManager->save($resolved, $response['metadata'], $fetcher->getFailedNaics($fetchResults));

            // Rotate state files (keep last 10)
            $stateManager->rotate();

            Log::info('SAM.gov multi-NAICS query completed', [
                'tool' => 'FetchSamOpportunitiesTool',
                'trigger' => $trigger,
                'user_id' => auth()->id(),
                'status' => $response['status'],
                'total_duration_ms' => $totalDuration,
                'opportunities_returned' => $returnedCount,
                'cache_hit_rate' => $fetchSummary['cache_hit_rate'],
            ]);

            // Convert to legacy format for backward compatibility
            $legacy = $this->toLegacyFormat($response, $resolved);

            // Persist legacy state to shared file used by UI/export
            $stateManager->saveLegacy($legacy);

            return $legacy;
        } catch (\Exception $e) {
            $errorCategory = 'unexpected_exception';
            if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
                $errorCategory = 'network_error';
            } elseif ($e instanceof \InvalidArgumentException) {
                $errorCategory = 'data_error';
            }

            Log::error('SAM.gov tool exception', [
                'tool' => 'FetchSamOpportunitiesTool',
                'trigger' => $trigger ?? 'unknown',
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'error_category' => $errorCategory,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $responseBuilder = new SamResponseBuilder;

            $legacy = $this->toLegacyFormat(
                $responseBuilder->failure($e->getMessage()),
                []
            );

            // Best-effort persist failure state for visibility in UI/export
            try {
                (new SamStateFileManager)->saveLegacy($legacy);
            } catch (\Throwable $persistException) {
                // swallow
            }

            return $legacy;
        }
    }

    /**
     * Apply post-fetch filters for PSC, keywords, etc.
     *
     * @param  array  $opportunities  Deduplicated opportunities
     * @param  array  $params  Resolved query parameters
     * @return array Filtered opportunities
     */
    protected function applyFilters(array $opportunities, array $params): array
    {
        // PSC and keyword filtering disabled per request—return deduped results as-is.
        return $opportunities;
    }

    /**
     * Convert new response format to legacy format for backward compatibility.
     */
    protected function toLegacyFormat(array $response, array $resolved = []): array
    {
        $legacy = [
            'success' => $response['status'] === 'success',
            'partial_success' => $response['status'] === 'partial_success',
            'fetched_at' => now()->toIso8601String(),
            'opportunities' => $response['opportunities'] ?? [],
        ];

        // Add query metadata if available
        if (! empty($resolved)) {
            $legacy['query'] = [
                'date_range' => ($resolved['posted_from'] ?? '').' to '.($resolved['posted_to'] ?? ''),
                'naics_codes' => $resolved['naics_codes'] ?? [],
                'psc_codes' => $resolved['psc_codes'] ?? [],
                'state_code' => $resolved['place'] ?? 'nationwide',
                'notice_types' => $resolved['notice_types'] ?? [],
                'keywords' => $resolved['keywords'] ?? null,
                'set_asides' => $resolved['set_aside_codes'] ?? [],
            ];
        }

        // Add summary
        $metadata = $response['metadata'] ?? [];
        $filteredCount = $metadata['total_after_filters'] ?? null;
        $returnedCount = $metadata['returned_count'] ?? count($response['opportunities'] ?? []);
        $dedupCount = $metadata['total_after_dedup'] ?? 0;

        $legacy['summary'] = [
            'total_records' => $metadata['count_before_dedup'] ?? 0,
            // Prefer post-filter count if available so UI matches what we display
            'total_after_dedup' => $filteredCount ?? $dedupCount,
            'dedup_before_filters' => $dedupCount,
            'filtered_out' => ($filteredCount !== null)
                ? max(($dedupCount ?? 0) - $filteredCount, 0)
                : 0,
            'duplicates_removed' => $metadata['duplicates_removed'] ?? 0,
            'returned' => $returnedCount,
            'limit' => $resolved['limit'] ?? 50,
            'successful_naics_count' => $metadata['naics_succeeded'] ?? 0,
            'failed_naics_count' => $metadata['naics_failed'] ?? 0,
            'cache_hit_rate' => ($metadata['cache_hits'] ?? 0) + ($metadata['cache_misses'] ?? 0) > 0
                ? round((($metadata['cache_hits'] ?? 0) / (($metadata['cache_hits'] ?? 0) + ($metadata['cache_misses'] ?? 0))) * 100, 1).'%'
                : '0%',
        ];

        // Add performance
        $legacy['performance'] = [
            'total_duration_ms' => $metadata['total_duration_ms'] ?? 0,
            'cache_hits' => $metadata['cache_hits'] ?? 0,
            'cache_misses' => $metadata['cache_misses'] ?? 0,
        ];

        // Add error if present
        if (isset($response['error'])) {
            $legacy['error'] = $response['error'];
        }

        // Add failed NAICS if present
        if (isset($response['errors']) && ! empty($response['errors'])) {
            $legacy['summary']['failed_naics'] = $response['errors'];
        }

        return $legacy;
    }

    /**
     * Map SAM.gov API response to our 12-field schema.
     */
    protected function mapOpportunities(array $apiData): array
    {
        return self::mapOpportunitiesStatic($apiData);
    }

    /**
     * Static version of mapOpportunities.
     */
    protected static function mapOpportunitiesStatic(array $apiData): array
    {
        $mapped = [];

        foreach ($apiData as $opp) {
            $mapped[] = [
                'notice_id' => $opp['noticeId'] ?? null,
                'solicitation_number' => $opp['solicitationNumber'] ?? null,
                'title' => $opp['title'] ?? 'Untitled',
                'notice_type' => $opp['type'] ?? 'Unknown',
                'posted_date' => self::formatDateStatic($opp['postedDate'] ?? null),
                'response_deadline' => self::formatDateStatic($opp['responseDeadLine'] ?? null),
                'naics_code' => $opp['naics'] ?? $opp['naicsCode'] ?? null,
                'psc_code' => $opp['psc'] ?? $opp['classificationCode'] ?? null,
                'state_code' => self::extractStateCodeStatic($opp),
                'agency_name' => self::extractAgencyNameStatic($opp),
                'set_aside_type' => $opp['typeOfSetAsideDescription'] ?? $opp['typeOfSetAside'] ?? null,
                'sam_url' => $opp['url'] ?? $opp['uiLink'] ?? null,
            ];
        }

        return $mapped;
    }

    /**
     * Format date to ISO 8601 (YYYY-MM-DD).
     */
    protected function formatDate(?string $date): ?string
    {
        return self::formatDateStatic($date);
    }

    /**
     * Static version of formatDate.
     */
    protected static function formatDateStatic(?string $date): ?string
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
     */
    protected function extractStateCode(array $opp): ?string
    {
        return self::extractStateCodeStatic($opp);
    }

    /**
     * Static version of extractStateCode.
     */
    protected static function extractStateCodeStatic(array $opp): ?string
    {
        // Try various possible paths in the response
        if (isset($opp['placeOfPerformance']['state']['code'])) {
            return $opp['placeOfPerformance']['state']['code'];
        }

        if (isset($opp['place'])) {
            // Try to extract state from string like "Colorado Springs, CO"
            if (preg_match('/\b([A-Z]{2})\b/', $opp['place'], $matches)) {
                return $matches[1];
            }
        }

        if (isset($opp['stateCode'])) {
            return $opp['stateCode'];
        }

        return null;
    }

    /**
     * Extract agency name from various possible fields.
     */
    protected function extractAgencyName(array $opp): ?string
    {
        return self::extractAgencyNameStatic($opp);
    }

    /**
     * Static version of extractAgencyName.
     */
    protected static function extractAgencyNameStatic(array $opp): ?string
    {
        // Try multiple possible fields
        if (isset($opp['department']['name'])) {
            return $opp['department']['name'];
        }

        if (isset($opp['agency'])) {
            return $opp['agency'];
        }

        if (isset($opp['fullParentPathName'])) {
            return $opp['fullParentPathName'];
        }

        if (isset($opp['organizationType'])) {
            return $opp['organizationType'];
        }

        return null;
    }
}
