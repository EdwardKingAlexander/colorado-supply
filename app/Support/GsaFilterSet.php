<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class GsaFilterSet
{
    /**
     * Get default NAICS codes for federal procurement.
     * These can be stored in database or config.
     */
    public static function getDefaultNaicsCodes(): array
    {
        return Cache::remember('gsa_default_naics', 3600, function () {
            // Default NAICS codes for industrial/MRO supplies
            return [
                '423840', // Industrial Supplies Merchant Wholesalers
                '423830', // Industrial Machinery/Equipment Wholesalers
                '423720', // Plumbing/Heating Equipment & Supplies
                '423810', // Construction/Mining Machinery Wholesalers
                '423860', // Transportation Equipment & Supplies
                '423850', // Service Establishment Equipment
            ];
        });
    }

    /**
     * Get default PSC (Product Service Codes) for federal procurement.
     */
    public static function getDefaultPscCodes(): array
    {
        return Cache::remember('gsa_default_psc', 3600, function () {
            // High-signal PSC codes for industrial supplies and maintenance
            return [
                '5340', // Hardware, Commercial
                '5305', // Screws
                '5306', // Bolts
                '5310', // Nuts and Washers
                '5315', // Nails, Machine Keys, and Pins
                '5330', // Packing and Gasket Materials
                '5365', // Bushings, Rings, Shims, and Spacers
                '4710', // Pipe and Tube
                '4720', // Hose and Tubing, Flexible
                '4730', // Hose, Pipe, Tube, Lubrication, and Railing Fittings
                '5120', // Hand Tools, Nonpowered
                '5130', // Hand Tools, Power Driven
                '5140', // Tools and Hardware, General Purpose
                '5180', // Fastening Devices
                '7510', // Office Supplies
            ];
        });
    }

    /**
     * Get human-readable description for a NAICS code.
     */
    public static function getNaicsDescription(string $code): string
    {
        $descriptions = [
            '423840' => 'Industrial Supplies Merchant Wholesalers',
            '423830' => 'Industrial Machinery & Equipment Wholesalers',
            '423720' => 'Plumbing & Heating Equipment Wholesalers',
            '423810' => 'Construction & Mining Machinery Wholesalers',
            '423860' => 'Transportation Equipment & Supplies Wholesalers',
            '423850' => 'Service Establishment Equipment Wholesalers',
        ];

        return $descriptions[$code] ?? 'Unknown NAICS';
    }
}
