<?php

namespace Database\Seeders;

use App\Actions\EnsureEdwardAdminUser;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        (new EnsureEdwardAdminUser())();
    }
}
