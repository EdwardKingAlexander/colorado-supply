<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Counts accounts still relying on email-code MFA, which is no longer allowed
 * (NIST SP 800-63B forbids email as an authenticator). Run on production to
 * see who must switch to an authenticator app, and to know when the legacy
 * email-code transition path can be removed (both counts at zero).
 */
class MfaEmailMethodReport extends Command
{
    protected $signature = 'mfa:email-method-report {--list : Also print the email address of each account}';

    protected $description = 'Report customers and admins still using email-code two-factor authentication.';

    public function handle(): int
    {
        $customers = User::query()
            ->whereNotNull('two_factor_confirmed_at')
            ->where('two_factor_method', 'email')
            ->orderBy('email')
            ->pluck('email');

        // Admins whose only factor was Filament email codes. They have no
        // authenticator app, so the panel will make them set one up at next login.
        $admins = Admin::query()
            ->where('has_email_authentication', true)
            ->whereNull('app_authentication_secret')
            ->orderBy('email')
            ->pluck('email');

        $this->info("Customers using email codes: {$customers->count()}");
        $this->info("Admins whose only factor is email codes: {$admins->count()}");

        if ($this->option('list')) {
            $this->printList('Customers', $customers->all());
            $this->printList('Admins', $admins->all());
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $emails
     */
    private function printList(string $heading, array $emails): void
    {
        if ($emails === []) {
            return;
        }

        $this->newLine();
        $this->line("{$heading}:");

        foreach ($emails as $email) {
            $this->line("  {$email}");
        }
    }
}
