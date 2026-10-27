<?php

namespace Tests\Feature\ActivityLogging;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_customer_logs_only_the_scoped_attributes(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor, 'web');

        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['owner_id' => $owner->id]);

        $entry = ActivityLog::query()
            ->where('subject_type', Customer::class)
            ->where('subject_id', $customer->id)
            ->where('event', 'created')
            ->firstOrFail();

        $this->assertSame('crm', $entry->log_name);
        $this->assertTrue($entry->causer->is($actor));
        $this->assertSame($company->id, $entry->company_id);

        $keys = array_keys($entry->properties['attributes'] ?? []);
        $this->assertEmpty(
            array_diff($keys, ['owner_id', 'name', 'email', 'phone', 'company', 'website']),
            'Created entry must not contain unscoped attributes.'
        );
        $this->assertContains('owner_id', $keys);
    }

    public function test_updating_a_customer_excludes_address_blobs(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner, 'web');
        $customer = Customer::factory()->create(['owner_id' => $owner->id]);

        $newOwner = User::factory()->create();
        $customer->update([
            'owner_id' => $newOwner->id,
            'billing_address' => ['line1' => '123 Main St'],
        ]);

        $entry = ActivityLog::query()
            ->where('subject_type', Customer::class)
            ->where('subject_id', $customer->id)
            ->where('event', 'updated')
            ->firstOrFail();

        $this->assertSame(['owner_id' => $newOwner->id], $entry->properties['attributes']);
        $this->assertSame(['owner_id' => $owner->id], $entry->properties['old']);
    }
}
