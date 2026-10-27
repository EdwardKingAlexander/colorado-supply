<?php

namespace App\Services\Catalog;

use App\Jobs\RebuildDibbsMatchesJob;
use App\Models\Manufacturer;
use App\Models\MilSpecPart;
use App\Models\Supplier;
use App\Services\Dibbs\DibbsRfqMatcher;
use Illuminate\Support\Facades\DB;

/**
 * Imports the NSN parts catalog (MilSpecPart + manufacturer + supplier) from
 * CSV/XLSX rows, in two steps: analyze() validates and counts without saving;
 * commit() saves in one transaction and rebuilds DIBBS matches once.
 *
 * Columns (headers are case-insensitive): nsn*, description*,
 * manufacturer_name*, manufacturer_cage, manufacturer_part_number,
 * supplier_name, supplier_cage, supplier_part_number.
 * (* manufacturer_name may be omitted when manufacturer_cage matches an
 * existing manufacturer.)
 *
 * Manufacturers and suppliers are matched by CAGE first, then by name
 * (case-insensitive), and created when missing. Parts are matched by NSN with
 * or without dashes and stored in the catalog's dashed format.
 */
class MilSpecPartCatalogImporter
{
    public const COLUMNS = [
        'nsn', 'description', 'manufacturer_name', 'manufacturer_cage',
        'manufacturer_part_number', 'supplier_name', 'supplier_cage', 'supplier_part_number',
    ];

    /**
     * @param  iterable<int, array<string, mixed>>  $rows  heading-keyed rows
     * @return array{rows: array<int, array<string, ?string>>, errors: array<int, array{row: int, message: string}>, new_parts: int, updated_parts: int, new_manufacturers: int, new_suppliers: int, is_valid: bool}
     */
    public function analyze(iterable $rows, int $firstRowNumber = 2): array
    {
        $clean = [];
        $errors = [];
        $seen = [];
        $existingParts = MilSpecPart::query()->pluck('nsn')->mapWithKeys(fn ($nsn) => [self::normalizeNsn($nsn) => true]);
        $newManufacturers = [];
        $newSuppliers = [];

        foreach ($rows as $i => $raw) {
            $rowNo = $firstRowNumber + $i;
            $row = collect(self::COLUMNS)->mapWithKeys(fn ($c) => [$c => self::text($raw[$c] ?? null)])->all();

            if (array_filter($row) === []) {
                continue; // blank line
            }

            $nsn = self::normalizeNsn($row['nsn'] ?? '');
            $problem = match (true) {
                ! preg_match('/^\d{4}[0-9A-Z]{9}$/', $nsn) => "NSN '{$row['nsn']}' must be a 4-digit FSC plus 9 characters (e.g. 5305-01-234-5678).",
                isset($seen[$nsn]) => "NSN {$row['nsn']} appears more than once in this file (first on row {$seen[$nsn]}).",
                $row['description'] === null => 'Description is required.',
                $row['manufacturer_cage'] !== null && ! self::validCage($row['manufacturer_cage']) => "Manufacturer CAGE '{$row['manufacturer_cage']}' must be 5 letters/digits.",
                $row['supplier_cage'] !== null && ! self::validCage($row['supplier_cage']) => "Supplier CAGE '{$row['supplier_cage']}' must be 5 letters/digits.",
                $row['manufacturer_name'] === null && ! $this->findManufacturer(null, $row['manufacturer_cage']) => 'Manufacturer name is required (no existing manufacturer has that CAGE).',
                $row['supplier_name'] === null && $row['supplier_cage'] !== null && ! $this->findSupplier(null, $row['supplier_cage']) => 'Supplier name is required (no existing supplier has that CAGE).',
                default => null,
            };

            if ($problem !== null) {
                $errors[] = ['row' => $rowNo, 'message' => $problem];

                continue;
            }

            $seen[$nsn] = $rowNo;
            $row['nsn'] = $nsn;
            $clean[] = $row;

            if (! $this->findManufacturer($row['manufacturer_name'], $row['manufacturer_cage'])) {
                $newManufacturers[strtoupper($row['manufacturer_cage'] ?? '').'|'.mb_strtolower($row['manufacturer_name'])] = true;
            }

            if ($row['supplier_name'] !== null && ! $this->findSupplier($row['supplier_name'], $row['supplier_cage'])) {
                $newSuppliers[strtoupper($row['supplier_cage'] ?? '').'|'.mb_strtolower($row['supplier_name'])] = true;
            }
        }

        $newParts = count(array_filter($clean, fn ($r) => ! isset($existingParts[$r['nsn']])));

        return [
            'rows' => $clean,
            'errors' => $errors,
            'new_parts' => $newParts,
            'updated_parts' => count($clean) - $newParts,
            'new_manufacturers' => count($newManufacturers),
            'new_suppliers' => count($newSuppliers),
            'is_valid' => $errors === [] && $clean !== [],
        ];
    }

