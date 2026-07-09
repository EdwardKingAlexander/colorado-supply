<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;

class ResetAdminMultiFactorAuthentication extends Command
{
    protected $signature = 'admin:reset-mfa {email}';

    protected $description = 'Clear a locked-out admin\'s multi-factor authentication enrollment so they can log in and re-enroll.';

    public function handle(): int
    {
        $admin = Admin::where('email', $this->argument('email'))->first();

        if (! $admin) {
            $this->error("No admin found with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        if (! $this->confirm("This will disable all multi-factor authentication for {$admin->email}, requiring them to re-enroll on next login. Continue?")) {
            return self::SUCCESS;
        }

        $admin->saveAppAuthenticationSecret(null);
        $admin->saveAppAuthenticationRecoveryCodes(null);
        $admin->toggleEmailAuthentication(false);

        $this->info("Multi-factor authentication reset for {$admin->email}. They will be prompted to re-enroll on next login.");

        return self::SUCCESS;
    }
}
