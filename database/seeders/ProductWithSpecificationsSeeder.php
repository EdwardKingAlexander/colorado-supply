<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductWithSpecificationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing product data
        $this->command->info('Clearing existing products and attributes...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('product_attributes')->truncate();
        DB::table('products')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create vendor and user if they don't exist
        $user = User::firstOrCreate(
            ['email' => 'vendor@example.com'],
            [
                'name' => 'Demo Vendor',
                'password' => bcrypt('password'),
            ]
        );

        $vendor = Vendor::firstOrCreate(
            ['email' => 'vendor@coloradosupply.com'],
            [
                'user_id' => $user->id,
                'name' => 'Colorado Industrial Supply',
                'slug' => 'colorado-industrial-supply',
                'phone' => '555-0123',
                'description' => 'Premier industrial supplier',
            ]
        );

        // Create parent categories (like McMaster-Carr's main categories)
        $fasteningJoining = Category::firstOrCreate(
            ['slug' => 'fastening-joining'],
            ['name' => 'Fastening & Joining', 'description' => 'Fasteners, adhesives, and joining supplies', 'parent_id' => null]
        );

        $electricalCategory = Category::firstOrCreate(
            ['slug' => 'electrical-lighting'],
            ['name' => 'Electrical & Lighting', 'description' => 'Electrical components and lighting', 'parent_id' => null]
        );

        $toolsEquipment = Category::firstOrCreate(
            ['slug' => 'tools-equipment'],
            ['name' => 'Tools & Equipment', 'description' => 'Hand tools, power tools, and equipment', 'parent_id' => null]
        );

        $safetyCategory = Category::firstOrCreate(
            ['slug' => 'safety-janitorial'],
            ['name' => 'Safety & Janitorial', 'description' => 'Safety equipment and janitorial supplies', 'parent_id' => null]
        );

        // Create subcategories (like McMaster-Carr's subcategories)
        $fasteners = Category::firstOrCreate(
            ['slug' => 'hex-bolts'],
            ['name' => 'Hex Bolts', 'description' => 'Hex head bolts in various materials and sizes', 'parent_id' => $fasteningJoining->id]
        );

        $electrical = Category::firstOrCreate(
            ['slug' => 'wire-cable'],
            ['name' => 'Wire & Cable', 'description' => 'Electrical wire and cable', 'parent_id' => $electricalCategory->id]
        );

        $tools = Category::firstOrCreate(
            ['slug' => 'hand-tools'],
            ['name' => 'Hand Tools', 'description' => 'Wrenches, sockets, screwdrivers, and more', 'parent_id' => $toolsEquipment->id]
        );

        $safety = Category::firstOrCreate(
            ['slug' => 'ppe'],
            ['name' => 'Personal Protective Equipment', 'description' => 'Safety glasses, gloves, hard hats, and ear protection', 'parent_id' => $safetyCategory->id]
        );

        // Seed Fasteners
        $this->seedFasteners($vendor, $fasteners);

        // Seed Electrical Components
        $this->seedElectrical($vendor, $electrical);

        // Seed Tools
        $this->seedTools($vendor, $tools);

        // Seed Safety Equipment
        $this->seedSafety($vendor, $safety);

        $this->command->info('Products and specifications seeded successfully!');
    }

    protected function seedFasteners(Vendor $vendor, Category $category): void
    {
        $bolts = [
            ['size' => '1/4"', 'length' => '1"', 'thread' => '20 TPI', 'material' => 'Steel', 'finish' => 'Zinc Plated', 'grade' => 'Grade 5', 'price' => 0.45],
            ['size' => '1/4"', 'length' => '2"', 'thread' => '20 TPI', 'material' => 'Steel', 'finish' => 'Zinc Plated', 'grade' => 'Grade 5', 'price' => 0.65],
            ['size' => '1/4"', 'length' => '1"', 'thread' => '20 TPI', 'material' => 'Stainless Steel', 'finish' => 'Plain', 'grade' => 'Grade 8', 'price' => 1.25],
            ['size' => '3/8"', 'length' => '1.5"', 'thread' => '16 TPI', 'material' => 'Steel', 'finish' => 'Black Oxide', 'grade' => 'Grade 8', 'price' => 0.85],
            ['size' => '3/8"', 'length' => '2"', 'thread' => '16 TPI', 'material' => 'Stainless Steel', 'finish' => 'Plain', 'grade' => 'Grade 8', 'price' => 1.95],
            ['size' => '1/2"', 'length' => '2"', 'thread' => '13 TPI', 'material' => 'Steel', 'finish' => 'Zinc Plated', 'grade' => 'Grade 5', 'price' => 1.15],
            ['size' => '1/2"', 'length' => '3"', 'thread' => '13 TPI', 'material' => 'Steel', 'finish' => 'Black Oxide', 'grade' => 'Grade 8', 'price' => 1.45],
            ['size' => '5/8"', 'length' => '3"', 'thread' => '11 TPI', 'material' => 'Steel', 'finish' => 'Zinc Plated', 'grade' => 'Grade 5', 'price' => 1.85],
        ];

        foreach ($bolts as $index => $boltData) {
            $product = Product::create([
                'vendor_id' => $vendor->id,
                'category_id' => $category->id,
                'name' => "Hex Bolt {$boltData['size']}-{$boltData['thread']} x {$boltData['length']}",
                'slug' => "hex-bolt-{$boltData['size']}-{$boltData['length']}-".strtolower(str_replace(' ', '-', $boltData['material']))."-{$index}",
                'sku' => 'HB-'.str_pad($index + 1000, 6, '0', STR_PAD_LEFT),
                'mpn' => 'HB-'.strtoupper(substr(md5($boltData['size'].$boltData['length']), 0, 8)),
                'description' => "High-quality hex head bolt made from {$boltData['material']} with {$boltData['finish']} finish. {$boltData['grade']} specification.",
                'price' => $boltData['price'],
                'list_price' => $boltData['price'] * 1.3,
                'stock' => rand(50, 500),
                'lead_time_days' => rand(1, 3),
                'is_active' => true,
                'weight_g' => rand(5, 50),
                'country_of_origin' => 'US',
            ]);

            // Add specifications
            $this->addAttribute($product, 'Thread Size', 'string', $boltData['size']);
            $this->addAttribute($product, 'Length', 'string', $boltData['length']);
            $this->addAttribute($product, 'Thread Count', 'string', $boltData['thread']);
            $this->addAttribute($product, 'Material', 'string', $boltData['material']);
            $this->addAttribute($product, 'Finish', 'string', $boltData['finish']);
            $this->addAttribute($product, 'Grade', 'string', $boltData['grade']);
            $this->addAttribute($product, 'Head Type', 'string', 'Hex');
            $this->addAttribute($product, 'Drive Type', 'string', 'External Hex');
        }
    }

    protected function seedElectrical(Vendor $vendor, Category $category): void
    {
        $wires = [
            ['gauge' => '12 AWG', 'type' => 'THHN', 'insulation' => 'Nylon', 'voltage' => '600V', 'temp' => '90°C', 'color' => 'Black', 'price' => 1.25],
            ['gauge' => '12 AWG', 'type' => 'THHN', 'insulation' => 'Nylon', 'voltage' => '600V', 'temp' => '90°C', 'color' => 'Red', 'price' => 1.25],
            ['gauge' => '12 AWG', 'type' => 'THHN', 'insulation' => 'Nylon', 'voltage' => '600V', 'temp' => '90°C', 'color' => 'White', 'price' => 1.25],
            ['gauge' => '14 AWG', 'type' => 'THHN', 'insulation' => 'Nylon', 'voltage' => '600V', 'temp' => '90°C', 'color' => 'Black', 'price' => 0.85],
            ['gauge' => '14 AWG', 'type' => 'THHN', 'insulation' => 'Nylon', 'voltage' => '600V', 'temp' => '90°C', 'color' => 'Green', 'price' => 0.85],
            ['gauge' => '10 AWG', 'type' => 'THHN', 'insulation' => 'Nylon', 'voltage' => '600V', 'temp' => '90°C', 'color' => 'Black', 'price' => 1.85],
            ['gauge' => '10 AWG', 'type' => 'THWN', 'insulation' => 'PVC', 'voltage' => '600V', 'temp' => '75°C', 'color' => 'Red', 'price' => 1.75],
            ['gauge' => '8 AWG', 'type' => 'THHN', 'insulation' => 'Nylon', 'voltage' => '600V', 'temp' => '90°C', 'color' => 'Black', 'price' => 2.95],
        ];

        foreach ($wires as $index => $wireData) {
            $product = Product::create([
                'vendor_id' => $vendor->id,
                'category_id' => $category->id,
                'name' => "{$wireData['gauge']} {$wireData['type']} Copper Wire - {$wireData['color']}",
                'slug' => "wire-{$wireData['gauge']}-{$wireData['type']}-{$wireData['color']}-{$index}",
                'sku' => 'WR-'.str_pad($index + 2000, 6, '0', STR_PAD_LEFT),
                'mpn' => 'WR-'.strtoupper(substr(md5($wireData['gauge'].$wireData['color']), 0, 8)),
                'description' => "{$wireData['gauge']} {$wireData['type']} stranded copper wire with {$wireData['insulation']} insulation. Rated for {$wireData['voltage']}, {$wireData['temp']} max temperature.",
                'price' => $wireData['price'],
                'list_price' => $wireData['price'] * 1.25,
                'stock' => rand(100, 1000),
                'lead_time_days' => rand(1, 5),
                'is_active' => true,
                'weight_g' => rand(20, 150),
                'country_of_origin' => 'US',
                'meta' => json_encode(['unit' => 'FT']),
            ]);

            $this->addAttribute($product, 'Wire Gauge', 'string', $wireData['gauge']);
            $this->addAttribute($product, 'Wire Type', 'string', $wireData['type']);
            $this->addAttribute($product, 'Insulation Material', 'string', $wireData['insulation']);
            $this->addAttribute($product, 'Voltage Rating', 'string', $wireData['voltage']);
            $this->addAttribute($product, 'Temperature Rating', 'string', $wireData['temp']);
            $this->addAttribute($product, 'Color', 'string', $wireData['color']);
            $this->addAttribute($product, 'Conductor Material', 'string', 'Copper');
            $this->addAttribute($product, 'Stranded', 'boolean', 'true');
        }
    }

    protected function seedTools(Vendor $vendor, Category $category): void
    {
        $tools = [
            ['name' => 'Adjustable Wrench', 'size' => '8"', 'material' => 'Chrome Vanadium', 'finish' => 'Chrome Plated', 'capacity' => '1"', 'price' => 18.95],
            ['name' => 'Adjustable Wrench', 'size' => '10"', 'material' => 'Chrome Vanadium', 'finish' => 'Chrome Plated', 'capacity' => '1-1/4"', 'price' => 24.95],
            ['name' => 'Adjustable Wrench', 'size' => '12"', 'material' => 'Chrome Vanadium', 'finish' => 'Chrome Plated', 'capacity' => '1-1/2"', 'price' => 32.95],
            ['name' => 'Socket Set', 'size' => '1/4" Drive', 'material' => 'Chrome Vanadium', 'finish' => 'Chrome Plated', 'pieces' => '20', 'price' => 45.99],
            ['name' => 'Socket Set', 'size' => '3/8" Drive', 'material' => 'Chrome Vanadium', 'finish' => 'Chrome Plated', 'pieces' => '32', 'price' => 89.99],
            ['name' => 'Socket Set', 'size' => '1/2" Drive', 'material' => 'Chrome Vanadium', 'finish' => 'Chrome Plated', 'pieces' => '24', 'price' => 125.99],
            ['name' => 'Screwdriver Set', 'type' => 'Phillips & Flat', 'material' => 'Chrome Vanadium', 'handle' => 'Rubber Grip', 'pieces' => '6', 'price' => 22.95],
            ['name' => 'Hex Key Set', 'type' => 'SAE', 'material' => 'Chrome Vanadium', 'finish' => 'Black Oxide', 'pieces' => '13', 'price' => 15.95],
            ['name' => 'Hex Key Set', 'type' => 'Metric', 'material' => 'Chrome Vanadium', 'finish' => 'Black Oxide', 'pieces' => '15', 'price' => 17.95],
        ];

        foreach ($tools as $index => $toolData) {
            $product = Product::create([
                'vendor_id' => $vendor->id,
                'category_id' => $category->id,
                'name' => $toolData['name'].' - '.($toolData['size'] ?? $toolData['type'] ?? ''),
                'slug' => strtolower(str_replace(' ', '-', $toolData['name'].'-'.$index)),
                'sku' => 'TL-'.str_pad($index + 3000, 6, '0', STR_PAD_LEFT),
                'mpn' => 'TL-'.strtoupper(substr(md5($toolData['name'].$index), 0, 8)),
                'description' => "Professional grade {$toolData['name']} made from {$toolData['material']}. Durable construction for long-lasting performance.",
                'price' => $toolData['price'],
                'list_price' => $toolData['price'] * 1.4,
                'stock' => rand(10, 100),
                'lead_time_days' => rand(2, 7),
                'is_active' => true,
                'weight_g' => rand(100, 2000),
                'country_of_origin' => 'TW',
            ]);

            $this->addAttribute($product, 'Tool Type', 'string', $toolData['name']);
            $this->addAttribute($product, 'Material', 'string', $toolData['material']);

            if (isset($toolData['size'])) {
                $this->addAttribute($product, 'Size', 'string', $toolData['size']);
            }

            if (isset($toolData['type'])) {
                $this->addAttribute($product, 'Type', 'string', $toolData['type']);
            }

            if (isset($toolData['finish'])) {
                $this->addAttribute($product, 'Finish', 'string', $toolData['finish']);
            }

            if (isset($toolData['pieces'])) {
                $this->addAttribute($product, 'Piece Count', 'integer', $toolData['pieces']);
            }

            if (isset($toolData['capacity'])) {
                $this->addAttribute($product, 'Max Capacity', 'string', $toolData['capacity']);
            }

            $this->addAttribute($product, 'Warranty', 'string', 'Lifetime');
        }
    }

    protected function seedSafety(Vendor $vendor, Category $category): void
    {
        $safetyItems = [
            ['name' => 'Safety Glasses', 'lens' => 'Clear', 'coating' => 'Anti-Fog', 'rating' => 'ANSI Z87.1', 'frame' => 'Black', 'price' => 8.95],
            ['name' => 'Safety Glasses', 'lens' => 'Smoke', 'coating' => 'Anti-Scratch', 'rating' => 'ANSI Z87.1', 'frame' => 'Black', 'price' => 9.95],
            ['name' => 'Safety Glasses', 'lens' => 'Clear', 'coating' => 'Anti-Fog', 'rating' => 'ANSI Z87.1+', 'frame' => 'Gray', 'price' => 12.95],
            ['name' => 'Work Gloves', 'material' => 'Leather', 'palm' => 'Full Grain', 'size' => 'Large', 'cuff' => 'Safety Cuff', 'price' => 14.95],
            ['name' => 'Work Gloves', 'material' => 'Leather', 'palm' => 'Full Grain', 'size' => 'X-Large', 'cuff' => 'Safety Cuff', 'price' => 14.95],
            ['name' => 'Work Gloves', 'material' => 'Synthetic', 'palm' => 'Padded', 'size' => 'Medium', 'cuff' => 'Knit Wrist', 'price' => 9.95],
            ['name' => 'Hard Hat', 'type' => 'Type I', 'class' => 'Class E', 'suspension' => '4-Point', 'color' => 'Yellow', 'price' => 24.95],
            ['name' => 'Hard Hat', 'type' => 'Type I', 'class' => 'Class E', 'suspension' => '4-Point', 'color' => 'White', 'price' => 24.95],
            ['name' => 'Hard Hat', 'type' => 'Type II', 'class' => 'Class E', 'suspension' => '6-Point', 'color' => 'Orange', 'price' => 34.95],
            ['name' => 'Ear Plugs', 'type' => 'Foam', 'nrr' => '33 dB', 'reusable' => 'false', 'corded' => 'true', 'price' => 0.45],
            ['name' => 'Ear Muffs', 'type' => 'Over-Ear', 'nrr' => '30 dB', 'reusable' => 'true', 'adjustable' => 'true', 'price' => 19.95],
        ];

        foreach ($safetyItems as $index => $itemData) {
            $uniqueId = $itemData['name'].'-'.($itemData['lens'] ?? $itemData['size'] ?? $itemData['color'] ?? 'item').'-'.$index;

            $product = Product::create([
                'vendor_id' => $vendor->id,
                'category_id' => $category->id,
                'name' => $itemData['name'].' - '.($itemData['lens'] ?? $itemData['size'] ?? $itemData['color'] ?? ''),
                'slug' => strtolower(str_replace([' ', '/'], ['-', '-'], $uniqueId)),
                'sku' => 'SF-'.str_pad($index + 4000, 6, '0', STR_PAD_LEFT),
                'mpn' => 'SF-'.strtoupper(substr(md5($uniqueId), 0, 8)),
                'description' => "OSHA compliant {$itemData['name']} designed for maximum protection and comfort in industrial environments.",
                'price' => $itemData['price'],
                'list_price' => $itemData['price'] * 1.35,
                'stock' => rand(50, 500),
                'lead_time_days' => rand(1, 5),
                'is_active' => true,
                'weight_g' => rand(10, 500),
                'country_of_origin' => 'US',
            ]);

            $this->addAttribute($product, 'Product Type', 'string', $itemData['name']);

            foreach ($itemData as $key => $value) {
                if ($key === 'name' || $key === 'price') {
                    continue;
                }

                $attributeName = ucwords(str_replace('_', ' ', $key));
                $type = in_array($key, ['reusable', 'adjustable', 'corded']) ? 'boolean' : 'string';

                $this->addAttribute($product, $attributeName, $type, $value);
            }

            $this->addAttribute($product, 'OSHA Compliant', 'boolean', 'true');
        }
    }

    protected function addAttribute(Product $product, string $name, string $type, string $value): void
    {
        ProductAttribute::create([
            'product_id' => $product->id,
            'name' => $name,
            'type' => $type,
            'value' => $value,
        ]);
    }
}
