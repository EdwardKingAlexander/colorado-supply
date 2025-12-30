<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Merges and deduplicates SAM.gov opportunities from multiple NAICS queries.
 *
 * This service:
 * - Merges opportunities from multiple per-NAICS responses
 * - Deduplicates by notice_id (unique identifier)
 * - Prefers most recent lastModifiedDate when duplicates exist
 * - Tags opportunities with source_naics for debugging
 * - Handles missing notice_id gracefully
 * - Returns metadata about deduplication
 */
class SamOpportunityDeduplicator
{
    /**
     * Merge and deduplicate opportunities from multiple NAICS results.
     *
     * @param  array  $naicsResults  Array of per-NAICS API responses
     * @return array Deduplicated opportunities with metadata
     */
    public function merge(array $naicsResults): array
    {
        // First, flatten all opportunities from successful queries
        $mergedData = $this->mergeResults($naicsResults);

        // Then deduplicate by notice_id
        $deduplicatedData = $this->deduplicateByNoticeId($mergedData['opportunities']);

        // Calculate final metadata
        return [
            'opportunities' => $deduplicatedData['opportunities'],
            'total_count' => $mergedData['total_count'],
            'count_before_dedup' => $mergedData['count_before_dedup'],
            'total_after_dedup' => $deduplicatedData['total_after_dedup'],
            'duplicates_removed' => $deduplicatedData['duplicates_removed'],
            'naics_queried' => $mergedData['naics_queried'],
            'naics_succeeded' => $mergedData['naics_succeeded'],
            'naics_failed' => $mergedData['naics_failed'],
        ];
    }

    /**
     * Merge opportunities from multiple NAICS results.
     *
     * @param  array  $naicsResults  Array of per-NAICS API responses
     * @return array Merged opportunities with metadata
     */
    protected function mergeResults(array $naicsResults): array
    {
        $allOpportunities = [];
        $totalCount = 0;
        $naicsQueried = [];
        $naicsSucceeded = [];
        $naicsFailed = [];

        foreach ($naicsResults as $result) {
            $naicsCode = $result['naics'] ?? 'unknown';
            $naicsQueried[] = $naicsCode;

            if ($result['success'] ?? false) {
                $naicsSucceeded[] = $naicsCode;

                if (! empty($result['opportunities'])) {
                    foreach ($result['opportunities'] as $opp) {
                        // Tag with source NAICS for debugging
                        $opp['source_naics'] = $naicsCode;
                        $allOpportunities[] = $opp;
                    }

                    $totalCount += $result['count'] ?? count($result['opportunities']);
                }
            } else {
                $naicsFailed[] = $naicsCode;
            }
        }

        return [
            'opportunities' => $allOpportunities,
            'total_count' => $totalCount,
            'count_before_dedup' => count($allOpportunities),
            'naics_queried' => $naicsQueried,
            'naics_succeeded' => $naicsSucceeded,
            'naics_failed' => $naicsFailed,
        ];
    }

