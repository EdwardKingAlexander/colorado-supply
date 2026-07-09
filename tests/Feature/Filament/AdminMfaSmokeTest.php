<?php

namespace Tests\Feature\Filament;

use App\Console\Commands\ResetAdminMultiFactorAuthentication;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMfaSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_implements_required_mfa_contracts_and_persists_secrets_encrypted(): void
    {
        $admin = Admin::factory()->create();

        $admin->saveAppAuthenticationSecret('SECRETSEED123');
        $admin->saveAppAuthenticationRecoveryCodes(['code-1', 'code-2']);
        $admin->toggleEmailAuthentication(true);

        $this->assertSame('SECRETSEED123', $admin->fresh()->getAppAuthenticationSecret());
        $this->assertSame(['code-1', 'code-2'], $admin->fresh()->getAppAuthenticationRecoveryCodes());
        $this->assertTrue($admin->fresh()->hasEmailAuthentication());
        $this->assertSame($admin->email, $admin->getAppAuthenticationHolderName());

        // Confirm encryption at rest: the raw DB value must not equal the plaintext secret.
        $rawValue = \DB::table('admins')->where('id', $admin->id)->value('app_authentication_secret');
        $this->assertNotSame('SECRETSEED123', $rawValue);
    }

    public function test_reset_mfa_command_clears_enrollment(): void
    {
        $admin = Admin::factory()->create();
        $admin->saveAppAuthenticationSecret('SECRETSEED123');
        $admin->saveAppAuthenticationRecoveryCodes(['code-1']);
        $admin->toggleEmailAuthentication(true);

        $this->artisan(ResetAdminMultiFactorAuthentication::class, ['email' => $admin->email])
            ->expectsConfirmation(
                "This will disable all multi-factor authentication for {$admin->email}, requiring them to re-enroll on next login. Continue?",
                'yes'
            )
            ->assertExitCode(0);

        $admin->refresh();

        $this->assertNull($admin->getAppAuthenticationSecret());
        $this->assertNull($admin->getAppAuthenticationRecoveryCodes());
        $this->assertFalse($admin->hasEmailAuthentication());
    }
}
