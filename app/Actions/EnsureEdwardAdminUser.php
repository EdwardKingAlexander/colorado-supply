<?php

namespace App\Actions;

use App\Models\Admin;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

class EnsureEdwardAdminUser
{
    public function __invoke(): bool
    {
        if (! App::environment(['local', 'development'])) {
            return false;
        }

        $admin = Admin::updateOrCreate(
            ['email' => 'edward@rockymountainweb.design'],
            [
                'name' => 'Edward',
                'password' => Hash::make('password'),
            ],
        );

        return $admin->wasRecentlyCreated;
    }
}
