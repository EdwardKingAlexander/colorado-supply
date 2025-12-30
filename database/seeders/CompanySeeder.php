<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Location;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::create([
            'name' => 'Colorado Supply & Procurement LLC',
            'slug' => 'colorado-supply-procurement-llc',
        ]);

        Location::create([
            'company_id' => $company->id,
            'name' => 'Main Store',
            'slug' => 'main-store',
        ]);

        User::whereNull('company_id')->update(['company_id' => $company->id]);

        $products = Product::all();
        $company->products()->attach($products->pluck('id'));
    }
}
