<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->city().' Branch';

        return [
            'company_id' => Company::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
        ];
    }

    public function pickup(): static
    {
        return $this->state(fn () => [
            'pickup_enabled' => true,
            'address' => ['line1' => '100 Supply Way', 'city' => 'Denver', 'state' => 'CO', 'postal_code' => '80216'],
            'phone' => '303-555-0100',
            'hours' => 'Mon–Fri 7am–5pm',
            'pickup_instructions' => 'Use the will-call door on the north side.',
        ]);
    }
}
