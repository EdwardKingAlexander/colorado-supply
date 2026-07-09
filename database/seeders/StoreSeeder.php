<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyProduct;
use App\Models\Location;
use App\Models\LocationProduct;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StoreSeeder extends Seeder
{
    private const VENDOR_EMAIL = 'store-seed@coloradosupply.com';

    private const VENDOR_SLUG = 'colorado-supply-industrial-catalog';

    private const COMPANY_SLUG = 'colorado-supply-procurement-llc';

    public function run(): void
    {
        $company = $this->company();
        $locations = $this->locations($company);
        $vendor = $this->vendor($company);
        $categories = $this->categories();

        $created = 0;

        foreach ($this->catalog() as $definition) {
            $category = $categories[$definition['category']];

            foreach ($definition['variants'] as $variant) {
                $product = $this->product($vendor, $category, $definition, $variant);
                $this->attributes($product, array_merge($definition['attributes'], $variant['attributes']));
                $this->inventory($company, $locations, $product, $variant);

                $created++;
            }
        }

        $this->command?->info("Seeded {$created} industrial store products with categories, attributes, and location inventory.");
    }

    private function company(): Company
    {
        return Company::query()->firstOrCreate(
            ['slug' => self::COMPANY_SLUG],
            ['name' => 'Colorado Supply & Procurement LLC']
        );
    }

    /**
     * @return array<string, Location>
     */
    private function locations(Company $company): array
    {
        $locations = [
            'denver' => ['name' => 'Denver Distribution Center', 'slug' => 'denver-distribution-center'],
            'front-range' => ['name' => 'Front Range Will Call', 'slug' => 'front-range-will-call'],
        ];

        return collect($locations)
            ->map(fn (array $location) => Location::query()->firstOrCreate(
                ['company_id' => $company->id, 'slug' => $location['slug']],
                ['name' => $location['name']]
            ))
            ->all();
    }

    private function vendor(Company $company): Vendor
    {
        $user = User::query()->firstOrCreate(
            ['email' => self::VENDOR_EMAIL],
            [
                'name' => 'Colorado Supply Store Seeder',
                'password' => Hash::make('password'),
                'role' => 'vendor',
                'company_id' => $company->id,
            ]
        );

        $user->forceFill([
            'role' => 'vendor',
            'company_id' => $company->id,
        ])->save();

        return Vendor::query()->updateOrCreate(
            ['email' => self::VENDOR_EMAIL],
            [
                'user_id' => $user->id,
                'name' => 'Colorado Supply Industrial Catalog',
                'phone' => '303-555-0198',
                'slug' => self::VENDOR_SLUG,
                'description' => 'Seeded industrial, MRO, safety, electrical, and federal supply catalog for frontend testing.',
                'cage_code' => '7CS01',
                'duns_number' => '081234567',
                'naics_code' => '423840',
            ]
        );
    }

    /**
     * @return array<string, Category>
     */
    private function categories(): array
    {
        $tree = [
            'fasteners' => [
                'name' => 'Fasteners & Hardware',
                'children' => [
                    'hex-bolts' => 'Hex Bolts',
                    'socket-cap-screws' => 'Socket Cap Screws',
                    'washers-spacers' => 'Washers & Spacers',
                    'anchors' => 'Anchors',
                ],
            ],
            'abrasives' => [
                'name' => 'Abrasives & Finishing',
                'children' => [
                    'cutoff-wheels' => 'Cutoff Wheels',
                    'flap-discs' => 'Flap Discs',
                    'sanding-belts' => 'Sanding Belts',
                ],
            ],
            'electrical' => [
                'name' => 'Electrical & Lighting',
                'children' => [
                    'wire-cable' => 'Wire & Cable',
                    'cord-grips' => 'Cord Grips',
                    'led-work-lights' => 'LED Work Lights',
                ],
            ],
            'fluid-power' => [
                'name' => 'Fluid Power & Plumbing',
                'children' => [
                    'hydraulic-hose' => 'Hydraulic Hose',
                    'pipe-fittings' => 'Pipe Fittings',
                    'valves' => 'Valves',
                ],
            ],
            'material-handling' => [
                'name' => 'Material Handling',
                'children' => [
                    'casters' => 'Casters',
                    'lifting-slings' => 'Lifting Slings',
                    'storage-bins' => 'Storage Bins',
                ],
            ],
            'safety' => [
                'name' => 'Safety & PPE',
                'children' => [
                    'eye-protection' => 'Eye Protection',
                    'hand-protection' => 'Hand Protection',
                    'respiratory-protection' => 'Respiratory Protection',
                    'hearing-protection' => 'Hearing Protection',
                ],
            ],
            'tools' => [
                'name' => 'Tools & Shop Equipment',
                'children' => [
                    'hand-tools' => 'Hand Tools',
                    'drill-bits' => 'Drill Bits',
                    'measuring-tools' => 'Measuring Tools',
                ],
            ],
            'janitorial' => [
                'name' => 'Janitorial & Facility',
                'children' => [
                    'spill-control' => 'Spill Control',
                    'wipers' => 'Wipers',
                    'floor-care' => 'Floor Care',
                ],
            ],
        ];

        $categories = [];

        foreach ($tree as $parentSlug => $parent) {
            $parentCategory = Category::query()->updateOrCreate(
                ['slug' => $parentSlug],
                [
                    'parent_id' => null,
                    'name' => $parent['name'],
                    'description' => "{$parent['name']} for industrial supply and MRO procurement.",
                ]
            );

            $categories[$parentSlug] = $parentCategory;

            foreach ($parent['children'] as $childSlug => $childName) {
                $categories[$childSlug] = Category::query()->updateOrCreate(
                    ['slug' => $childSlug],
                    [
                        'parent_id' => $parentCategory->id,
                        'name' => $childName,
                        'description' => "{$childName} in multiple sizes, materials, and compliance options.",
                    ]
                );
            }
        }

        return $categories;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalog(): array
    {
        return [
            $this->family('hex-bolts', 'Hex Head Bolt', 'HB', 'EA', 31161500, 5306, [
                'Product Type' => 'Hex Bolt',
                'Drive Type' => 'External Hex',
                'Thread Standard' => 'UNC',
            ], [
                ['1/4-20 x 1 in', 0.42, ['Diameter' => '1/4 in', 'Length' => '1 in', 'Material' => 'Alloy Steel', 'Finish' => 'Zinc Plated', 'Grade' => 'Grade 5']],
                ['1/4-20 x 2 in', 0.58, ['Diameter' => '1/4 in', 'Length' => '2 in', 'Material' => 'Alloy Steel', 'Finish' => 'Zinc Plated', 'Grade' => 'Grade 5']],
                ['3/8-16 x 1-1/2 in', 0.86, ['Diameter' => '3/8 in', 'Length' => '1-1/2 in', 'Material' => 'Alloy Steel', 'Finish' => 'Black Oxide', 'Grade' => 'Grade 8']],
                ['1/2-13 x 3 in', 1.78, ['Diameter' => '1/2 in', 'Length' => '3 in', 'Material' => '316 Stainless Steel', 'Finish' => 'Plain', 'Grade' => '18-8']],
                ['5/8-11 x 4 in', 3.95, ['Diameter' => '5/8 in', 'Length' => '4 in', 'Material' => 'Alloy Steel', 'Finish' => 'Hot-Dip Galvanized', 'Grade' => 'Grade 8']],
            ]),
            $this->family('socket-cap-screws', 'Socket Head Cap Screw', 'SHCS', 'EA', 31161500, 5305, [
                'Product Type' => 'Socket Cap Screw',
                'Drive Type' => 'Hex Socket',
                'Head Style' => 'Cylindrical',
            ], [
                ['10-32 x 1/2 in', 0.31, ['Diameter' => '#10', 'Length' => '1/2 in', 'Material' => 'Alloy Steel', 'Finish' => 'Black Oxide', 'Strength' => '170 ksi']],
                ['1/4-20 x 3/4 in', 0.44, ['Diameter' => '1/4 in', 'Length' => '3/4 in', 'Material' => 'Alloy Steel', 'Finish' => 'Black Oxide', 'Strength' => '170 ksi']],
                ['5/16-18 x 1 in', 0.69, ['Diameter' => '5/16 in', 'Length' => '1 in', 'Material' => '18-8 Stainless Steel', 'Finish' => 'Plain', 'Strength' => '70 ksi']],
                ['3/8-16 x 1-1/4 in', 0.94, ['Diameter' => '3/8 in', 'Length' => '1-1/4 in', 'Material' => 'Alloy Steel', 'Finish' => 'Zinc Yellow', 'Strength' => '170 ksi']],
            ]),
            $this->family('washers-spacers', 'Flat Washer', 'WSH', 'EA', 31161800, 5310, [
                'Product Type' => 'Washer',
                'Washer Type' => 'Flat',
            ], [
                ['1/4 in SAE', 0.08, ['Inside Diameter' => '1/4 in', 'Material' => 'Steel', 'Finish' => 'Zinc Plated', 'Standard' => 'SAE']],
                ['3/8 in SAE', 0.12, ['Inside Diameter' => '3/8 in', 'Material' => 'Steel', 'Finish' => 'Zinc Plated', 'Standard' => 'SAE']],
                ['1/2 in USS', 0.19, ['Inside Diameter' => '1/2 in', 'Material' => '18-8 Stainless Steel', 'Finish' => 'Plain', 'Standard' => 'USS']],
                ['5/8 in Fender', 0.36, ['Inside Diameter' => '5/8 in', 'Material' => 'Steel', 'Finish' => 'Hot-Dip Galvanized', 'Standard' => 'Fender']],
            ]),
            $this->family('anchors', 'Wedge Anchor', 'ANC', 'EA', 31162100, 5340, [
                'Product Type' => 'Concrete Anchor',
                'Anchor Type' => 'Wedge',
            ], [
                ['1/4 x 2-1/4 in', 1.18, ['Diameter' => '1/4 in', 'Length' => '2-1/4 in', 'Material' => 'Carbon Steel', 'Finish' => 'Zinc Plated', 'Embedment' => '1-1/8 in']],
                ['3/8 x 3 in', 1.92, ['Diameter' => '3/8 in', 'Length' => '3 in', 'Material' => 'Carbon Steel', 'Finish' => 'Zinc Plated', 'Embedment' => '1-1/2 in']],
                ['1/2 x 4-1/4 in', 3.85, ['Diameter' => '1/2 in', 'Length' => '4-1/4 in', 'Material' => '304 Stainless Steel', 'Finish' => 'Plain', 'Embedment' => '2-1/4 in']],
            ]),
            $this->family('cutoff-wheels', 'Cutoff Wheel', 'COW', 'EA', 31191500, 5345, [
                'Product Type' => 'Cutoff Wheel',
                'Abrasive' => 'Aluminum Oxide',
            ], [
                ['4-1/2 x .045 x 7/8 in Type 1', 2.45, ['Diameter' => '4-1/2 in', 'Thickness' => '.045 in', 'Arbor' => '7/8 in', 'Max RPM' => '13300', 'Material Compatibility' => 'Steel']],
                ['6 x .045 x 7/8 in Type 1', 3.65, ['Diameter' => '6 in', 'Thickness' => '.045 in', 'Arbor' => '7/8 in', 'Max RPM' => '10200', 'Material Compatibility' => 'Stainless Steel']],
                ['14 x 1/8 x 1 in Type 1', 9.85, ['Diameter' => '14 in', 'Thickness' => '1/8 in', 'Arbor' => '1 in', 'Max RPM' => '4400', 'Material Compatibility' => 'Ferrous Metal']],
            ]),
            $this->family('flap-discs', 'Flap Disc', 'FLP', 'EA', 31191500, 5345, [
                'Product Type' => 'Flap Disc',
                'Backing' => 'Fiberglass',
            ], [
                ['4-1/2 in 40 Grit Zirconia', 4.35, ['Diameter' => '4-1/2 in', 'Grit' => '40', 'Abrasive' => 'Zirconia Alumina', 'Shape' => 'Type 29']],
                ['4-1/2 in 60 Grit Zirconia', 4.18, ['Diameter' => '4-1/2 in', 'Grit' => '60', 'Abrasive' => 'Zirconia Alumina', 'Shape' => 'Type 29']],
                ['7 in 80 Grit Ceramic', 8.75, ['Diameter' => '7 in', 'Grit' => '80', 'Abrasive' => 'Ceramic Alumina', 'Shape' => 'Type 27']],
            ]),
            $this->family('sanding-belts', 'Sanding Belt', 'SBLT', 'EA', 31191500, 5345, [
                'Product Type' => 'Sanding Belt',
                'Joint Type' => 'Bi-Directional Tape',
            ], [
                ['2 x 48 in 80 Grit', 5.95, ['Width' => '2 in', 'Length' => '48 in', 'Grit' => '80', 'Abrasive' => 'Aluminum Oxide']],
                ['4 x 36 in 120 Grit', 6.25, ['Width' => '4 in', 'Length' => '36 in', 'Grit' => '120', 'Abrasive' => 'Silicon Carbide']],
                ['6 x 48 in 60 Grit', 12.85, ['Width' => '6 in', 'Length' => '48 in', 'Grit' => '60', 'Abrasive' => 'Zirconia Alumina']],
            ]),
            $this->family('wire-cable', 'THHN Copper Wire', 'WIRE', 'FT', 26121600, 6145, [
                'Product Type' => 'Building Wire',
                'Conductor Material' => 'Copper',
                'Voltage Rating' => '600 V',
            ], [
                ['14 AWG Black', 0.42, ['Gauge' => '14 AWG', 'Color' => 'Black', 'Insulation' => 'PVC/Nylon', 'Temperature Rating' => '90 C']],
                ['12 AWG White', 0.68, ['Gauge' => '12 AWG', 'Color' => 'White', 'Insulation' => 'PVC/Nylon', 'Temperature Rating' => '90 C']],
                ['10 AWG Green', 1.14, ['Gauge' => '10 AWG', 'Color' => 'Green', 'Insulation' => 'PVC/Nylon', 'Temperature Rating' => '90 C']],
                ['8 AWG Red', 2.08, ['Gauge' => '8 AWG', 'Color' => 'Red', 'Insulation' => 'PVC/Nylon', 'Temperature Rating' => '90 C']],
            ]),
            $this->family('cord-grips', 'Nylon Cord Grip', 'CGRIP', 'EA', 39121400, 5975, [
                'Product Type' => 'Cord Grip',
                'Ingress Protection' => 'IP68',
            ], [
                ['1/2 NPT .20-.35 in Cable', 7.45, ['Thread Size' => '1/2 NPT', 'Cable Diameter' => '.20-.35 in', 'Material' => 'Nylon', 'Color' => 'Black']],
                ['3/4 NPT .39-.55 in Cable', 9.95, ['Thread Size' => '3/4 NPT', 'Cable Diameter' => '.39-.55 in', 'Material' => 'Nylon', 'Color' => 'Black']],
                ['1 NPT .55-.70 in Cable', 14.85, ['Thread Size' => '1 NPT', 'Cable Diameter' => '.55-.70 in', 'Material' => 'Nickel-Plated Brass', 'Color' => 'Metallic']],
            ]),
            $this->family('led-work-lights', 'LED Work Light', 'LEDWL', 'EA', 39111600, 6210, [
                'Product Type' => 'Work Light',
                'Light Source' => 'LED',
            ], [
                ['2500 Lumen Magnetic Base', 42.95, ['Lumens' => '2500', 'Mount' => 'Magnetic Base', 'Voltage' => '120 VAC', 'Cord Length' => '6 ft']],
                ['5000 Lumen Tripod', 119.95, ['Lumens' => '5000', 'Mount' => 'Tripod', 'Voltage' => '120 VAC', 'Cord Length' => '10 ft']],
                ['10000 Lumen String Light', 229.00, ['Lumens' => '10000', 'Mount' => 'Temporary String', 'Voltage' => '120 VAC', 'Cord Length' => '50 ft']],
            ]),
            $this->family('hydraulic-hose', 'Hydraulic Hose Assembly', 'HYD', 'EA', 40142000, 4720, [
                'Product Type' => 'Hydraulic Hose',
                'Reinforcement' => 'Two-Wire Braid',
            ], [
                ['1/4 in x 24 in JIC 6F', 24.95, ['Inside Diameter' => '1/4 in', 'Length' => '24 in', 'Working Pressure' => '5000 psi', 'End A' => 'JIC 6F', 'End B' => 'JIC 6F']],
                ['3/8 in x 36 in JIC 8F', 36.50, ['Inside Diameter' => '3/8 in', 'Length' => '36 in', 'Working Pressure' => '4000 psi', 'End A' => 'JIC 8F', 'End B' => 'JIC 8F']],
                ['1/2 in x 48 in NPT M', 54.75, ['Inside Diameter' => '1/2 in', 'Length' => '48 in', 'Working Pressure' => '3500 psi', 'End A' => 'NPT Male', 'End B' => 'NPT Male']],
            ]),
            $this->family('pipe-fittings', 'Pipe Fitting', 'PIPE', 'EA', 40183100, 4730, [
                'Product Type' => 'Pipe Fitting',
                'Thread Standard' => 'NPT',
            ], [
                ['1/4 in 90 Degree Elbow', 3.15, ['Size' => '1/4 in', 'Shape' => '90 Degree Elbow', 'Material' => 'Brass', 'Pressure Rating' => '1200 psi']],
                ['1/2 in Tee', 5.85, ['Size' => '1/2 in', 'Shape' => 'Tee', 'Material' => '316 Stainless Steel', 'Pressure Rating' => '1500 psi']],
                ['3/4 in Coupling', 4.95, ['Size' => '3/4 in', 'Shape' => 'Coupling', 'Material' => 'Malleable Iron', 'Pressure Rating' => '300 psi']],
                ['1 in Union', 18.75, ['Size' => '1 in', 'Shape' => 'Union', 'Material' => 'Brass', 'Pressure Rating' => '600 psi']],
            ]),
            $this->family('valves', 'Ball Valve', 'VALVE', 'EA', 40141600, 4820, [
                'Product Type' => 'Ball Valve',
                'Operation' => 'Lever Handle',
            ], [
                ['1/4 in Brass Full Port', 12.95, ['Pipe Size' => '1/4 in', 'Body Material' => 'Brass', 'Port Type' => 'Full Port', 'Pressure Rating' => '600 psi']],
                ['1/2 in Stainless Full Port', 28.95, ['Pipe Size' => '1/2 in', 'Body Material' => '316 Stainless Steel', 'Port Type' => 'Full Port', 'Pressure Rating' => '1000 psi']],
                ['1 in PVC Compact', 16.50, ['Pipe Size' => '1 in', 'Body Material' => 'PVC', 'Port Type' => 'Standard Port', 'Pressure Rating' => '150 psi']],
            ]),
            $this->family('casters', 'Swivel Caster', 'CSTR', 'EA', 31162700, 5340, [
                'Product Type' => 'Caster',
                'Caster Type' => 'Swivel',
            ], [
                ['3 in Polyurethane Wheel', 14.95, ['Wheel Diameter' => '3 in', 'Wheel Material' => 'Polyurethane', 'Load Capacity' => '300 lb', 'Brake' => 'No']],
                ['4 in Rubber Wheel with Brake', 22.95, ['Wheel Diameter' => '4 in', 'Wheel Material' => 'Rubber', 'Load Capacity' => '350 lb', 'Brake' => 'Yes']],
                ['6 in Phenolic Wheel', 49.95, ['Wheel Diameter' => '6 in', 'Wheel Material' => 'Phenolic', 'Load Capacity' => '1200 lb', 'Brake' => 'No']],
            ]),
            $this->family('lifting-slings', 'Web Lifting Sling', 'SLING', 'EA', 24101600, 3940, [
                'Product Type' => 'Lifting Sling',
                'Sling Type' => 'Eye and Eye',
                'Material' => 'Polyester',
            ], [
                ['1 in x 4 ft', 16.95, ['Width' => '1 in', 'Length' => '4 ft', 'Vertical Capacity' => '1600 lb', 'Color' => 'Purple']],
                ['2 in x 6 ft', 29.95, ['Width' => '2 in', 'Length' => '6 ft', 'Vertical Capacity' => '3200 lb', 'Color' => 'Green']],
                ['3 in x 10 ft', 62.95, ['Width' => '3 in', 'Length' => '10 ft', 'Vertical Capacity' => '4800 lb', 'Color' => 'Yellow']],
            ]),
            $this->family('storage-bins', 'Stackable Storage Bin', 'BIN', 'EA', 24112000, 7240, [
                'Product Type' => 'Storage Bin',
                'Nestable' => 'Yes',
            ], [
                ['5 x 4 x 3 in Blue', 2.95, ['Length' => '5 in', 'Width' => '4 in', 'Height' => '3 in', 'Color' => 'Blue', 'Material' => 'Polypropylene']],
                ['11 x 6 x 5 in Yellow', 6.75, ['Length' => '11 in', 'Width' => '6 in', 'Height' => '5 in', 'Color' => 'Yellow', 'Material' => 'Polypropylene']],
                ['18 x 11 x 10 in Gray', 18.50, ['Length' => '18 in', 'Width' => '11 in', 'Height' => '10 in', 'Color' => 'Gray', 'Material' => 'HDPE']],
            ]),
            $this->family('eye-protection', 'Safety Glasses', 'GLASS', 'EA', 46181800, 4240, [
                'Product Type' => 'Safety Glasses',
                'Standard' => 'ANSI Z87.1+',
            ], [
                ['Clear Anti-Fog Lens', 7.95, ['Lens Color' => 'Clear', 'Coating' => 'Anti-Fog', 'Frame Color' => 'Black', 'UV Protection' => '99.9%']],
                ['Smoke Anti-Scratch Lens', 8.95, ['Lens Color' => 'Smoke', 'Coating' => 'Anti-Scratch', 'Frame Color' => 'Gray', 'UV Protection' => '99.9%']],
                ['Indoor/Outdoor Mirror Lens', 11.50, ['Lens Color' => 'Mirror', 'Coating' => 'Anti-Fog', 'Frame Color' => 'Blue', 'UV Protection' => '99.9%']],
            ]),
            $this->family('hand-protection', 'Work Gloves', 'GLOVE', 'PR', 46181500, 8415, [
                'Product Type' => 'Work Gloves',
            ], [
                ['Nitrile Coated Medium', 4.95, ['Size' => 'Medium', 'Coating' => 'Nitrile', 'Cut Rating' => 'A1', 'Cuff Style' => 'Knit Wrist']],
                ['Nitrile Coated Large', 4.95, ['Size' => 'Large', 'Coating' => 'Nitrile', 'Cut Rating' => 'A1', 'Cuff Style' => 'Knit Wrist']],
                ['Leather Driver Large', 12.95, ['Size' => 'Large', 'Material' => 'Cowhide Leather', 'Cut Rating' => 'A1', 'Cuff Style' => 'Slip-On']],
                ['Cut Resistant A4 X-Large', 16.95, ['Size' => 'X-Large', 'Coating' => 'Polyurethane', 'Cut Rating' => 'A4', 'Cuff Style' => 'Knit Wrist']],
            ]),
            $this->family('respiratory-protection', 'Respirator', 'RESP', 'EA', 46182000, 4240, [
                'Product Type' => 'Respirator',
                'NIOSH Approved' => 'Yes',
            ], [
                ['N95 Disposable Box of 20', 21.95, ['Protection Level' => 'N95', 'Style' => 'Disposable', 'Valve' => 'No', 'Package Quantity' => '20']],
                ['P100 Half Mask Medium', 38.95, ['Protection Level' => 'P100', 'Style' => 'Half Mask', 'Size' => 'Medium', 'Package Quantity' => '1']],
                ['Organic Vapor Cartridge Pair', 18.95, ['Protection Level' => 'Organic Vapor', 'Style' => 'Cartridge', 'Compatibility' => 'Bayonet', 'Package Quantity' => '2']],
            ]),
            $this->family('hearing-protection', 'Hearing Protection', 'HEAR', 'EA', 46181900, 4240, [
                'Product Type' => 'Hearing Protection',
            ], [
                ['Foam Earplugs Corded Box of 100', 24.95, ['NRR' => '32 dB', 'Style' => 'Foam Earplug', 'Corded' => 'Yes', 'Package Quantity' => '100']],
                ['Reusable Earplugs Case Pack of 50', 42.95, ['NRR' => '27 dB', 'Style' => 'Reusable Earplug', 'Corded' => 'No', 'Package Quantity' => '50']],
                ['Over-Ear Earmuffs', 19.95, ['NRR' => '30 dB', 'Style' => 'Earmuff', 'Foldable' => 'Yes', 'Package Quantity' => '1']],
            ]),
            $this->family('hand-tools', 'Combination Wrench', 'WRENCH', 'EA', 27111700, 5120, [
                'Product Type' => 'Combination Wrench',
                'Finish' => 'Chrome',
            ], [
                ['SAE 7/16 in', 6.95, ['Size' => '7/16 in', 'Measurement System' => 'SAE', 'Material' => 'Chrome Vanadium', 'Length' => '6 in']],
                ['SAE 1/2 in', 7.95, ['Size' => '1/2 in', 'Measurement System' => 'SAE', 'Material' => 'Chrome Vanadium', 'Length' => '7 in']],
                ['Metric 10 mm', 6.50, ['Size' => '10 mm', 'Measurement System' => 'Metric', 'Material' => 'Chrome Vanadium', 'Length' => '6 in']],
                ['Metric 13 mm', 7.50, ['Size' => '13 mm', 'Measurement System' => 'Metric', 'Material' => 'Chrome Vanadium', 'Length' => '7 in']],
            ]),
            $this->family('drill-bits', 'Jobber Drill Bit', 'DRILL', 'EA', 27112800, 5133, [
                'Product Type' => 'Drill Bit',
                'Shank Type' => 'Round',
            ], [
                ['1/8 in Cobalt', 2.75, ['Diameter' => '1/8 in', 'Material' => 'Cobalt Steel', 'Finish' => 'Gold Oxide', 'Point Angle' => '135 deg']],
                ['1/4 in Cobalt', 5.95, ['Diameter' => '1/4 in', 'Material' => 'Cobalt Steel', 'Finish' => 'Gold Oxide', 'Point Angle' => '135 deg']],
                ['3/8 in Black Oxide', 7.25, ['Diameter' => '3/8 in', 'Material' => 'High Speed Steel', 'Finish' => 'Black Oxide', 'Point Angle' => '118 deg']],
                ['1/2 in Silver & Deming', 14.95, ['Diameter' => '1/2 in', 'Material' => 'High Speed Steel', 'Finish' => 'Black Oxide', 'Point Angle' => '118 deg']],
            ]),
            $this->family('measuring-tools', 'Measuring Tool', 'MEAS', 'EA', 41111600, 5210, [
                'Product Type' => 'Measuring Tool',
            ], [
                ['Digital Caliper 6 in', 39.95, ['Tool Type' => 'Digital Caliper', 'Range' => '0-6 in', 'Resolution' => '0.0005 in', 'Battery Included' => 'Yes']],
                ['Tape Measure 25 ft', 12.95, ['Tool Type' => 'Tape Measure', 'Range' => '25 ft', 'Blade Width' => '1 in', 'Magnetic Tip' => 'Yes']],
                ['Torpedo Level 9 in', 9.95, ['Tool Type' => 'Torpedo Level', 'Length' => '9 in', 'Vials' => '3', 'Magnetic Base' => 'Yes']],
            ]),
            $this->family('spill-control', 'Spill Control', 'SPILL', 'EA', 47131900, 4235, [
                'Product Type' => 'Spill Control',
            ], [
                ['Universal Absorbent Pad Bale', 42.95, ['Absorbency' => 'Universal', 'Capacity' => '25 gal', 'Package Quantity' => '100', 'Size' => '15 x 19 in']],
                ['Oil Only Absorbent Sock 3 x 48 in', 5.95, ['Absorbency' => 'Oil Only', 'Capacity' => '1 gal', 'Package Quantity' => '1', 'Size' => '3 x 48 in']],
                ['Hazmat Spill Kit 20 gal', 149.95, ['Absorbency' => 'Hazmat', 'Capacity' => '20 gal', 'Container' => 'Duffel Bag', 'Package Quantity' => '1']],
            ]),
            $this->family('wipers', 'Industrial Wiper', 'WIPER', 'BX', 47131500, 7920, [
                'Product Type' => 'Wiper',
            ], [
                ['Blue Shop Towel Roll', 18.95, ['Material' => 'Cellulose/Synthetic', 'Color' => 'Blue', 'Sheet Count' => '200', 'Sheet Size' => '10 x 12 in']],
                ['Lint Free Wiper Box', 32.95, ['Material' => 'Polyester/Cellulose', 'Color' => 'White', 'Sheet Count' => '300', 'Sheet Size' => '9 x 9 in']],
                ['Heavy Duty Degreasing Wipe Bucket', 24.95, ['Material' => 'Polypropylene', 'Color' => 'Orange', 'Sheet Count' => '75', 'Sheet Size' => '10 x 12 in']],
            ]),
            $this->family('floor-care', 'Floor Care', 'FLOOR', 'EA', 47121600, 7920, [
                'Product Type' => 'Floor Care',
            ], [
                ['Push Broom 24 in', 28.95, ['Tool Type' => 'Push Broom', 'Width' => '24 in', 'Bristle Material' => 'Polypropylene', 'Handle Included' => 'Yes']],
                ['Wet Mop Head Medium', 8.95, ['Tool Type' => 'Wet Mop Head', 'Size' => 'Medium', 'Material' => 'Cotton Blend', 'Launderable' => 'Yes']],
                ['Dust Mop 36 in', 34.95, ['Tool Type' => 'Dust Mop', 'Width' => '36 in', 'Material' => 'Cotton', 'Handle Included' => 'Yes']],
            ]),
        ];
    }

    /**
     * @param  array<string, string>  $attributes
     * @param  array<int, array{0: string, 1: float, 2: array<string, string>}>  $variants
     * @return array<string, mixed>
     */
    private function family(string $category, string $baseName, string $skuPrefix, string $unit, int $unspsc, int $psc, array $attributes, array $variants): array
    {
        return [
            'category' => $category,
            'baseName' => $baseName,
            'skuPrefix' => $skuPrefix,
            'unit' => $unit,
            'unspsc' => (string) $unspsc,
            'psc_fsc' => (string) $psc,
            'attributes' => $attributes,
            'variants' => collect($variants)
                ->map(fn (array $variant, int $index) => [
                    'name' => $variant[0],
                    'price' => $variant[1],
                    'attributes' => $variant[2],
                    'index' => $index + 1,
                ])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $variant
     */
    private function product(Vendor $vendor, Category $category, array $definition, array $variant): Product
    {
        $displayName = "{$definition['baseName']} - {$variant['name']}";
        $slug = Str::slug("{$definition['skuPrefix']} {$variant['name']}");
        $sku = sprintf('CS-%s-%03d', $definition['skuPrefix'], $variant['index']);
        $price = (float) $variant['price'];
        $stock = $this->stockFor($sku);
        $madeInUsa = $this->madeInUsa($definition['skuPrefix'], $variant['index']);

        return Product::query()->updateOrCreate(
            [
                'vendor_id' => $vendor->id,
                'sku' => $sku,
            ],
            [
                'category_id' => $category->id,
                'name' => $displayName,
                'slug' => $slug,
                'mpn' => sprintf('%s-%s', $definition['skuPrefix'], Str::upper(Str::slug($variant['name'], ''))),
                'description' => $this->description($definition['baseName'], $variant['name'], $definition['attributes'], $variant['attributes']),
                'image' => sprintf('https://picsum.photos/seed/%s/900/900', $slug),
                'cost' => round($price * 0.62, 2),
                'list_price' => round($price * 1.28, 2),
                'price' => $price,
                'stock' => $stock,
                'reorder_point' => max(5, (int) floor($stock * 0.2)),
                'lead_time_days' => $this->leadTime($definition['skuPrefix'], $variant['index']),
                'is_active' => true,
                'weight_g' => $this->weight($definition['skuPrefix'], $variant['index']),
                'length_mm' => $this->dimension($variant['index'], 90, 760),
                'width_mm' => $this->dimension($variant['index'], 35, 260),
                'height_mm' => $this->dimension($variant['index'], 15, 180),
                'unspsc' => $definition['unspsc'],
                'psc_fsc' => $definition['psc_fsc'],
                'country_of_origin' => $madeInUsa ? 'US' : $this->country($variant['index']),
                'meta' => json_encode(['unit' => $definition['unit']]),
                'standard_type' => $this->standardType($definition['skuPrefix']),
                'naics_code' => '423840',
                'cage_code' => '7CS01',
                'part_number' => sprintf('%s-%04d', $definition['skuPrefix'], $variant['index']),
                'unit_of_measure' => $definition['unit'],
                'minimum_order_quantity' => $definition['unit'] === 'FT' ? 25 : 1,
                'is_hazmat' => in_array($definition['skuPrefix'], ['SPILL', 'WIPER'], true),
                'export_controlled' => false,
                'berry_compliant' => $madeInUsa && in_array($definition['skuPrefix'], ['GLOVE', 'SLING', 'RESP'], true),
                'taa_compliant' => true,
                'made_in_usa' => $madeInUsa,
                'gsa_approved' => $variant['index'] % 2 === 0,
                'qpl_listed' => in_array($definition['skuPrefix'], ['SHCS', 'WIRE', 'RESP'], true),
                'material' => $variant['attributes']['Material'] ?? $variant['attributes']['Body Material'] ?? null,
                'finish' => $variant['attributes']['Finish'] ?? null,
                'specifications' => $this->specifications($definition['attributes'], $variant['attributes']),
                'gsa_price' => round($price * 0.92, 2),
                'contract_price' => round($price * 0.87, 2),
                'search_keywords' => $this->keywords($displayName, $definition['attributes'], $variant['attributes']),
            ]
        );
    }

    /**
     * @param  array<string, string>  $attributes
     */
    private function attributes(Product $product, array $attributes): void
    {
        ProductAttribute::query()
            ->where('product_id', $product->id)
            ->whereNotIn('name', array_keys($attributes))
            ->delete();

        foreach ($attributes as $name => $value) {
            ProductAttribute::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'name' => $name,
                ],
                [
                    'type' => $this->attributeType((string) $value),
                    'value' => (string) $value,
                ]
            );
        }
    }

    /**
     * @param  array<string, Location>  $locations
     * @param  array<string, mixed>  $variant
     */
    private function inventory(Company $company, array $locations, Product $product, array $variant): void
    {
        CompanyProduct::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'product_id' => $product->id,
            ],
            [
                'price' => $product->price,
            ]
        );

        foreach ($locations as $key => $location) {
            $maxStock = $key === 'denver' ? 500 : 160;
            $onHand = $key === 'denver'
                ? $this->stockFor($product->sku)
                : max(0, (int) floor($this->stockFor($product->sku) * 0.35));

            LocationProduct::query()->updateOrCreate(
                [
                    'location_id' => $location->id,
                    'product_id' => $product->id,
                ],
                [
                    'bin_label' => sprintf('%s-%02d-%03d', Str::upper(Str::substr($key, 0, 3)), $variant['index'], $product->id % 100),
                    'reorder_point' => max(5, (int) floor($onHand * 0.25)),
                    'max_stock' => $maxStock,
                    'on_hand' => min($maxStock, $onHand),
                    'visible' => true,
                ]
            );
        }
    }

    /**
     * @param  array<string, string>  $familyAttributes
     * @param  array<string, string>  $variantAttributes
     */
    private function description(string $baseName, string $variantName, array $familyAttributes, array $variantAttributes): string
    {
        $productType = $familyAttributes['Product Type'] ?? $baseName;
        $material = $variantAttributes['Material'] ?? $variantAttributes['Body Material'] ?? 'industrial grade construction';
        $standard = $familyAttributes['Standard'] ?? $familyAttributes['Thread Standard'] ?? 'commercial specification';

        return "{$baseName} {$variantName} for plant maintenance, production, and field service work. {$productType} supplied with {$material} and {$standard} details for filter testing.";
    }

    /**
     * @param  array<string, string>  $familyAttributes
     * @param  array<string, string>  $variantAttributes
     */
    private function specifications(array $familyAttributes, array $variantAttributes): string
    {
        return collect($familyAttributes)
            ->merge($variantAttributes)
            ->map(fn (string $value, string $key) => "{$key}: {$value}")
            ->implode('; ');
    }

    /**
     * @param  array<string, string>  $familyAttributes
     * @param  array<string, string>  $variantAttributes
     */
    private function keywords(string $displayName, array $familyAttributes, array $variantAttributes): string
    {
        return Str::lower($displayName.' '.implode(' ', $familyAttributes).' '.implode(' ', $variantAttributes));
    }

    private function attributeType(string $value): string
    {
        if (in_array(Str::lower($value), ['yes', 'no', 'true', 'false'], true)) {
            return 'boolean';
        }

        if (is_numeric($value)) {
            return str_contains($value, '.') ? 'float' : 'integer';
        }

        return 'string';
    }

    private function stockFor(string $sku): int
    {
        return 35 + (crc32($sku) % 420);
    }

    private function leadTime(string $skuPrefix, int $index): int
    {
        return in_array($skuPrefix, ['HYD', 'LEDWL', 'SLING'], true) ? 5 + $index : 1 + ($index % 4);
    }

    private function weight(string $skuPrefix, int $index): int
    {
        return match ($skuPrefix) {
            'BIN', 'CSTR', 'LEDWL', 'FLOOR' => 900 + ($index * 750),
            'HYD', 'VALVE', 'SLING' => 450 + ($index * 420),
            'WIRE' => 70,
            default => 40 + ($index * 95),
        };
    }

    private function dimension(int $index, int $min, int $max): int
    {
        return min($max, $min + ($index * 37));
    }

    private function madeInUsa(string $skuPrefix, int $index): bool
    {
        return in_array($skuPrefix, ['HB', 'SHCS', 'WIRE', 'HYD', 'SLING', 'SPILL'], true) || $index % 3 === 0;
    }

    private function country(int $index): string
    {
        return ['TW', 'MX', 'CA', 'DE'][$index % 4];
    }

    private function standardType(string $skuPrefix): string
    {
        return match ($skuPrefix) {
            'SHCS', 'WIRE' => 'MS',
            'RESP', 'SLING' => 'MIL-PRF',
            default => 'Commercial',
        };
    }
}
