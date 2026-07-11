<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\VendorContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorContact>
 */
class VendorContactFactory extends Factory
{
    protected $model = VendorContact::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'name' => fake()->name(),
            'job_title' => fake()->jobTitle(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'mobile_phone' => fake()->phoneNumber(),
            'notes' => fake()->optional()->sentence(),
            'is_preferred' => false,
        ];
    }

    public function preferred(): static
    {
        return $this->state(fn () => ['is_preferred' => true]);
    }
}
