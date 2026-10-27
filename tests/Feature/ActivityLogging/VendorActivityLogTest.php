<?php

namespace Tests\Feature\ActivityLogging;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorActivityLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function complianceFields(): array
    {
        return [
            'sam_expiration_date',
            'sam_number',
            'w9_date',
            'tax_id',
            'insurance_expiration_date',
            'insurance_policy_number',
            'cage_code',
            'duns_number',
            'naics_code',
        ];
    }

    public function test_creating_a_vendor_logs_only_compliance_fields(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor, 'web');

        $company = Company::factory()->create();
        $vendorUser = User::factory()->create(['company_id' => $company->id]);
        $vendor = Vendor::factory()->create([
            'user_id' => $vendorUser->id,
            'cage_code' => 'ABC12',
            'sam_number' => 'SAM-001',
        ]);

        $entry = ActivityLog::query()
            ->where('subject_type', Vendor::class)
            ->where('subject_id', $vendor->id)
            ->where('event', 'created')
            ->firstOrFail();

        $this->assertSame('vendors', $entry->log_name);
        $this->assertTrue($entry->causer->is($actor));
        $this->assertSame($company->id, $entry->company_id);

        $keys = array_keys($entry->properties['attributes'] ?? []);
        $this->assertEmpty(
            array_diff($keys, $this->complianceFields()),
            'Created entry must not contain unscoped attributes.'
        );
        $this->assertContains('cage_code', $keys);
        $this->assertContains('sam_number', $keys);
    }

    public function test_updating_a_vendor_logs_only_the_changed_compliance_field(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor, 'web');
        $vendor = Vendor::factory()->create(['cage_code' => 'ABC12']);

        $vendor->update(['cage_code' => 'XYZ99', 'name' => 'unscoped noise']);

        $entry = ActivityLog::query()
            ->where('subject_type', Vendor::class)
            ->where('subject_id', $vendor->id)
            ->where('event', 'updated')
            ->firstOrFail();

        $this->assertSame(['cage_code' => 'XYZ99'], $entry->properties['attributes']);
        $this->assertSame(['cage_code' => 'ABC12'], $entry->properties['old']);
    }
}
