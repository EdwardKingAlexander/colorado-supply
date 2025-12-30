<?php

use App\Mcp\Servers\Business\Tools\ComplianceAuditTool;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function () {
    // Clean up any existing vendors for clean test state
    Vendor::query()->delete();

    // Create a default user for vendor relationships
    $this->user = User::factory()->create();

    // Ensure State directory exists
    $stateDir = app_path('Mcp/Servers/Business/State');
    if (! File::exists($stateDir)) {
        File::makeDirectory($stateDir, 0755, true);
    }
});

afterEach(function () {
    // Clean up test report file
    $reportFile = app_path('Mcp/Servers/Business/State/compliance-report.json');
    if (File::exists($reportFile)) {
        File::delete($reportFile);
    }
});

test('identifies fully compliant vendor', function () {
    $vendor = Vendor::create([
        'user_id' => $this->user->id,
        'name' => 'Compliant Vendor Inc',
        'email' => 'compliant@test.com',
        'slug' => Str::slug('Compliant Vendor Inc'),
        'sam_expiration_date' => now()->addMonths(6),
        'sam_number' => 'SAM-12345',
        'w9_date' => now()->subMonths(6),
        'tax_id' => '12-3456789',
        'insurance_expiration_date' => now()->addMonths(8),
        'insurance_policy_number' => 'INS-98765',
        'cage_code' => 'CAGE123',
        'duns_number' => '123456789',
        'naics_code' => '541330',
    ]);

    $tool = new ComplianceAuditTool;
    $result = $tool->execute([
        'vendor_id' => $vendor->id,
        'include_compliant' => true,
    ]);

    $json = json_decode($result, true);

    expect($json['success'])->toBeTrue()
        ->and($json['summary']['compliant'])->toBe(1)
        ->and($json['summary']['non_compliant'])->toBe(0)
        ->and($json['vendors'][0]['compliance_status'])->toBe('compliant')
        ->and($json['vendors'][0]['missing_count'])->toBe(0)
        ->and($json['vendors'][0]['expired_count'])->toBe(0);
});

test('identifies vendor with expired insurance', function () {
    $vendor = Vendor::create([
        'user_id' => $this->user->id,
        'name' => 'Expired Insurance Co',
        'email' => 'expired@test.com',
        'slug' => Str::slug('Expired Insurance Co'),
        'sam_expiration_date' => now()->addMonths(6),
        'sam_number' => 'SAM-67890',
        'w9_date' => now()->subMonths(6),
        'tax_id' => '98-7654321',
        'insurance_expiration_date' => now()->subMonths(3), // Expired 3 months ago
        'insurance_policy_number' => 'INS-11111',
        'cage_code' => 'CAGE456',
        'duns_number' => '987654321',
        'naics_code' => '541330',
    ]);

    $tool = new ComplianceAuditTool;
    $result = $tool->execute([
        'vendor_id' => $vendor->id,
        'include_compliant' => true,
    ]);

    $json = json_decode($result, true);

    expect($json['success'])->toBeTrue()
        ->and($json['summary']['compliant'])->toBe(0)
        ->and($json['summary']['non_compliant'])->toBe(1)
        ->and($json['vendors'][0]['compliance_status'])->toBe('non_compliant')
        ->and($json['vendors'][0]['expired_count'])->toBe(1)
        ->and($json['vendors'][0]['documents']['expired'])->toHaveCount(1)
        ->and($json['vendors'][0]['documents']['expired'][0]['document'])->toBe('Insurance Certificate')
        ->and($json['vendors'][0]['documents']['expired'][0]['status'])->toBe('expired');
});

test('identifies vendor with documents expiring soon', function () {
    $vendor = Vendor::create([
        'user_id' => $this->user->id,
        'name' => 'Expiring Soon LLC',
        'email' => 'expiring@test.com',
        'slug' => Str::slug('Expiring Soon LLC'),
        'sam_expiration_date' => now()->addDays(15), // Expires in 15 days
        'sam_number' => 'SAM-99999',
        'w9_date' => now()->subMonths(6),
        'tax_id' => '11-2233445',
        'insurance_expiration_date' => now()->addMonths(8),
        'insurance_policy_number' => 'INS-22222',
        'cage_code' => 'CAGE789',
        'duns_number' => '111222333',
        'naics_code' => '541330',
    ]);

    $tool = new ComplianceAuditTool;
    $result = $tool->execute([
        'vendor_id' => $vendor->id,
        'expiration_warning_days' => 30,
        'include_compliant' => true,
    ]);

    $json = json_decode($result, true);

    expect($json['success'])->toBeTrue()
        ->and($json['summary']['expiring_soon'])->toBe(1)
        ->and($json['vendors'][0]['compliance_status'])->toBe('expiring_soon')
        ->and($json['vendors'][0]['expiring_soon_count'])->toBe(1)
        ->and($json['vendors'][0]['documents']['expiring_soon'])->toHaveCount(1)
        ->and($json['vendors'][0]['documents']['expiring_soon'][0]['document'])->toBe('SAM Registration');
});

