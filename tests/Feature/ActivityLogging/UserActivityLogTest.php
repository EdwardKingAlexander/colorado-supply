<?php

namespace Tests\Feature\ActivityLogging;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_user_logs_only_name_and_email(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor, 'web');

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $entry = ActivityLog::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'created')
            ->firstOrFail();

        $this->assertSame('users', $entry->log_name);
        $this->assertTrue($entry->causer->is($actor));
        $this->assertSame($company->id, $entry->company_id);
        $this->assertSame(['email', 'name'], $this->sortedKeys($entry->properties['attributes'] ?? []));
    }

    public function test_updating_a_user_excludes_sensitive_fields(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor, 'web');
        $user = User::factory()->create();

        $user->update(['name' => 'New Name', 'password' => 'new-secret-password']);

        $entry = ActivityLog::query()
            ->where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->firstOrFail();

        $this->assertSame(['name' => 'New Name'], $entry->properties['attributes']);
        $this->assertArrayNotHasKey('password', $entry->properties['attributes']);
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
