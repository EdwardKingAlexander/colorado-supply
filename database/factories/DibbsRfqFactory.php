<?php

namespace Database\Factories;

use App\Models\DibbsRfq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DibbsRfq>
 */
class DibbsRfqFactory extends Factory
{
    public function definition(): array
    {
        $fsc = fake()->randomElement(['5305', '5310', '5320', '4820', '5975']);
        $niin = fake()->numerify('#########');
        $solicitation = 'SPE'.fake()->bothify('#??##').'T'.fake()->numerify('####');

        return [
            'solicitation_number' => $solicitation,
            'purchase_request' => fake()->unique()->numerify('##########'),
            'nsn' => $fsc.$niin,
            'item_type' => '1',
            'fsc' => $fsc,
            'niin' => $niin,
            'nomenclature' => strtoupper(fake()->words(2, true)),
            'quantity' => fake()->numberBetween(1, 500),
            'unit_of_issue' => 'EA',
            'return_by_date' => fake()->dateTimeBetween('now', '+20 days'),
            'buyer_code' => 'PCPQD',
            'amsc' => fake()->randomElement(['G', 'Z', 'C', 'D']),
            'set_aside' => fake()->randomElement(['Y', 'N']),
            'set_aside_percent' => 100,
            'automated_award' => true,
            'raw_record' => str_pad($solicitation, 140),
        ];
    }
}
