<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ResetCustomerMultiFactorAuthentication extends Command
{
    protected $signature = 'user:reset-mfa {email}';

    protected $description = 'Clear a locked-out customer\'s multi-factor authentication enrollment so they can log in and re-enroll.';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No user found with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        if (! $this->confirm("This will disable all multi-factor authentication for {$user->email}, requiring them to re-enroll on next login. Continue?")) {
            return self::SUCCESS;
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_method = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        $user->mfaCodes()->delete();

        $this->info("Multi-factor authentication reset for {$user->email}. They will be prompted to re-enroll on next login.");

        return self::SUCCESS;
    }
}
