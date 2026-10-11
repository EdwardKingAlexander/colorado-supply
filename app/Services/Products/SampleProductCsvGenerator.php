<?php

namespace App\Services\Products;

use App\Models\Category;
use App\Models\Vendor;
use Illuminate\Support\Str;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuntimeException;

/**
 * Builds a product CSV in the format ProductResource's importer reads, using
 * the vendors and leaf categories already in the database.
 *
 * Output is deterministic for a given database (fixed seed, SKUs SAMPLE-0001..),
 * so re-importing the same file updates the sample products instead of
 * duplicating them.
 */
class SampleProductCsvGenerator
{
    private const SEED = 20261010;

    private const HEADER = [
        'vendor_id', 'vendor', 'sku', 'slug', 'name', 'category_id', 'category', 'description',
        'cost', 'price', 'list_price', 'stock', 'reorder_point', 'lead_time_days', 'is_active',
        'unit_of_measure', 'minimum_order_quantity', 'standard_type', 'country_of_origin',
        'made_in_usa', 'berry_compliant', 'taa_compliant', 'material', 'finish',
        'attribute:Material', 'attribute:Finish', 'attribute:Size',
    ];

    private const GRADES = ['Heavy-Duty', 'Standard', 'Industrial', 'Premium', 'Economy', 'Commercial-Grade', 'Precision', 'High-Temp', 'Corrosion-Resistant', 'Low-Profile'];

    private const MATERIALS = ['Steel', 'Stainless Steel', 'Carbon Steel', 'Brass', 'Bronze', 'Aluminum', 'Nylon', 'Polypropylene'];

    private const FINISHES = ['Plain', 'Zinc Plated', 'Black Oxide', 'Galvanized', 'Powder Coated', 'Anodized'];

    private const SIZES = ['1/4 in', '3/8 in', '1/2 in', '3/4 in', '1 in', '2 in', '#6', '#8', 'M6', 'M8', 'M10', '12 in', '24 in'];

    /** Unit of measure => sensible minimum order quantities. */
    private const UNITS = ['EA' => [1, 1, 5], 'BX' => [1, 10], 'PK' => [1, 25, 100], 'FT' => [10, 50]];

    /** Weighted toward US-made. */
    private const COUNTRIES = ['US', 'US', 'US', 'CA', 'MX', 'DE', 'JP', 'TW', 'CN'];

    /** Trade Agreements Act designated countries among the above. */
    private const TAA_COUNTRIES = ['US', 'CA', 'MX', 'DE', 'JP', 'TW'];

    private const GOVERNMENT_STANDARDS = ['MS', 'NAS', 'AN'];

    public function generate(int $count = 1000): string
    {
        $vendors = Vendor::query()->orderBy('id')->get(['id', 'name']);
        $categories = Category::query()->whereDoesntHave('children')->orderBy('id')->get(['id', 'name']);

        if ($vendors->isEmpty()) {
            throw new RuntimeException('Add at least one vendor before generating sample products.');
        }

        if ($categories->isEmpty()) {
            throw new RuntimeException('Add at least one category before generating sample products.');
        }

        $random = new Randomizer(new Mt19937(self::SEED));
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, self::HEADER, escape: '');

        for ($i = 0; $i < $count; $i++) {
            // Cycle categories so every leaf category gets products.
            $category = $categories[$i % $categories->count()];
            $vendor = $vendors[$random->getInt(0, $vendors->count() - 1)];
            fputcsv($handle, $this->row($i + 1, $vendor, $category, $random), escape: '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    private function row(int $number, Vendor $vendor, Category $category, Randomizer $random): array
    {
        $sku = sprintf('SAMPLE-%04d', $number);
        $grade = $this->pick(self::GRADES, $random);
        $material = $this->pick(self::MATERIALS, $random);
        $finish = $this->pick(self::FINISHES, $random);
        $size = $this->pick(self::SIZES, $random);
        $noun = $this->noun($category->name);
        $name = "{$grade} {$noun}, {$material}, {$size}";

        $cost = round(exp($random->getFloat(log(0.5), log(500))), 2);
        $price = round($cost * $random->getFloat(1.25, 1.6), 2);
        $unit = $this->pick(array_keys(self::UNITS), $random);
        $country = $this->pick(self::COUNTRIES, $random);
        $stock = $random->getInt(0, 2000);

        return [
            $vendor->id,
            $vendor->name,
            $sku,
            Str::slug("{$name}-{$sku}"),
            $name,
            $category->id,
            $category->name,
            "{$grade} {$material} {$noun} ({$size}), {$finish} finish. Sample catalog item.",
            number_format($cost, 2, '.', ''),
            number_format($price, 2, '.', ''),
            number_format(round($price * 1.2, 2), 2, '.', ''),
            $stock,
            $random->getInt(0, 100),
            $random->getInt(1, 30),
            'yes',
            $unit,
            $this->pick(self::UNITS[$unit], $random),
            $random->getInt(1, 10) === 1 ? $this->pick(self::GOVERNMENT_STANDARDS, $random) : 'Commercial',
            $country,
            $country === 'US' ? 'yes' : 'no',
            $country === 'US' ? 'yes' : 'no',
            in_array($country, self::TAA_COUNTRIES, true) ? 'yes' : 'no',
            $material,
            $finish,
            $material,
            $finish,
            $size,
        ];
    }

    /**
     * "Switches & Receptacles" -> "Switch", "Hydraulic Valves" -> "Hydraulic Valve".
     */
    private function noun(string $categoryName): string
    {
        return Str::singular(trim(Str::before($categoryName, '&')));
    }

    private function pick(array $options, Randomizer $random): mixed
    {
        return $options[$random->getInt(0, count($options) - 1)];
    }
}
