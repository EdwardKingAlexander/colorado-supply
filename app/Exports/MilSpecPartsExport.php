<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MilSpecPartsExport implements FromCollection, WithColumnWidths, WithHeadings, WithStyles
{
    /**
     * Generate sample mil-spec parts data based on research findings.
     */
    public function collection(): Collection
    {
        return collect([
            // AN (Army-Navy) Standards
            ['AN3-4A', 'Hex Head Bolt', 'AN', 'MS Aerospace', 'Avnet, Arrow Electronics'],
            ['AN4-5A', 'Clevis Bolt', 'AN', 'Parker Hannifin', 'Heilind Electronics, Master Electronics'],
            ['AN6-6A', 'Eye Bolt', 'AN', 'Boeing', 'TTI, DigiKey'],

            // MS (Military Standard) Parts
            ['MS24693C-4', 'Tinnerman Speed Nut', 'MS', 'PEM Fastening Systems', 'Mouser, Rochester Electronics'],
            ['MS3367-3-4', 'Hose Assembly', 'MS', 'Eaton Aerospace', 'Atlantic Fasteners Aerospace, ISO Group'],
            ['MS21042-3', 'Self-Locking Nut', 'MS', 'Alcoa Fastening Systems', 'Mil-Aero Solutions, PartTarget'],
            ['MS90725', 'Electrical Connector', 'MS', 'Amphenol Corporation', 'Avnet, Heilind Electronics'],
            ['MS27039', 'Terminal Lug', 'MS', 'TE Connectivity', 'Arrow Electronics, TTI'],
            ['MS25083', 'Washer, Lock', 'MS', 'Nord-Lock Group', 'DigiKey, Mouser'],

            // NAS (National Aerospace Standard) Parts
            ['NAS1580', 'Precision Bolt', 'NAS', 'Alcoa Fastening Systems', 'Atlantic Fasteners Aerospace, MilSpec Parts'],
            ['NAS1351', 'Rivet, Solid', 'NAS', 'Cherry Aerospace', 'ISO Group, Mil-Aero Solutions'],
            ['NAS679', 'Washer, Flat', 'NAS', 'Lisi Aerospace', 'Master Electronics, PartTarget'],
            ['NAS1523', 'Screw, Machine', 'NAS', 'SPS Technologies', 'Heilind Electronics, Atlantic Fasteners'],
            ['NAS1611', 'Pin, Grooved', 'NAS', 'MS Aerospace', 'Avnet, TTI'],

            // MIL-DTL (Military Detail Specification)
            ['MIL-DTL-38999', 'Circular Connector', 'MIL-DTL', 'Amphenol Corporation', 'Octopart Listed Vendors, DigiKey'],
            ['MIL-DTL-5015', 'Connector, Plug', 'MIL-DTL', 'Glenair Inc.', 'Mouser, Arrow Electronics'],
            ['MIL-DTL-83723', 'Contact, Socket', 'MIL-DTL', 'TE Connectivity', 'Heilind Electronics, Avnet'],
            ['MIL-DTL-26482', 'Connector Assembly', 'MIL-DTL', 'ITT Cannon', 'Rochester Electronics, TTI'],

            // MIL-C (Military Capacitor Specifications)
            ['MIL-C-39029', 'Capacitor, Fixed', 'MIL-C', 'Vishay Intertechnology', 'DigiKey, Mouser, Octopart Vendors'],
            ['MIL-C-55342', 'Capacitor, Chip', 'MIL-C', 'KEMET Corporation', 'Avnet, Arrow Electronics'],
            ['MIL-C-81511', 'Capacitor, Tantalum', 'MIL-C', 'AVX Corporation', 'TTI, Heilind Electronics'],

            // MIL-PRF (Military Performance Specification)
            ['MIL-PRF-19500', 'Semiconductor Device', 'MIL-PRF', 'Texas Instruments', 'DigiKey, Mouser, Rochester Electronics'],
            ['MIL-PRF-38534', 'Hybrid Microcircuit', 'MIL-PRF', 'Microsemi Corporation', 'Avnet, Arrow Electronics'],
            ['MIL-PRF-55365', 'Resistor, Film', 'MIL-PRF', 'Vishay Dale', 'Heilind Electronics, TTI'],

            // Additional Common Parts
            ['MS25171', 'Cotter Pin', 'MS', 'Alcoa Fastening Systems', 'Atlantic Fasteners Aerospace, MilSpec Parts'],
            ['NAS1149', 'Washer, Flat', 'NAS', 'Lisi Aerospace', 'ISO Group, Mil-Aero Solutions'],
            ['AN960', 'Washer, Plain', 'AN', 'MS Aerospace', 'Master Electronics, PartTarget'],
            ['MS35338', 'Screw, Cap', 'MS', 'SPS Technologies', 'DigiKey, Mouser'],
            ['NAS1352', 'Rivet, Countersunk', 'NAS', 'Cherry Aerospace', 'Atlantic Fasteners, Avnet'],
            ['MIL-DTL-27426', 'Terminal Board', 'MIL-DTL', 'Curtis Industries', 'Arrow Electronics, TTI'],
        ]);
    }

    /**
     * Define column headings.
     */
    public function headings(): array
    {
        return [
            'Mil-Spec Part Number',
            'Description',
            'Standard Type',
            'Manufacturer(s)',
            'Vendor(s)',
        ];
    }

    /**
     * Apply styles to the worksheet.
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF'],
                    'bold' => true,
                ],
            ],
        ];
    }

    /**
     * Set column widths for better readability.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 25,  // Part Number
            'B' => 30,  // Description
            'C' => 15,  // Standard Type
            'D' => 35,  // Manufacturer(s)
            'E' => 45,  // Vendor(s)
        ];
    }
}
