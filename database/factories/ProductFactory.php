<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        // Use an existing vendor/category when possible
        $vendorId = Vendor::query()->inRandomOrder()->value('id') ?? Vendor::factory()->create()->id;
        $categoryId = Category::query()->inRandomOrder()->value('id'); // nullable is fine

        $name = $this->faker->unique()->words(3, true);
        $slug = Str::slug($name).'-'.Str::lower(Str::random(6));
        $imageSeed = Str::slug($name).'-'.Str::lower(Str::random(4));

        return [
            'vendor_id' => $vendorId,
            'category_id' => $categoryId,

            'name' => $name,
            // Vendor-scoped unique: append a short random suffix to avoid collisions
            'slug' => $slug,
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-########')),

            // Optional manufacturer/commerce ids
            'mpn' => strtoupper($this->faker->bothify('MPN-#####')),
            'gtin' => $this->faker->randomElement([null, $this->faker->ean13()]),

            'description' => $this->faker->paragraph(),
            'image' => sprintf('https://picsum.photos/seed/%s/800/800', $imageSeed),

            // Pricing
            'cost' => $this->faker->randomFloat(2, 1, 150),
            'list_price' => $this->faker->randomFloat(2, 10, 600),
            'price' => $this->faker->randomFloat(2, 5, 550),

            // Inventory
            'stock' => $this->faker->numberBetween(0, 250),
            'reorder_point' => $this->faker->randomElement([null, 10, 25, 50]),
            'lead_time_days' => $this->faker->randomElement([null, 3, 7, 14]),

            'is_active' => $this->faker->boolean(85),

            // Logistics (optional)
            'weight_g' => $this->faker->randomElement([null, 250, 500, 1200]),
            'length_mm' => $this->faker->randomElement([null, 50, 100, 200]),
            'width_mm' => $this->faker->randomElement([null, 20, 40, 80]),
            'height_mm' => $this->faker->randomElement([null, 20, 40, 80]),

            // Gov/classification (optional)
            'unspsc' => null,
            'psc_fsc' => null,
            'country_of_origin' => 'US',

            'meta' => null,
        ];
    }
}
