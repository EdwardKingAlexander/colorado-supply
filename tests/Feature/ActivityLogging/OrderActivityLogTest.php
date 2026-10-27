<?php

namespace Tests\Feature\ActivityLogging;

use App\Enums\OrderStatus;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_order_logs_only_the_scoped_attributes(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor, 'web');

        $company = Company::factory()->create();
        $order = Order::factory()->create(['company_id' => $company->id]);

        $entry = ActivityLog::query()
            ->where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->where('event', 'created')
            ->firstOrFail();

        $this->assertSame('store', $entry->log_name);
        $this->assertTrue($entry->causer->is($actor));
        $this->assertSame($company->id, $entry->company_id);

        $keys = array_keys($entry->properties['attributes'] ?? []);
        $this->assertEmpty(
            array_diff($keys, ['status', 'payment_status', 'fulfillment_status', 'grand_total', 'portal_user_id']),
            'Created entry must not contain unscoped attributes.'
        );
        $this->assertContains('status', $keys);
    }

    public function test_updating_an_order_logs_the_old_and_new_values(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor, 'web');
        $order = Order::factory()->create();

        $order->update(['status' => OrderStatus::Confirmed, 'notes' => 'unscoped noise']);

        $entry = ActivityLog::query()
            ->where('subject_type', Order::class)
            ->where('subject_id', $order->id)
            ->where('event', 'updated')
            ->firstOrFail();

        $this->assertSame('store', $entry->log_name);
        $this->assertTrue($entry->causer->is($actor));
        $this->assertSame(['status' => OrderStatus::Confirmed->value], $entry->properties['attributes']);
        $this->assertSame(['status' => OrderStatus::Draft->value], $entry->properties['old']);
    }

    public function test_touching_only_unscoped_order_fields_writes_no_entry(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor, 'web');
        $order = Order::factory()->create();

        $order->update(['notes' => 'just a note']);

        $this->assertSame(
            0,
            ActivityLog::query()
                ->where('subject_type', Order::class)
                ->where('subject_id', $order->id)
                ->where('event', 'updated')
                ->count()
        );
    }
}
