<?php

namespace Tests\Feature\Filament;

use App\Models\Admin;
use App\Models\User;
use App\Notifications\VerifyEmailAddress;
use App\Support\EmailVerificationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_the_email_verification_toggle_in_the_user_menu(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin');

        $items = filament()->getPanel('admin')->getUserMenuItems();

        $this->assertArrayHasKey('toggleEmailVerification', $items);
        $this->assertSame('Disable Email Verification', $items['toggleEmailVerification']->getLabel());
    }

    public function test_toggling_the_action_flips_the_verification_setting(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        $this->assertTrue(EmailVerificationSettings::isEnabled());

        $panel = filament()->getPanel('admin');
        $panel->getUserMenuItems()['toggleEmailVerification']->call();

        $this->assertFalse(EmailVerificationSettings::isEnabled());

        $panel->getUserMenuItems()['toggleEmailVerification']->call();

        $this->assertTrue(EmailVerificationSettings::isEnabled());
    }

    public function test_disabling_via_the_action_mutes_verification_for_new_registrations(): void
    {
        Notification::fake();

        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');

        filament()->getPanel('admin')->getUserMenuItems()['toggleEmailVerification']->call();
        auth('admin')->logout();

        $this->post('/register', [
            'name' => 'Toggle Test',
            'email' => 'toggle-test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        Notification::assertNotSentTo(
            User::firstWhere('email', 'toggle-test@example.com'),
            VerifyEmailAddress::class
        );

        $this->get(route('sam.favorites'))->assertOk();
    }
}
