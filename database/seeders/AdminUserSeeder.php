<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        Admin::updateOrCreate(
            ['email' => 'edward@rockymountainweb.design'],
            [
                'name' => 'Edward',
                'password' => $password,
            ]
        );

        User::updateOrCreate(
            ['email' => 'edward@rockymountainweb.design'],
            [
                'name' => 'Edward',
                'password' => $password,
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
    }
}
