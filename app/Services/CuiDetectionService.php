<?php

namespace App\Services;

use App\Models\ContractDocument;
use App\Models\ContractDocumentAuditLog;

class CuiDetectionService
{
    /**
     * CUI marking patterns to detect.
     * Based on 32 CFR Part 2002 - Controlled Unclassified Information
     */
    protected array $patterns = [
        // Standard CUI markings
        '/\bCUI\b/' => 'CUI',
        '/\bCONTROLLED\s+UNCLASSIFIED\s+INFORMATION\b/i' => 'CUI',

        // CUI with category
        '/\bCUI\s*\/\/\s*([A-Z\-]+)/i' => 'CUI_CATEGORY',

        // Specific CUI categories relevant to procurement
        '/\bCUI\s*\/\/\s*SP-PROCURE\b/i' => 'SP-PROCURE',
        '/\bCUI\s*\/\/\s*SP-BUDG\b/i' => 'SP-BUDG',
        '/\bCUI\s*\/\/\s*PRVCY\b/i' => 'PRVCY',
        '/\bCUI\s*\/\/\s*PROPIN\b/i' => 'PROPIN',
        '/\bCUI\s*\/\/\s*ITAR\b/i' => 'ITAR',
        '/\bCUI\s*\/\/\s*EXPT\b/i' => 'EXPT',

        // Legacy markings that may still appear
        '/\bFOR\s+OFFICIAL\s+USE\s+ONLY\b/i' => 'FOUO_LEGACY',
        '/\bFOUO\b/' => 'FOUO_LEGACY',
        '/\bSENSITIVE\s+BUT\s+UNCLASSIFIED\b/i' => 'SBU_LEGACY',
        '/\bSBU\b/' => 'SBU_LEGACY',
        '/\bLIMITED\s+DISTRIBUTION\b/i' => 'LIMITED_DIST',

        // Source selection / procurement sensitive
        '/\bSOURCE\s+SELECTION\s+(?:INFORMATION|SENSITIVE)\b/i' => 'SOURCE_SELECTION',
        '/\bPROCUREMENT\s+SENSITIVE\b/i' => 'PROC_SENSITIVE',

        // Proprietary markings
        '/\bPROPRIETARY\s+INFORMATION\b/i' => 'PROPRIETARY',
        '/\bTRADE\s+SECRET\b/i' => 'TRADE_SECRET',

        // Export controlled
        '/\bEXPORT\s+CONTROLLED\b/i' => 'EXPORT_CONTROLLED',
        '/\bITAR\s+CONTROLLED\b/i' => 'ITAR',
        '/\bEAR\s+CONTROLLED\b/i' => 'EAR',
    ];

    /**
     * Scan text content for CUI markings.
     */
    public function scanText(string $text): array
    {
        $detectedCategories = [];

        foreach ($this->patterns as $pattern => $category) {
            if (preg_match($pattern, $text, $matches)) {
                // For CUI_CATEGORY pattern, extract the actual category
                if ($category === 'CUI_CATEGORY' && isset($matches[1])) {
                    $detectedCategories[] = strtoupper($matches[1]);
                } else {
                    $detectedCategories[] = $category;
                }
            }
        }

        return array_unique($detectedCategories);
    }

    /**
     * Scan a document's filename for CUI indicators.
     */
    public function scanFilename(string $filename): array
    {
        $detectedCategories = [];

        // Check filename for common CUI indicators
        $filenamePatterns = [
            '/\bCUI\b/i' => 'CUI',
            '/\bFOUO\b/i' => 'FOUO_LEGACY',
            '/\bSBU\b/i' => 'SBU_LEGACY',
            '/\bITAR\b/i' => 'ITAR',
            '/\bEAR\b/i' => 'EAR',
            '/\bPROPRIETARY\b/i' => 'PROPRIETARY',
            '/\bSENSITIVE\b/i' => 'POTENTIALLY_SENSITIVE',
        ];

        foreach ($filenamePatterns as $pattern => $category) {
            if (preg_match($pattern, $filename)) {
                $detectedCategories[] = $category;
            }
        }

        return array_unique($detectedCategories);
    }

    /**
     * Update a document's CUI status based on detected categories.
     */
    public function updateDocumentCuiStatus(ContractDocument $document, array $categories): void
    {
        $hasCui = ! empty($categories);

        $oldValues = [
            'cui_detected' => $document->cui_detected,
            'cui_categories' => $document->cui_categories,
        ];

        $document->update([
            'cui_detected' => $hasCui,
            'cui_categories' => $hasCui ? $categories : null,
        ]);

        if ($hasCui) {
            $document->logAction(
                ContractDocumentAuditLog::ACTION_CUI_DETECTED,
                ['categories' => $categories],
                $oldValues,
                ['cui_detected' => true, 'cui_categories' => $categories]
            );
        }
    }

    /**
     * Quick check on filename only (for upload-time detection).
     */
    public function quickScan(ContractDocument $document): array
    {
        return $this->scanFilename($document->original_filename);
    }

    /**
     * Get human-readable description of a CUI category.
     */
    public static function getCategoryDescription(string $category): string
    {
        return match ($category) {
            'CUI' => 'Controlled Unclassified Information',
            'SP-PROCURE' => 'Procurement Sensitive',
            'SP-BUDG' => 'Budget Sensitive',
            'PRVCY' => 'Privacy Information',
            'PROPIN' => 'Proprietary Information',
            'ITAR' => 'International Traffic in Arms Regulations',
            'EXPT' => 'Export Controlled',
            'EAR' => 'Export Administration Regulations',
            'FOUO_LEGACY' => 'For Official Use Only (Legacy)',
            'SBU_LEGACY' => 'Sensitive But Unclassified (Legacy)',
            'LIMITED_DIST' => 'Limited Distribution',
            'SOURCE_SELECTION' => 'Source Selection Information',
            'PROC_SENSITIVE' => 'Procurement Sensitive',
            'PROPRIETARY' => 'Proprietary Information',
            'TRADE_SECRET' => 'Trade Secret',
            'EXPORT_CONTROLLED' => 'Export Controlled',
            'POTENTIALLY_SENSITIVE' => 'Potentially Sensitive (Review Required)',
            default => $category,
        };
    }

    /**
     * Get all known CUI categories.
     */
    public static function getAllCategories(): array
    {
        return [
            'CUI' => 'Controlled Unclassified Information',
            'SP-PROCURE' => 'Procurement Sensitive',
            'SP-BUDG' => 'Budget Sensitive',
            'PRVCY' => 'Privacy Information',
            'PROPIN' => 'Proprietary Information',
            'ITAR' => 'International Traffic in Arms Regulations',
            'EAR' => 'Export Administration Regulations',
            'EXPT' => 'Export Controlled',
            'FOUO_LEGACY' => 'For Official Use Only (Legacy)',
            'SBU_LEGACY' => 'Sensitive But Unclassified (Legacy)',
            'SOURCE_SELECTION' => 'Source Selection Information',
            'PROPRIETARY' => 'Proprietary Information',
        ];
    }
}