    /**
     * Save an analyzed plan. Rejects plans with errors.
     *
     * @return array{created: int, updated: int, matches: array{nsn_catalog: int, approved_source: int}}
     */
    public function commit(array $plan): array
    {
        if (! $plan['is_valid']) {
            throw new \InvalidArgumentException('The catalog file has errors; fix them before importing.');
        }

        [$created, $updated] = RebuildDibbsMatchesJob::withoutRebuilds(fn () => DB::transaction(function () use ($plan) {
            $created = 0;
            $updated = 0;

            foreach ($plan['rows'] as $row) {
                $manufacturer = $this->findManufacturer($row['manufacturer_name'], $row['manufacturer_cage'])
                    ?? Manufacturer::create(['name' => $row['manufacturer_name'], 'cage_code' => $row['manufacturer_cage'] ? strtoupper($row['manufacturer_cage']) : null]);

                // A name match without a CAGE learns the CAGE from the file.
                if ($row['manufacturer_cage'] && ! $manufacturer->cage_code) {
                    $manufacturer->update(['cage_code' => strtoupper($row['manufacturer_cage'])]);
                }

                $part = MilSpecPart::query()->whereRaw("replace(nsn, '-', '') = ?", [$row['nsn']])->first();
                $attributes = [
                    'description' => $row['description'],
                    'manufacturer_id' => $manufacturer->id,
                    'manufacturer_part_number' => $row['manufacturer_part_number'] ?? $part?->manufacturer_part_number,
                ];

                if ($part) {
                    $part->update($attributes);
                    $updated++;
                } else {
                    $part = MilSpecPart::create($attributes + ['nsn' => self::formatNsn($row['nsn'])]);
                    $created++;
                }

                if ($row['supplier_name'] !== null || $row['supplier_cage'] !== null) {
                    $supplier = $this->findSupplier($row['supplier_name'], $row['supplier_cage'])
                        ?? Supplier::create(['name' => $row['supplier_name'], 'cage_code' => $row['supplier_cage'] ? strtoupper($row['supplier_cage']) : null]);

                    $part->suppliers()->syncWithoutDetaching([$supplier->id => ['supplier_part_number' => $row['supplier_part_number']]]);
                }
            }

            return [$created, $updated];
        }));

        return ['created' => $created, 'updated' => $updated, 'matches' => app(DibbsRfqMatcher::class)->rebuild()];
    }

    /**
     * Sample rows for the downloadable template.
     *
     * @return array<int, array<int, string>>
     */
    public static function templateRows(): array
    {
        return [
            self::COLUMNS,
            ['5305-01-234-5678', 'SCREW,CAP,HEXAGON HEAD', 'Example Fastener Co', '1ABC2', 'HCS-0500-13', 'Example Industrial Supply', '9XYZ8', 'EIS-55012'],
            ['4820-01-555-0101', 'VALVE,GATE', 'Example Valve Co', '79227', 'GV-200', '', '', ''],
        ];
    }

    public static function normalizeNsn(?string $nsn): string
    {
        return strtoupper(preg_replace('/[^0-9A-Za-z]/', '', (string) $nsn));
    }

    /**
     * 13 characters → XXXX-XX-XXX-XXXX (the catalog's existing format).
     */
    public static function formatNsn(string $normalized): string
    {
        return substr($normalized, 0, 4).'-'.substr($normalized, 4, 2).'-'.substr($normalized, 6, 3).'-'.substr($normalized, 9);
    }

    protected function findManufacturer(?string $name, ?string $cage): ?Manufacturer
    {
        return ($cage ? Manufacturer::whereRaw('upper(cage_code) = ?', [strtoupper($cage)])->first() : null)
            ?? ($name ? Manufacturer::whereRaw('lower(name) = ?', [mb_strtolower($name)])->first() : null);
    }

    protected function findSupplier(?string $name, ?string $cage): ?Supplier
    {
        return ($cage ? Supplier::whereRaw('upper(cage_code) = ?', [strtoupper($cage)])->first() : null)
            ?? ($name ? Supplier::whereRaw('lower(name) = ?', [mb_strtolower($name)])->first() : null);
    }

    protected static function validCage(string $cage): bool
    {
        return (bool) preg_match('/^[0-9A-Za-z]{5}$/', $cage);
    }

    protected static function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
