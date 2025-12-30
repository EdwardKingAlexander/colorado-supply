<?php

namespace App\Support;

/**
 * Build standardized success/partial/failure JSON responses for all integration points.
 *
 * This service:
 * - Three response types: success, partial_success, failure
 * - Consistent metadata structure
 * - Error aggregation for failures
 * - Performance metrics inclusion
 */
class SamResponseBuilder
{
    /**
     * Build a success response.
     *
     * @param  array  $opportunities  Array of opportunities
     * @param  array  $metadata  Performance and query metadata
     * @return array Standardized success response
     */
    public function success(array $opportunities, array $metadata = []): array
    {
        return [
            'status' => 'success',
            'opportunities' => $opportunities,
            'metadata' => $this->formatMetadata($metadata, [
                'total_count' => count($opportunities),
            ]),
        ];
    }

    /**
     * Build a partial success response.
     *
     * @param  array  $opportunities  Array of opportunities (may be partial)
     * @param  array  $metadata  Performance and query metadata
     * @param  array  $errors  Array of errors encountered
     * @return array Standardized partial success response
     */
    public function partialSuccess(array $opportunities, array $metadata = [], array $errors = []): array
    {
        return [
            'status' => 'partial_success',
            'opportunities' => $opportunities,
            'metadata' => $this->formatMetadata($metadata, [
                'total_count' => count($opportunities),
                'errors_count' => count($errors),
            ]),
            'errors' => $this->formatErrors($errors),
        ];
    }

    /**
     * Build a failure response.
     *
     * @param  string  $error  Primary error message
     * @param  array  $metadata  Performance and query metadata
     * @param  array  $errors  Additional errors (optional)
     * @return array Standardized failure response
     */
    public function failure(string $error, array $metadata = [], array $errors = []): array
    {
        $allErrors = $this->formatErrors(array_merge([['message' => $error]], $errors));

        return [
            'status' => 'failure',
            'error' => $error,
            'metadata' => $this->formatMetadata($metadata, [
                'total_count' => 0,
                'errors_count' => count($allErrors),
            ]),
            'errors' => $allErrors,
        ];
    }

    /**
     * Format metadata with defaults and additional fields.
     *
     * @param  array  $metadata  Raw metadata
     * @param  array  $additional  Additional fields to merge
     * @return array Formatted metadata
     */
    protected function formatMetadata(array $metadata, array $additional = []): array
    {
        $formatted = [
            'total_count' => $metadata['total_count'] ?? 0,
            'naics_queried' => $metadata['naics_queried'] ?? 0,
            'naics_succeeded' => $metadata['naics_succeeded'] ?? 0,
            'naics_failed' => $metadata['naics_failed'] ?? 0,
            'cache_hits' => $metadata['cache_hits'] ?? 0,
            'cache_misses' => $metadata['cache_misses'] ?? 0,
            'total_duration_ms' => $metadata['total_duration_ms'] ?? 0,
            'count_before_dedup' => $metadata['count_before_dedup'] ?? 0,
            'total_after_dedup' => $metadata['total_after_dedup'] ?? 0,
            'duplicates_removed' => $metadata['duplicates_removed'] ?? 0,
        ];

        // Merge additional fields
        return array_merge($formatted, $additional);
    }

    /**
     * Format errors into consistent structure.
     *
     * @param  array  $errors  Raw errors
     * @return array Formatted errors
     */
    protected function formatErrors(array $errors): array
    {
        return array_map(function ($error) {
            if (is_string($error)) {
                return ['message' => $error];
            }

            return [
                'message' => $error['message'] ?? $error['error'] ?? 'Unknown error',
                'naics' => $error['naics'] ?? null,
                'type' => $error['type'] ?? $error['error_type'] ?? null,
                'status_code' => $error['status_code'] ?? null,
                'details' => $error['details'] ?? $error['response_body'] ?? null,
            ];
        }, array_values($errors));
    }

    /**
     * Determine response type based on NAICS success/failure counts.
     *
     * @param  int  $succeeded  Number of NAICS that succeeded
     * @param  int  $failed  Number of NAICS that failed
     * @return string Response type: 'success', 'partial_success', or 'failure'
     */
    public function determineStatus(int $succeeded, int $failed): string
    {
        if ($failed === 0) {
            return 'success';
        }

        if ($succeeded > 0) {
            return 'partial_success';
        }

        return 'failure';
    }