    /**
     * Deduplicate opportunities by notice_id.
     *
     * Strategy:
     * - Use notice_id as unique key
     * - If duplicate exists, prefer the one with most recent lastModifiedDate
     * - If no lastModifiedDate, prefer the first occurrence
     * - Opportunities without notice_id are always kept (assumed unique)
     *
     * @param  array  $opportunities  Array of opportunities
     * @return array Deduplicated opportunities with metadata
     */
    protected function deduplicateByNoticeId(array $opportunities): array
    {
        $countBeforeDedup = count($opportunities);

        // Track seen notice_ids and their corresponding opportunity indices
        $seen = [];
        $deduplicated = [];
        $duplicatesRemoved = 0;

        foreach ($opportunities as $opp) {
            $noticeId = $opp['notice_id'] ?? null;

            // Always keep opportunities without notice_id (assume unique)
            if (empty($noticeId)) {
                $deduplicated[] = $opp;

                Log::debug('SAM.gov opportunity without notice_id kept', [
                    'service' => 'SamOpportunityDeduplicator',
                    'title' => $opp['title'] ?? 'Unknown',
                    'source_naics' => $opp['source_naics'] ?? null,
                ]);

                continue;
            }

            // Check if we've seen this notice_id before
            if (isset($seen[$noticeId])) {
                // Duplicate found - compare dates
                $existingOpp = $deduplicated[$seen[$noticeId]];
                $shouldReplace = $this->shouldReplaceExisting($existingOpp, $opp);

                if ($shouldReplace) {
                    // Replace with newer version
                    $deduplicated[$seen[$noticeId]] = $opp;

                    Log::debug('SAM.gov duplicate replaced with newer version', [
                        'service' => 'SamOpportunityDeduplicator',
                        'notice_id' => $noticeId,
                        'old_source_naics' => $existingOpp['source_naics'] ?? null,
                        'new_source_naics' => $opp['source_naics'] ?? null,
                        'old_modified' => $existingOpp['lastModifiedDate'] ?? null,
                        'new_modified' => $opp['lastModifiedDate'] ?? null,
                    ]);
                } else {
                    // Keep existing version
                    Log::debug('SAM.gov duplicate skipped (existing is newer)', [
                        'service' => 'SamOpportunityDeduplicator',
                        'notice_id' => $noticeId,
                        'existing_source_naics' => $existingOpp['source_naics'] ?? null,
                        'duplicate_source_naics' => $opp['source_naics'] ?? null,
                    ]);
                }

                $duplicatesRemoved++;
            } else {
                // First occurrence - add to deduplicated list
                $seen[$noticeId] = count($deduplicated);
                $deduplicated[] = $opp;
            }
        }

        $countAfterDedup = count($deduplicated);

        // Log warning if high deduplication rate
        if ($countBeforeDedup > 0) {
            $dedupRate = ($duplicatesRemoved / $countBeforeDedup) * 100;

            if ($dedupRate > 20) {
                Log::warning('High deduplication rate detected', [
                    'service' => 'SamOpportunityDeduplicator',
                    'deduplication_rate' => round($dedupRate, 2).'%',
                    'duplicates_removed' => $duplicatesRemoved,
                    'total_before_dedup' => $countBeforeDedup,
                    'suggestion' => 'Consider reviewing NAICS code selection for overlap',
                ]);
            }
        }

        return [
            'opportunities' => array_values($deduplicated), // Re-index array
            'total_after_dedup' => $countAfterDedup,
            'duplicates_removed' => $duplicatesRemoved,
        ];
    }

    /**
     * Determine if we should replace existing opportunity with new one.
     *
     * Compares lastModifiedDate if available, otherwise keeps existing.
     *
     * @param  array  $existing  Existing opportunity
     * @param  array  $new  New opportunity (potential replacement)
     * @return bool True if new should replace existing
     */
    protected function shouldReplaceExisting(array $existing, array $new): bool
    {
        $existingDate = $existing['lastModifiedDate'] ?? null;
        $newDate = $new['lastModifiedDate'] ?? null;

        // If neither has a date, keep existing (first occurrence wins)
        if (empty($existingDate) && empty($newDate)) {
            return false;
        }

        // If only new has a date, prefer it
        if (empty($existingDate) && ! empty($newDate)) {
            return true;
        }

        // If only existing has a date, keep it
        if (! empty($existingDate) && empty($newDate)) {
            return false;
        }

        // Both have dates - compare them
        try {
            return strtotime($newDate) > strtotime($existingDate);
        } catch (\Exception $e) {
            // If date parsing fails, keep existing
            Log::warning('Failed to parse lastModifiedDate during deduplication', [
                'service' => 'SamOpportunityDeduplicator',
                'existing_date' => $existingDate,
                'new_date' => $newDate,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get deduplication statistics from results.
     *
     * @param  array  $mergedResults  Results from merge()
     * @return array Statistics
     */
    public function getStats(array $mergedResults): array
    {
        return [
            'total_before_dedup' => $mergedResults['count_before_dedup'] ?? 0,
            'total_after_dedup' => $mergedResults['total_after_dedup'] ?? 0,
            'duplicates_removed' => $mergedResults['duplicates_removed'] ?? 0,
            'deduplication_rate' => $this->calculateDeduplicationRate($mergedResults),
            'naics_queried_count' => count($mergedResults['naics_queried'] ?? []),
            'naics_succeeded_count' => count($mergedResults['naics_succeeded'] ?? []),
            'naics_failed_count' => count($mergedResults['naics_failed'] ?? []),
        ];
    }

    /**
     * Calculate deduplication rate as percentage.
     *
     * @param  array  $mergedResults  Results from merge()
     * @return float Deduplication rate (0-100)
     */
    protected function calculateDeduplicationRate(array $mergedResults): float
    {
        $before = $mergedResults['count_before_dedup'] ?? 0;
        $removed = $mergedResults['duplicates_removed'] ?? 0;

        if ($before === 0) {
            return 0.0;
        }

        return round(($removed / $before) * 100, 2);
    }
}
