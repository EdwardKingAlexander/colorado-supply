<?php

namespace App\Console\Commands;

use App\Actions\EnsureEdwardAdminUser as EnsureEdwardAdminUserAction;
use Illuminate\Console\Command;

class EnsureEdwardAdminUser extends Command
{
    protected $signature = 'ensure:filament-admin';

    protected $description = 'Ensure that the Edward Filament admin user exists.';

    public function handle(): int
    {
        $wasCreated = (new EnsureEdwardAdminUserAction())();

        if ($wasCreated) {
            $this->info('Created Edward Filament admin user.');
        } else {
            $this->info('Edward Filament admin user already exists.');
        }

        return self::SUCCESS;
    }
}
