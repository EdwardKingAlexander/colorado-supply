<?php

namespace App\Services\PubLog;

use RuntimeException;

class PubLogWriter
{
    public function write(int $importId, string $member, array $records): int
    {
        if ($records === []) {
            return 0;
        }
        $table = PubLogSchema::members()[$member]['table'];
        $physical = PubLogTables::staging($table);
        $key = match ($table) {
            'nsn_catalog_items' => 'niin',
            'cage_entities' => 'cage_code',
            default => 'row_hash',
        };
        $hashKey = $member === 'P_CAGE.CSV' ? 'summary_hash' : ($member === 'V_CAGE_ADDRESS.CSV' ? 'address_hash' : 'row_hash');
        $batch = [];
        foreach ($records as [$number, $row]) {
            $mapped = $this->map($member, $row, $number);
            $identity = $mapped[$key];
            if (isset($batch[$identity]) && $batch[$identity][$hashKey] !== $mapped[$hashKey]) {
                throw new RuntimeException("Conflicting duplicate {$key} in {$member}.");
            }
            $batch[$identity] ??= ['import_id' => $importId, ...$mapped];
        }
        // PHP coerces numeric array keys to integers. Use original values so
        // MySQL compares text identifiers as text and can use the full index.
        $identities = array_column($batch, $key);
        // Relationship identity is unique per (import_id, kind, row_hash); omitting
        // kind limits MySQL to the import_id prefix and scans the whole import.
        $existing = PubLogDb::connection()->table($physical)->where('import_id', $importId)
            ->when($table === 'nsn_catalog_relationships', fn ($query) => $query->where('kind', reset($batch)['kind']))
            ->whereIn($key, $identities)->get([$key, $hashKey])->keyBy($key);
        foreach ($batch as $identity => $row) {
            if (isset($existing[$identity]) && $existing[$identity]->{$hashKey} !== null) {
                if ($existing[$identity]->{$hashKey} !== $row[$hashKey]) {
                    throw new RuntimeException("Conflicting existing {$key} in {$member}.");
                }
                unset($batch[$identity]);
            }
        }
        if ($batch === []) {
            return 0;
        }
        if ($table === 'cage_entities') {
            // The address and summary are independent source facts on the same entity.
            $fields = $member === 'P_CAGE.CSV' ? ['summary', 'summary_row', 'summary_hash'] : ['address', 'address_row', 'address_hash'];
            PubLogDb::connection()->table($physical)->upsert(array_values($batch), ['import_id', 'cage_code'], $fields);
        } else {
            PubLogDb::connection()->table($physical)->insert(array_values($batch));
        }

        return count($batch);
    }

    private function map(string $member, array $row, int $number): array
    {
        foreach (['NIIN' => '/^[0-9]{9}$/D', 'FSC' => '/^[0-9]{4}$/D'] as $field => $pattern) {
            if (isset($row[$field]) && ! preg_match($pattern, $row[$field])) {
                throw new RuntimeException("Invalid {$field} at {$member} record {$number}.");
            }
        }
        // Preserve source anomalies such as `Q9C4; consumers expose a validity
        // flag and must not treat these as verified CAGE identities.
        if (isset($row['CAGE_CODE']) && (mb_strlen($row['CAGE_CODE'], 'UTF-8') !== 5 || preg_match('/[\x00-\x1F\x7F]/', $row['CAGE_CODE']))) {
            throw new RuntimeException("Invalid CAGE_CODE length/control character at {$member} record {$number}.");
        }
        ksort($row);
        $hash = hash('sha256', json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $provenance = ['source_row' => $number, 'row_hash' => $hash];
        if (in_array($member, ['V_FLIS_PART.CSV', 'V_FLIS_STANDARDIZATION.CSV', 'V_FLIS_CANCELLED_NIIN.CSV'], true)) {
            $provenance['row_hash'] = hex2bin($hash);
        }
        if ($member === 'P_FLIS_NSN.CSV') {
            // Official October data contains short INC values; preserve rather than pad them.
            if ($row['INC'] !== '' && ! preg_match('/^[0-9]{1,5}$/D', $row['INC'])) {
                throw new RuntimeException("Invalid item name code at {$member} record {$number}.");
            }

            return [...$provenance, 'niin' => $row['NIIN'], 'fsc' => $row['FSC'], 'nsn' => $row['FSC'].$row['NIIN'], 'inc' => $row['INC'], 'item_name' => $row['ITEM_NAME'], 'sos' => $row['SOS'], 'end_item_name' => $row['END_ITEM_NAME'], 'cancelled_niin' => $row['CANCELLED_NIIN']];
        }
        if (in_array($member, ['P_CAGE.CSV', 'V_CAGE_ADDRESS.CSV'], true)) {
            $prefix = $member === 'P_CAGE.CSV' ? 'summary' : 'address';

            return ['cage_code' => $row['CAGE_CODE'], $prefix => json_encode($row, JSON_THROW_ON_ERROR), $prefix.'_row' => $number, $prefix.'_hash' => $hash];
        }
        if ($member === 'V_FLIS_PART.CSV') {
            $searchKey = mb_strtoupper(trim($row['PART_NUMBER']), 'UTF-8');
            if ($searchKey === '' || mb_strlen($searchKey) > 255 || mb_strlen($row['PART_NUMBER']) > 255) {
                throw new RuntimeException('Empty or oversized reference number.');
            }
            $codes = $this->columns(array_diff_key($row, array_flip(['NIIN', 'PART_NUMBER', 'CAGE_CODE'])));

            return [...$provenance, 'niin' => $row['NIIN'], 'part_number' => $row['PART_NUMBER'], 'part_search_key' => $searchKey, 'cage_code' => $row['CAGE_CODE'], ...$codes];
        }
        if ($member === 'P_HELP.CSV') {
            if ($row['CODE'] === '' || mb_strlen($row['CODE']) > 255) {
                throw new RuntimeException('Invalid definition code.');
            }

            return [...$provenance, 'code' => $row['CODE'], 'literal' => $row['LITERAL'], 'description' => $row['DESCRIPTION'], 'regs' => $row['REGS']];
        }

        return [...$provenance, 'kind' => $member === 'V_FLIS_STANDARDIZATION.CSV' ? 'standardization' : 'cancellation', ...$this->columns($row)];
    }

    private function columns(array $row): array
    {
        $columns = [];
        foreach ($row as $name => $value) {
            if (mb_strlen($value, 'UTF-8') > 255) {
                throw new RuntimeException("Oversized source value for {$name}; no truncation permitted.");
            }
            $columns[strtolower($name)] = $value;
        }

        return $columns;
    }
}
