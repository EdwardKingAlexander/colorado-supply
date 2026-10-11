<?php

namespace Tests\Feature\Filament;

use App\Filament\Widgets\StoreStatusWidget;
use App\Models\Admin;
use App\Models\User;
use App\Support\StoreAvailability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StoreStatusWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_widget_appears_on_the_admin_dashboard(): void
    {
        // The admin panel requires MFA enrollment before it renders any page.
        $admin = Admin::factory()->create();
        $admin->toggleEmailAuthentication(true);

        $this->actingAs($admin->fresh(), 'admin')
            ->get('/admin')
            ->assertSuccessful()
            ->assertSee('Store Status')
            ->assertSee('Preview maintenance page');
    }

    public function test_switch_reflects_current_store_state(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');

        Livewire::test(StoreStatusWidget::class)
            ->assertSet('storeEnabled', true)
            ->assertSee('Customers can browse the store');

        StoreAvailability::set(false);

        Livewire::test(StoreStatusWidget::class)
            ->assertSet('storeEnabled', false)
            ->assertSee('Closed — Under Maintenance');
    }

    public function test_switching_off_sends_customers_to_maintenance_page_but_not_admins(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        Livewire::test(StoreStatusWidget::class)
            ->set('storeEnabled', false)
            ->assertNotified('Store Closed');

        $this->assertFalse(StoreAvailability::isEnabled());

        $this->get('/store')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->component('Store/StoreIndex'));

        auth('admin')->logout();

        $this->actingAs(User::factory()->create(), 'web')
            ->get('/store')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->component('Store/Unavailable'));
    }

    public function test_switching_back_on_restores_the_store_for_customers(): void
    {
        StoreAvailability::set(false);

        $this->actingAs(Admin::factory()->create(), 'admin');

        Livewire::test(StoreStatusWidget::class)
            ->set('storeEnabled', true)
            ->assertNotified('Store Open');

        auth('admin')->logout();

        $this->actingAs(User::factory()->create(), 'web')
            ->get('/store')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->component('Store/StoreIndex'));
    }

    public function test_maintenance_preview_is_admin_only(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin')
            ->get('/store/maintenance-preview')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Store/Unavailable')
                ->where('contactEmail', StoreAvailability::CONTACT_EMAIL));

        auth('admin')->logout();

        $this->actingAs(User::factory()->create(), 'web')
            ->get('/store/maintenance-preview')
            ->assertRedirect();
    }
}
