<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\StripeEventResource\Pages\ListStripeEvents;
use App\Models\StripeEvent;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StripeEventResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');

        $user = User::factory()->create();

        $role = Role::query()->firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web'],
            ['name' => 'super_admin', 'guard_name' => 'web']
        );

        $user->assignRole($role);

        $this->actingAs($user);
    }

    public function test_admin_can_list_stripe_events(): void
    {
        $events = collect([
            StripeEvent::create([
                'stripe_event_id' => 'evt_test_1',
                'type' => 'checkout.session.completed',
                'payload' => ['id' => 'evt_test_1'],
                'processed_at' => now(),
            ]),
            StripeEvent::create([
                'stripe_event_id' => 'evt_test_2',
                'type' => 'payment_intent.payment_failed',
                'payload' => ['id' => 'evt_test_2'],
            ]),
        ]);

        Livewire::test(ListStripeEvents::class)
            ->assertCanSeeTableRecords($events);
    }
}
