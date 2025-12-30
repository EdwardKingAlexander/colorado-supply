<?php

namespace App\Mcp\Servers\Business\Tools;

use App\Mcp\Servers\Tool;
use App\Models\Vendor;
use Carbon\Carbon;

class ComplianceAuditTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'compliance-audit';

    /**
     * The tool's description.
     */
    protected string $description = 'Audit vendor compliance documents (SAM, W-9, Insurance, CAGE, DUNS, NAICS) and identify missing or outdated items';

    /**
     * Required compliance documents and their typical renewal periods (in days).
     */
    protected array $requiredDocuments = [
        'sam_registration' => [
            'name' => 'SAM Registration',
            'renewal_period' => 365, // Annual
            'fields' => ['sam_expiration_date', 'sam_number'],
        ],
        'w9' => [
            'name' => 'W-9 Form',
            'renewal_period' => 1095, // Every 3 years
            'fields' => ['w9_date', 'tax_id'],
        ],
        'insurance' => [
            'name' => 'Insurance Certificate',
            'renewal_period' => 365, // Annual
            'fields' => ['insurance_expiration_date', 'insurance_policy_number'],
        ],
        'cage_code' => [
            'name' => 'CAGE Code',
            'renewal_period' => null, // No expiration
            'fields' => ['cage_code'],
        ],
        'duns_number' => [
            'name' => 'DUNS Number',
            'renewal_period' => null, // No expiration
            'fields' => ['duns_number'],
        ],
        'naics_code' => [
            'name' => 'NAICS Code',
            'renewal_period' => null, // No expiration
            'fields' => ['naics_code'],
        ],
    ];

    /**
     * Define the tool's input schema.
     */
    protected function inputSchema(): array
    {
        return [
            'vendor_id' => [
                'type' => 'integer',
                'description' => 'Specific vendor ID to audit (omit to audit all vendors)',
                'required' => false,
            ],
            'expiration_warning_days' => [
                'type' => 'integer',
                'description' => 'Number of days before expiration to flag as "expiring soon" (default: 30)',
                'required' => false,
            ],
            'include_compliant' => [
                'type' => 'boolean',
                'description' => 'Include fully compliant vendors in report (default: false)',
                'required' => false,
            ],
        ];
    }

    /**
     * Execute the tool.
     */
    public function execute(array $inputs): string
    {
        $vendorId = $inputs['vendor_id'] ?? null;
        $warningDays = $inputs['expiration_warning_days'] ?? 30;
        $includeCompliant = $inputs['include_compliant'] ?? false;

        try {
            // Get vendors to audit
            $vendors = $vendorId
                ? Vendor::where('id', $vendorId)->get()
                : Vendor::all();

            if ($vendors->isEmpty()) {
                return json_encode([
                    'success' => false,
                    'error' => 'No vendors found',
                ], JSON_PRETTY_PRINT);
            }

            // Audit each vendor
            $auditResults = [];
            $summary = [
                'total_vendors' => $vendors->count(),
                'compliant' => 0,
                'non_compliant' => 0,
                'expiring_soon' => 0,
                'missing_documents' => 0,
            ];

            foreach ($vendors as $vendor) {
                $vendorAudit = $this->auditVendor($vendor, $warningDays);

                // Update summary
                if ($vendorAudit['compliance_status'] === 'compliant') {
                    $summary['compliant']++;
                } else {
                    $summary['non_compliant']++;
                }

                if ($vendorAudit['expiring_soon_count'] > 0) {
                    $summary['expiring_soon']++;
                }

                if ($vendorAudit['missing_count'] > 0) {
                    $summary['missing_documents']++;
                }

                // Add to results if not compliant or if including compliant
                if ($vendorAudit['compliance_status'] !== 'compliant' || $includeCompliant) {
                    $auditResults[] = $vendorAudit;
                }
            }

            // Prepare final report
            $report = [
                'success' => true,
                'audit_date' => now()->toIso8601String(),
                'summary' => $summary,
                'vendors' => $auditResults,
                'settings' => [
                    'expiration_warning_days' => $warningDays,
                    'include_compliant' => $includeCompliant,
                ],
            ];

            // Save report to State
            $this->saveReport($report);

            return json_encode($report, JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage(),
            ], JSON_PRETTY_PRINT);
        }
    }

    /**
     * Audit a single vendor for compliance.
     */
    protected function auditVendor($vendor, int $warningDays): array
    {
        $issues = [];
        $expiringSoon = [];
        $compliant = [];
        $missing = [];

        foreach ($this->requiredDocuments as $key => $config) {
            $status = $this->checkDocument($vendor, $config, $warningDays);

            if ($status['status'] === 'missing') {
                $missing[] = $status;
            } elseif ($status['status'] === 'expired') {
                $issues[] = $status;
            } elseif ($status['status'] === 'expiring_soon') {
                $expiringSoon[] = $status;
            } elseif ($status['status'] === 'compliant') {
                $compliant[] = $status;
            }
        }

        // Determine overall compliance status
        $complianceStatus = 'compliant';
        if (count($missing) > 0 || count($issues) > 0) {
            $complianceStatus = 'non_compliant';
        } elseif (count($expiringSoon) > 0) {
            $complianceStatus = 'expiring_soon';
        }

        return [
            'vendor_id' => $vendor->id,
            'vendor_name' => $vendor->name ?? 'Unknown',
            'compliance_status' => $complianceStatus,
            'missing_count' => count($missing),
            'expired_count' => count($issues),
            'expiring_soon_count' => count($expiringSoon),
            'compliant_count' => count($compliant),
            'documents' => [
                'missing' => $missing,
                'expired' => $issues,
                'expiring_soon' => $expiringSoon,
                'compliant' => $compliant,
            ],
        ];
    }

    /**
     * Check a specific document for compliance.
     */
    protected function checkDocument($vendor, array $config, int $warningDays): array
    {
        $result = [
            'document' => $config['name'],
            'status' => 'compliant',
            'message' => null,
            'fields_checked' => $config['fields'],
        ];

        // Check if all required fields exist
        $missingFields = [];
        foreach ($config['fields'] as $field) {
            if (empty($vendor->{$field})) {
                $missingFields[] = $field;
            }
        }

        if (! empty($missingFields)) {
            $result['status'] = 'missing';
            $result['message'] = 'Missing required fields: '.implode(', ', $missingFields);

            return $result;
        }

        // Check expiration if applicable
        if ($config['renewal_period'] !== null) {
            $expirationField = $this->findExpirationField($config['fields']);

            if ($expirationField && isset($vendor->{$expirationField})) {
                $fieldValue = Carbon::parse($vendor->{$expirationField});
                $now = Carbon::now();

                // If field is an actual expiration date, use it directly
                // If field is a signing/issue date, calculate expiration by adding renewal period
                if (str_contains($expirationField, 'expiration')) {
                    $expirationDate = $fieldValue;
                } else {
                    // This is a signing/issue date, calculate expiration
                    $expirationDate = $fieldValue->copy()->addDays($config['renewal_period']);
                }

                if ($expirationDate->isPast()) {
                    $result['status'] = 'expired';
                    $result['message'] = 'Expired on '.$expirationDate->toDateString().' ('.$expirationDate->diffForHumans().')';
                    $result['expiration_date'] = $expirationDate->toDateString();
                } elseif ($expirationDate->diffInDays($now) <= $warningDays) {
                    $result['status'] = 'expiring_soon';
                    $result['message'] = 'Expires on '.$expirationDate->toDateString().' (in '.$expirationDate->diffInDays($now).' days)';
                    $result['expiration_date'] = $expirationDate->toDateString();
                    $result['days_until_expiration'] = $expirationDate->diffInDays($now);
                } else {
                    $result['status'] = 'compliant';
                    $result['message'] = 'Valid until '.$expirationDate->toDateString();
                    $result['expiration_date'] = $expirationDate->toDateString();
                    $result['days_until_expiration'] = $expirationDate->diffInDays($now);
                }
            }
        } else {
            $result['status'] = 'compliant';
            $result['message'] = 'Verified';
        }

        return $result;
    }

    /**
     * Find the expiration date field from a list of fields.
     */
    protected function findExpirationField(array $fields): ?string
    {
        foreach ($fields as $field) {
            if (str_contains($field, 'expiration') || str_contains($field, 'date')) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Save the compliance report to State directory.
     */
    protected function saveReport(array $report): void
    {
        $stateDir = app_path('Mcp/Servers/Business/State');
        $reportFile = $stateDir.'/compliance-report.json';

        // Ensure State directory exists
        if (! file_exists($stateDir)) {
            mkdir($stateDir, 0755, true);
        }

        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
    }
}
