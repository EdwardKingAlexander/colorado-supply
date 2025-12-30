<?php

namespace App\Support;

use App\Models\GsaFilter;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Resolves SAM.gov v2 API query parameters by merging GsaFilter defaults with runtime overrides.
 *
 * This service ensures:
 * - Required NAICS codes are always present (from DB or override)
 * - Date formats comply with SAM.gov v2 API (MM/dd/yyyy)
 * - Notice types are converted to v2 single-letter codes
 * - All parameters are validated before returning
 */
class SamParameterResolver
{
    private SamSearchParameters $searchParams;

    public function __construct()
    {
        $this->searchParams = new SamSearchParameters;
    }

    /**
     * Resolve query parameters from GsaFilter defaults and runtime overrides.
     *
     * @param  array  $params  Runtime parameter overrides from MCP tool call
     * @return array Fully resolved parameter array ready for API calls
     *
     * @throws InvalidArgumentException If required parameters are missing or invalid
     */
    public function resolve(array $params = []): array
    {
        // Load NAICS codes from DB or override
        $naicsCodes = $this->resolveNaicsCodes($params);

        // Load PSC codes from DB or override (optional)
        $pscCodes = $this->resolvePscCodes($params);

        // Resolve notice types with v2 conversion
        $noticeTypes = $this->resolveNoticeTypes($params);
        $noticeTypeCodes = $this->convertNoticeTypesToV2Codes($noticeTypes);

        // Resolve place of performance (state code)
        $place = $this->resolvePlaceCode($params);

        // Resolve date range
        $daysBack = $params['days_back'] ?? 30;
        $this->validateDaysBack($daysBack);

        $dateRange = $this->resolveDateRange($params, $daysBack);

        // Resolve other parameters
        // Default to 100 so the UI shows a reasonable slice when limit isn't provided
        $limit = $params['limit'] ?? 100;
        $this->validateLimit($limit);

        $keywords = $this->resolveKeywords($params);
        $clearCache = $params['clearCache'] ?? false;
        $smallBusinessOnly = (bool) ($params['small_business_only'] ?? false);
        $setAsideCodes = $this->resolveSetAsides($params, $smallBusinessOnly);

        // Build query metadata for logging/debugging
        $queryMetadata = [
            'date_range' => "{$dateRange['posted_from']} to {$dateRange['posted_to']}",
            'naics_codes' => $naicsCodes,
            'psc_codes' => $pscCodes,
            'state_code' => $place ?? 'nationwide',
            'notice_types' => $noticeTypes,
            'keywords' => $keywords,
            'small_business_only' => $smallBusinessOnly,
            'set_aside_codes' => $setAsideCodes,
        ];

        return [
            'naics_codes' => $naicsCodes,
            'psc_codes' => $pscCodes,
            'notice_types' => $noticeTypes,
            'notice_type_codes' => $noticeTypeCodes,
            'place' => $place,
            'days_back' => $daysBack,
            'limit' => $limit,
            'keywords' => $keywords,
            'clearCache' => $clearCache,
            'set_aside_codes' => $setAsideCodes,
            'posted_from' => $dateRange['posted_from'],
            'posted_to' => $dateRange['posted_to'],
            'query_metadata' => $queryMetadata,
        ];
    }

    /**
     * Resolve NAICS codes from override or database defaults.
     *
     * @throws InvalidArgumentException If no NAICS codes are available
     */
    protected function resolveNaicsCodes(array $params): array
    {
        // Check for override
        if (isset($params['naics_override']) && ! empty($params['naics_override'])) {
            $naicsCodes = $params['naics_override'];

            // Validate it's an array
            if (! is_array($naicsCodes)) {
                throw new InvalidArgumentException('naics_override must be an array');
            }

            // Validate all entries are strings
            foreach ($naicsCodes as $code) {
                if (! is_string($code)) {
                    throw new InvalidArgumentException('All NAICS codes must be strings');
                }

                // Validate NAICS code format (should be 6 digits)
                if (! preg_match('/^\d{6}$/', $code)) {
                    throw new InvalidArgumentException("Invalid NAICS code format: {$code}. Expected 6 digits.");
                }
            }

            return $naicsCodes;
        }

        $defaults = array_merge($this->searchParams->naics_codes_primary, $this->searchParams->naics_codes_secondary);

        return $defaults;
    }

    /**
     * Resolve PSC codes from override or database defaults (optional).
     */
    protected function resolvePscCodes(array $params): array
    {
        // Check for override
        if (isset($params['psc_override']) && ! empty($params['psc_override'])) {
            $pscCodes = $params['psc_override'];

            // Validate it's an array
            if (! is_array($pscCodes)) {
                throw new InvalidArgumentException('psc_override must be an array');
            }

            return $pscCodes;
        }

        return $this->searchParams->psc_codes;
    }

    /**
     * Resolve set-aside codes from override or defaults.
     */
    protected function resolveSetAsides(array $params, bool $smallBusinessOnly): array
    {
        // If explicitly requesting small business only, force SBA
        if ($smallBusinessOnly) {
            return ['SBA'];
        }

        // Allow override of set_asides as array of strings
        if (isset($params['set_asides']) && is_array($params['set_asides'])) {
            return array_values(array_unique(array_filter(array_map([$this, 'normalizeSetAsideCode'], $params['set_asides']))));
        }

        // Default from config
        return array_values(array_unique(array_filter(array_map([$this, 'normalizeSetAsideCode'], $this->searchParams->set_asides))));
    }

