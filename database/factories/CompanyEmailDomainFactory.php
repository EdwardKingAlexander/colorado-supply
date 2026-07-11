<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Company;
use App\Models\CompanyEmailDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyEmailDomain>
 */
class CompanyEmailDomainFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'domain' => fake()->unique()->domainName(),
            'is_primary' => false,
            'approved_by_admin_id' => Admin::factory(),
            'approved_at' => now(),
        ];
    }
}
