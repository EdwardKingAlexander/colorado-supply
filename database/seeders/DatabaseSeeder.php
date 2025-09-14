<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Product;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        Artisan::call('make:filament-user',
    [
        '--name' => 'Edward',
        '--email' => 'edward@rockymountainweb.design',
        '--password' => 'password',
    ]);

        Vendor::factory()->count(10)->create();
        Category::factory()->count(5)->create();
        Product::factory()->count(50)->create();

    }
}