    /**
     * Build response automatically based on results.
     *
     * @param  array  $opportunities  Array of opportunities
     * @param  array  $metadata  Performance and query metadata
     * @param  array  $errors  Array of errors (optional)
     * @return array Standardized response
     */
    public function build(array $opportunities, array $metadata = [], array $errors = []): array
    {
        $succeeded = $metadata['naics_succeeded'] ?? 0;
        $failed = $metadata['naics_failed'] ?? 0;

        $status = $this->determineStatus($succeeded, $failed);

        return match ($status) {
            'success' => $this->success($opportunities, $metadata),
            'partial_success' => $this->partialSuccess($opportunities, $metadata, $errors),
            'failure' => $this->failure(
                $errors[0]['message'] ?? $errors[0]['error'] ?? 'All NAICS queries failed',
                $metadata,
                array_slice($errors, 1) // Skip first error as it's used as primary
            ),
        };
    }

    /**
     * Extract errors from fetch results.
     *
     * @param  array  $fetchResults  Results from SamMultiNaicsFetcher
     * @return array Array of formatted errors
     */
    public function extractErrors(array $fetchResults): array
    {
        $errors = [];

        foreach ($fetchResults as $result) {
            if (! ($result['success'] ?? false)) {
                $errors[] = [
                    'message' => $result['error'] ?? 'Unknown error',
                    'naics' => $result['naics'] ?? null,
                    'type' => $result['error_type'] ?? null,
                    'status_code' => $result['status_code'] ?? null,
                    'details' => $result['response_body'] ?? null,
                ];
            }
        }

        return $errors;
    }

    /**
     * Add performance warnings to metadata.
     *
     * @param  array  $metadata  Existing metadata
     * @param  array  $warnings  Performance warnings
     * @return array Metadata with warnings added
     */
    public function addWarnings(array $metadata, array $warnings): array
    {
        if (empty($warnings)) {
            return $metadata;
        }

        $metadata['warnings'] = $warnings;
        $metadata['warnings_count'] = count($warnings);

        return $metadata;
    }

    /**
     * Add deduplication stats to metadata.
     *
     * @param  array  $metadata  Existing metadata
     * @param  array  $dedupStats  Deduplication statistics
     * @return array Metadata with dedup stats added
     */
    public function addDeduplicationStats(array $metadata, array $dedupStats): array
    {
        $metadata['duplicates_removed'] = $dedupStats['duplicates_removed'] ?? 0;
        $metadata['deduplication_rate'] = $dedupStats['deduplication_rate'] ?? '0%';
        $metadata['count_before_dedup'] = $dedupStats['total_before_dedup'] ?? 0;

        return $metadata;
    }

    /**
     * Check if response indicates success.
     *
     * @param  array  $response  Response to check
     * @return bool True if status is 'success'
     */
    public function isSuccess(array $response): bool
    {
        return ($response['status'] ?? '') === 'success';
    }

    /**
     * Check if response indicates partial success.
     *
     * @param  array  $response  Response to check
     * @return bool True if status is 'partial_success'
     */
    public function isPartialSuccess(array $response): bool
    {
        return ($response['status'] ?? '') === 'partial_success';
    }

    /**
     * Check if response indicates failure.
     *
     * @param  array  $response  Response to check
     * @return bool True if status is 'failure'
     */
    public function isFailure(array $response): bool
    {
        return ($response['status'] ?? '') === 'failure';
    }

    /**
     * Get opportunities from response.
     *
     * @param  array  $response  Response
     * @return array Opportunities array (empty if failure)
     */
    public function getOpportunities(array $response): array
    {
        return $response['opportunities'] ?? [];
    }

    /**
     * Get metadata from response.
     *
     * @param  array  $response  Response
     * @return array Metadata array
     */
    public function getMetadata(array $response): array
    {
        return $response['metadata'] ?? [];
    }

    /**
     * Get errors from response.
     *
     * @param  array  $response  Response
     * @return array Errors array (empty if success)
     */
    public function getErrors(array $response): array
    {
        return $response['errors'] ?? [];
    }
}
