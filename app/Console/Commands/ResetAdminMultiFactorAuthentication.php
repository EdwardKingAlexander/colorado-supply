<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;

class ResetAdminMultiFactorAuthentication extends Command
{
    protected $signature = 'admin:reset-mfa {email} {--force : Skip the confirmation prompt (required when running non-interactively, e.g. from Forge\'s Commands panel)}';

    protected $description = 'Clear a locked-out admin\'s multi-factor authentication enrollment so they can log in and re-enroll.';

    public function handle(): int
    {
        $admin = Admin::where('email', $this->argument('email'))->first();

        if (! $admin) {
            $this->error("No admin found with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        // Non-interactive runs answer "no" to confirm(), so without --force they
        // must fail loudly rather than exit 0 having reset nothing.
        if (! $this->option('force') && ! $this->confirm("This will disable all multi-factor authentication for {$admin->email}, requiring them to re-enroll on next login. Continue?")) {
            $this->warn('Nothing was changed. Re-run with --force to reset without the prompt.');

            return self::FAILURE;
        }

        $admin->saveAppAuthenticationSecret(null);
        $admin->saveAppAuthenticationRecoveryCodes(null);
        $admin->toggleEmailAuthentication(false);

        $this->info("Multi-factor authentication reset for {$admin->email}. They will be prompted to re-enroll on next login.");

        return self::SUCCESS;
    }
}
