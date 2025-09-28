<?php

namespace App\Actions;

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class EnsureEdwardAdminUser
{
    public function __invoke(): bool
    {
        $admin = Admin::firstOrCreate(
            ['email' => 'Edward@cogovsupply.com'],
            [
                'name' => 'Edward',
                'password' => Hash::make('JillieBean247!'),
            ],
        );

        return $admin->wasRecentlyCreated;
    }
}
