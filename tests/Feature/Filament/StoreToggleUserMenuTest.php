<?php

namespace Tests\Feature\Filament;

use App\Models\Admin;
use App\Models\User;
use App\Support\McpSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreToggleUserMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_toggle_store_action_in_the_user_menu(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin');

        $items = filament()->getPanel('admin')->getUserMenuItems();

        $this->assertArrayHasKey('toggleStoreAvailability', $items);
        $this->assertSame('Disable Store', $items['toggleStoreAvailability']->getLabel());
    }

    public function test_toggling_the_action_flips_store_settings(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $this->assertTrue(McpSettings::for('store-settings', ['enabled' => true])['enabled']);

        $panel = filament()->getPanel('admin');
        $action = $panel->getUserMenuItems()['toggleStoreAvailability'];
        $action->call();

        $this->assertFalse(McpSettings::for('store-settings', ['enabled' => true])['enabled']);

        $action = $panel->getUserMenuItems()['toggleStoreAvailability'];
        $action->call();

        $this->assertTrue(McpSettings::for('store-settings', ['enabled' => true])['enabled']);
    }

    public function test_disabling_the_store_via_the_action_shows_unavailable_page_to_non_admins(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $panel = filament()->getPanel('admin');
        $panel->getUserMenuItems()['toggleStoreAvailability']->call();

        auth('admin')->logout();

        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->get('/store')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->component('Store/Unavailable'));
    }
}