test('identifies vendor with missing documents', function () {
    $vendor = Vendor::create([
        'user_id' => $this->user->id,
        'name' => 'Missing Docs Corp',
        'email' => 'missing@test.com',
        'slug' => Str::slug('Missing Docs Corp'),
        'sam_expiration_date' => now()->addMonths(6),
        'sam_number' => 'SAM-11111',
        // Missing: W-9, Insurance, CAGE, DUNS, NAICS
    ]);

    $tool = new ComplianceAuditTool;
    $result = $tool->execute([
        'vendor_id' => $vendor->id,
        'include_compliant' => true,
    ]);

    $json = json_decode($result, true);

    expect($json['success'])->toBeTrue()
        ->and($json['summary']['non_compliant'])->toBe(1)
        ->and($json['vendors'][0]['compliance_status'])->toBe('non_compliant')
        ->and($json['vendors'][0]['missing_count'])->toBe(5)
        ->and($json['vendors'][0]['documents']['missing'])->toHaveCount(5);
});

test('audits all vendors when no vendor_id specified', function () {
    // Create multiple vendors with different compliance statuses
    Vendor::create([
        'user_id' => $this->user->id,
        'name' => 'Vendor 1',
        'email' => 'v1@test.com',
        'slug' => 'vendor-1',
        'sam_expiration_date' => now()->addMonths(6),
        'sam_number' => 'SAM-1',
        'w9_date' => now()->subMonths(6),
        'tax_id' => '11-1111111',
        'insurance_expiration_date' => now()->addMonths(8),
        'insurance_policy_number' => 'INS-1',
        'cage_code' => 'CAGE1',
        'duns_number' => '111111111',
        'naics_code' => '541330',
    ]);

    Vendor::create([
        'user_id' => $this->user->id,
        'name' => 'Vendor 2',
        'email' => 'v2@test.com',
        'slug' => 'vendor-2',
        'sam_expiration_date' => now()->subMonths(1), // Expired
        'sam_number' => 'SAM-2',
    ]);

    Vendor::create([
        'user_id' => $this->user->id,
        'name' => 'Vendor 3',
        'email' => 'v3@test.com',
        'slug' => 'vendor-3',
        // Missing all documents
    ]);

    $tool = new ComplianceAuditTool;
    $result = $tool->execute([
        'include_compliant' => true,
    ]);

    $json = json_decode($result, true);

    expect($json['success'])->toBeTrue()
        ->and($json['summary']['total_vendors'])->toBe(3)
        ->and($json['summary']['compliant'])->toBe(1)
        ->and($json['summary']['non_compliant'])->toBe(2)
        ->and($json['vendors'])->toHaveCount(3);
});

test('excludes compliant vendors when include_compliant is false', function () {
    Vendor::create([
        'user_id' => $this->user->id,
        'name' => 'Compliant Vendor',
        'email' => 'compliant@test.com',
        'slug' => 'compliant-vendor',
        'sam_expiration_date' => now()->addMonths(6),
        'sam_number' => 'SAM-1',
        'w9_date' => now()->subMonths(6),
        'tax_id' => '11-1111111',
        'insurance_expiration_date' => now()->addMonths(8),
        'insurance_policy_number' => 'INS-1',
        'cage_code' => 'CAGE1',
        'duns_number' => '111111111',
        'naics_code' => '541330',
    ]);

    Vendor::create([
        'user_id' => $this->user->id,
        'name' => 'Non-Compliant Vendor',
        'email' => 'noncompliant@test.com',
        'slug' => 'non-compliant-vendor',
        // Missing all documents
    ]);

    $tool = new ComplianceAuditTool;
    $result = $tool->execute([
        'include_compliant' => false,
    ]);

    $json = json_decode($result, true);

    expect($json['success'])->toBeTrue()
        ->and($json['summary']['total_vendors'])->toBe(2)
        ->and($json['summary']['compliant'])->toBe(1)
        ->and($json['summary']['non_compliant'])->toBe(1)
        ->and($json['vendors'])->toHaveCount(1) // Only non-compliant vendor shown
        ->and($json['vendors'][0]['vendor_name'])->toBe('Non-Compliant Vendor');
});

test('saves report to State directory', function () {
    Vendor::create([
        'user_id' => $this->user->id,
        'name' => 'Test Vendor',
        'email' => 'test@test.com',
        'slug' => 'test-vendor',
        'sam_expiration_date' => now()->addMonths(6),
        'sam_number' => 'SAM-1',
    ]);

    $tool = new ComplianceAuditTool;
    $tool->execute([]);

    $reportFile = app_path('Mcp/Servers/Business/State/compliance-report.json');

    expect(File::exists($reportFile))->toBeTrue();

    $reportContent = json_decode(File::get($reportFile), true);
    expect($reportContent['success'])->toBeTrue()
        ->and($reportContent)->toHaveKey('audit_date')
        ->and($reportContent)->toHaveKey('summary')
        ->and($reportContent)->toHaveKey('vendors');
});

test('handles non-existent vendor gracefully', function () {
    $tool = new ComplianceAuditTool;
    $result = $tool->execute([
        'vendor_id' => 99999, // Non-existent ID
    ]);

    $json = json_decode($result, true);

    expect($json['success'])->toBeFalse()
        ->and($json['error'])->toBe('No vendors found');
});
