<?php

namespace Tests\Feature\ActivityLogging;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_opportunity_logs_only_the_scoped_attributes(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $this->actingAs($owner, 'web');

        $opportunity = $this->makeOpportunity($owner);

        $entry = ActivityLog::query()
            ->where('subject_type', Opportunity::class)
            ->where('subject_id', $opportunity->id)
            ->where('event', 'created')
            ->firstOrFail();

        $this->assertSame('crm', $entry->log_name);
        $this->assertTrue($entry->causer->is($owner));
        $this->assertSame($company->id, $entry->company_id);
        $this->assertSame(
            ['amount', 'owner_id', 'pipeline_id', 'stage_id', 'status'],
            $this->sortedKeys($entry->properties['attributes'] ?? [])
        );
    }

    public function test_moving_stage_logs_old_and_new_without_description_noise(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner, 'web');
        $opportunity = $this->makeOpportunity($owner);

        // Fresh instance, like a new request would load: DB defaults
        // (amount 0) must not show up as a change on the next update.
        $opportunity->refresh();

        $newStage = Stage::create([
            'pipeline_id' => $opportunity->pipeline_id,
            'name' => 'Negotiation',
        ]);
        $oldStageId = $opportunity->stage_id;
        $opportunity->update(['stage_id' => $newStage->id, 'description' => 'unscoped noise']);

        $entry = ActivityLog::query()
            ->where('subject_type', Opportunity::class)
            ->where('subject_id', $opportunity->id)
            ->where('event', 'updated')
            ->firstOrFail();

        $this->assertSame(['stage_id' => $newStage->id], $entry->properties['attributes']);
        $this->assertSame(['stage_id' => $oldStageId], $entry->properties['old']);
    }

    private function makeOpportunity(User $owner): Opportunity
    {
        $customer = Customer::factory()->create(['owner_id' => $owner->id]);
        $pipeline = Pipeline::create(['name' => 'Test Pipeline']);
        $stage = Stage::create(['pipeline_id' => $pipeline->id, 'name' => 'New']);

        return Opportunity::create([
            'customer_id' => $customer->id,
            'pipeline_id' => $pipeline->id,
            'stage_id' => $stage->id,
            'title' => 'Test deal',
            'owner_id' => $owner->id,
            'created_by' => $owner->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return list<string>
     */
    private function sortedKeys(array $attributes): array
    {
        $keys = array_keys($attributes);
        sort($keys);

        return $keys;
    }
}
