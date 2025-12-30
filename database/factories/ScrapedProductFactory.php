<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScrapedProduct>
 */
class ScrapedProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $domain = $this->faker->domainName();
        $url = 'https://'.$domain.'/products/'.strtolower($this->faker->bothify('???-####'));
        $price = $this->faker->randomFloat(2, 10, 999);

        $nsn = $this->faker->numerify('####-##-###-####');
        $cageCode = strtoupper($this->faker->bothify('?####'));

        return [
            'source_url' => $url,
            'vendor_domain' => $domain,
            'title' => $this->faker->words(3, true),
            'sku' => strtoupper($this->faker->bothify('???-####')),
            'nsn' => $this->faker->boolean(30) ? $nsn : null, // 30% chance of having NSN
            'cage_code' => $this->faker->boolean(30) ? $cageCode : null, // 30% chance of having CAGE code
            'milspec' => $this->faker->boolean(20) ? 'MIL-STD-'.$this->faker->numberBetween(100, 999) : null, // 20% chance of having mil-spec
            'price' => '$'.number_format($price, 2),
            'price_numeric' => $price,
            'html_cache_path' => null,
            'raw_data' => [
                'success' => true,
                'product' => [
                    'title' => $this->faker->words(3, true),
                    'sku' => strtoupper($this->faker->bothify('???-####')),
                    'nsn' => $this->faker->boolean(30) ? $nsn : null,
                    'cage_code' => $this->faker->boolean(30) ? $cageCode : null,
                    'milspec' => $this->faker->boolean(20) ? 'MIL-STD-'.$this->faker->numberBetween(100, 999) : null,
                    'price' => '$'.number_format($price, 2),
                    'price_numeric' => $price,
                ],
            ],
            'status' => 'pending',
            'product_id' => null,
            'imported_by' => null,
            'imported_at' => null,
            'import_notes' => null,
        ];
    }

    /**
     * Indicate that the scraped product has been imported.
     */
    public function imported(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'imported',
            'product_id' => Product::factory(),
            'imported_by' => Admin::factory(),
            'imported_at' => now(),
            'import_notes' => 'Imported via factory',
        ]);
    }

    /**
     * Indicate that the scraped product import failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'import_notes' => 'Import failed via factory',
        ]);
    }

    /**
     * Indicate that the scraped product was ignored.
     */
    public function ignored(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ignored',
            'import_notes' => 'Ignored via factory',
        ]);
    }

    /**
     * Set a specific vendor domain.
     */
    public function fromVendor(string $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'vendor_domain' => $domain,
            'source_url' => "https://{$domain}/products/".strtolower($this->faker->bothify('???-####')),
        ]);
    }
}
