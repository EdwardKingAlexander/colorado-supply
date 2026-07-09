<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivitylogCauserResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_causer_resolves_to_the_authenticated_web_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web');

        $activity = activity()->log('web guard test event');

        $this->assertTrue($activity->causer->is($user));
    }

    public function test_causer_resolves_to_the_authenticated_admin(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $activity = activity()->log('admin guard test event');

        $this->assertTrue($activity->causer->is($admin));
    }

    public function test_causer_is_null_when_no_guard_is_authenticated(): void
    {
        $activity = activity()->log('unauthenticated test event');

        $this->assertNull($activity->causer);
    }
}