    /**
     * Normalize human-friendly set-aside labels to API codes.
     */
    protected function normalizeSetAsideCode(string $code): ?string
    {
        $trimmed = strtoupper(trim($code));

        $map = [
            'SB' => 'SBA',
            'SBA' => 'SBA',
            'SMALL BUSINESS' => 'SBA',
            'WOSB' => 'WOSB',
            'EDWOSB' => 'EDWOSB',
            'SDVOSB' => 'SDVOSBC',
            'SDVOSBC' => 'SDVOSBC',
            '8(A)' => '8A',
            '8A' => '8A',
            'HUBZONE' => 'HUBZone',
            'HUB' => 'HUBZone',
        ];

        return $map[$trimmed] ?? $trimmed ?: null;
    }

    /**
     * Resolve notice types from parameters or use defaults.
     */
    protected function resolveNoticeTypes(array $params): array
    {
        $noticeTypes = $params['notice_type'] ?? $this->searchParams->notice_types;

        if (! is_array($noticeTypes)) {
            throw new InvalidArgumentException('notice_type must be an array');
        }

        return $noticeTypes;
    }

    /**
     * Convert notice types to SAM.gov v2 API single-letter codes.
     */
    protected function convertNoticeTypesToV2Codes(array $noticeTypes): array
    {
        $noticeTypeMap = [
            'Presolicitation' => 'p',
            'Solicitation' => 'o',
            'Combined Synopsis/Solicitation' => 'k',
            'Sources Sought' => 's',
        ];

        $ptypes = [];
        foreach ($noticeTypes as $type) {
            if (isset($noticeTypeMap[$type])) {
                $ptypes[] = $noticeTypeMap[$type];
            }
        }

        // Default to most common types if none matched
        if (empty($ptypes)) {
            $ptypes = ['o', 'p', 'k'];
        }

        return $ptypes;
    }

    /**
     * Resolve place of performance (state code).
     */
    protected function resolvePlaceCode(array $params): ?string
    {
        // Check if 'place' was explicitly provided (even if null)
        // If explicitly set to null, that means nationwide search (no state filter)
        if (array_key_exists('place', $params)) {
            $place = $params['place'];
        } else {
            // Not provided - use config default
            $place = $this->searchParams->place_of_performance;
        }

        if (empty($place) || $place === null) {
            return null;
        }

        // Normalize to uppercase
        $normalized = strtoupper(trim($place));

        // Validate it's a 2-letter state code
        if (! preg_match('/^[A-Z]{2}$/', $normalized)) {
            throw new InvalidArgumentException("Invalid state code: {$place}. Expected 2-letter state code (e.g., 'CO').");
        }

        return $normalized;
    }

    /**
     * Resolve date range for SAM.gov v2 API (MM/dd/yyyy format).
     */
    protected function resolveDateRange(array $params, int $daysBack): array
    {
        // Check if custom dates provided
        if (isset($params['posted_from']) && isset($params['posted_to'])) {
            $postedFrom = $this->validateAndFormatDate($params['posted_from']);
            $postedTo = $this->validateAndFormatDate($params['posted_to']);

            return [
                'posted_from' => $postedFrom,
                'posted_to' => $postedTo,
            ];
        }

        // Build date range from days_back
        $postedTo = Carbon::now()->format('m/d/Y');
        $postedFrom = Carbon::now()->subDays($daysBack)->format('m/d/Y');

        return [
            'posted_from' => $postedFrom,
            'posted_to' => $postedTo,
        ];
    }

    /**
     * Validate and format a date string to SAM.gov v2 format (MM/dd/yyyy).
     *
     * @throws InvalidArgumentException If date is invalid
     */
    protected function validateAndFormatDate(string $date): string
    {
        try {
            $carbon = Carbon::parse($date);

            return $carbon->format('m/d/Y');
        } catch (\Exception $e) {
            throw new InvalidArgumentException("Invalid date format: {$date}. Expected ISO 8601 (YYYY-MM-DD) or valid date string.");
        }
    }

    /**
     * Validate days_back parameter.
     *
     * @throws InvalidArgumentException If days_back is invalid
     */
    protected function validateDaysBack(int $daysBack): void
    {
        if ($daysBack < 1 || $daysBack > 365) {
            throw new InvalidArgumentException("Invalid days_back: {$daysBack}. Must be between 1 and 365.");
        }
    }

    /**
     * Validate limit parameter.
     *
     * @throws InvalidArgumentException If limit is invalid
     */
    protected function validateLimit(int $limit): void
    {
        if ($limit < 1 || $limit > 1000) {
            throw new InvalidArgumentException("Invalid limit: {$limit}. Must be between 1 and 1000.");
        }
    }

    /**
     * Resolve keywords from parameters or defaults.
     * Handles both array and comma-separated string formats.
     */
    protected function resolveKeywords(array $params): array
    {
        $keywords = $params['keywords'] ?? $this->searchParams->keywords;

        // If it's already an array, return it
        if (is_array($keywords)) {
            return array_values(array_filter(array_map('trim', $keywords)));
        }

        // If it's a string, split by comma
        if (is_string($keywords)) {
            if (empty(trim($keywords))) {
                return [];
            }

            $keywordArray = array_map('trim', explode(',', $keywords));

            return array_values(array_filter($keywordArray));
        }

        // Default to empty array
        return [];
    }
}
