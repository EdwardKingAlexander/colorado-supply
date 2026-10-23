<?php

namespace App\Reporting\Query;

use App\Reporting\FieldType;
use Illuminate\Database\Query\Builder;

/**
 * Runs a compiled query and turns database rows into arrays keyed by column
 * key, with values cast to their report types.
 */
final class RowFetcher
{
    /**
     * @param  list<string>  $raw  keys to return uncast (e.g. a pivot column holding a sentinel)
     * @return list<array<string, mixed>>
     */
    public static function fetch(CompiledReport $compiled, Builder $query, ?int $limit = null, array $raw = []): array
    {
        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()->map(function (object $row) use ($compiled, $raw) {
            $values = [];

            foreach ($compiled->selects as $select) {
                if (property_exists($row, $select->alias)) {
                    $value = $row->{$select->alias};
                    $values[$select->key] = in_array($select->key, $raw, true) ? $value : self::cast($value, $select->type);
                }
            }

            return $values;
        })->all();
    }

    public static function cast(mixed $value, FieldType $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            FieldType::Money => round((float) $value, 2),
            FieldType::Number => is_numeric($value) ? $value + 0 : null,
            FieldType::Boolean => (bool) (int) $value,
            FieldType::Id => (int) $value,
            default => (string) $value,
        };
    }
}
