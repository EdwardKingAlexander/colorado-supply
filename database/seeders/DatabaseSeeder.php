<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(5)->create();

        $this->call(AdminUserSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);

        Vendor::factory()->count(10)->create();
        Category::factory()->count(5)->create();
        Product::factory()->count(50)->create();

        $this->call(CompanySeeder::class);
    }
}
