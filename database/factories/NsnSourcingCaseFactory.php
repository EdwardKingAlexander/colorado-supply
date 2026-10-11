<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Company;
use App\Models\NsnSourcingCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NsnSourcingCase>
 */
class NsnSourcingCaseFactory extends Factory
{
    public function definition(): array
    {
        $niin = fake()->numerify('#########');
        $part = strtoupper(fake()->bothify('??-####-##'));
        $cage = strtoupper(fake()->bothify('#?###'));

        return [
            'company_id' => Company::factory(),
            'pub_log_import_id' => 1,
            'nsn_reference_id' => null,
            'release_date' => '2026-10-01',
            'nsn' => '5940'.$niin,
            'niin' => $niin,
            'part_number' => $part,
            'part_search_key' => $part,
            'cage_code' => $cage,
            'item_name' => 'TERMINAL BOARD',
            'status' => 'open',
            'production_state' => 'unknown',
            'opened_by_type' => Admin::class,
            'opened_by_id' => Admin::factory(),
            'open_key' => fn (array $a) => NsnSourcingCase::openKey($a['niin'], $a['part_search_key'], $a['cage_code']),
        ];
    }
}
