<?php

namespace Database\Seeders;

use App\Models\GsaFilter;
use Illuminate\Database\Seeder;

class GsaFilterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $records = [
            ['type' => 'naics', 'code' => '423840', 'description' => 'Industrial Supplies Merchant Wholesalers'],
            ['type' => 'naics', 'code' => '423830', 'description' => 'Industrial Machinery & Equipment Wholesalers'],
            ['type' => 'naics', 'code' => '423710', 'description' => 'Hardware Merchant Wholesalers'],
            ['type' => 'naics', 'code' => '423730', 'description' => 'Warm Air Heating & Air-Conditioning Equipment'],
            ['type' => 'naics', 'code' => '423610', 'description' => 'Electrical Apparatus & Equipment Wholesalers'],
            ['type' => 'naics', 'code' => '423990', 'description' => 'Other Misc Durable Goods Merchant Wholesalers'],
            ['type' => 'psc', 'code' => 'J045', 'description' => 'Maintenance/Repair of Plumbing/Heating Equipment'],
            ['type' => 'psc', 'code' => 'J048', 'description' => 'Maintenance/Repair of Equipment—Valves/Piping'],
            ['type' => 'psc', 'code' => 'J059', 'description' => 'Maintenance/Repair of Electrical/Electronic Equipment'],
            ['type' => 'psc', 'code' => '5340', 'description' => 'Hardware, Commercial'],
            ['type' => 'psc', 'code' => '5935', 'description' => 'Connectors, Electrical'],
            ['type' => 'psc', 'code' => '6145', 'description' => 'Wire & Cable, Electrical'],
            ['type' => 'psc', 'code' => '4510', 'description' => 'Plumbing Fixtures & Accessories'],
            ['type' => 'psc', 'code' => '5120', 'description' => 'Hand Tools (Non-Power)'],
            ['type' => 'psc', 'code' => '5180', 'description' => 'Sets, Kits, Outfits of Hand Tools'],
            ['type' => 'psc', 'code' => '6135', 'description' => 'Batteries, Non-rechargeable'],
        ];

        foreach ($records as $record) {
            GsaFilter::where('code', $record['code'])->delete();

            GsaFilter::create([
                'type' => $record['type'],
                'code' => $record['code'],
                'description' => $record['description'],
                'enabled' => true,
            ]);
        }

        $this->command?->info('GSA filters seeded successfully.');
    }
}
