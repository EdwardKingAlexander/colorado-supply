<?php

namespace App\Reporting\Fields;

use App\Reporting\FieldType;

/**
 * The location a line is assigned to: `ll` is that location, `plc` its parent.
 * Names use the same rules as the original dashboard reports (a top-level
 * location's own lines show as sublocation "Primary").
 */
final class LocationFields
{
    /**
     * @return list<Field>
     */
    public static function fields(string $lineAlias): array
    {
        $names = ['leaf_location', 'parent_location'];

        return [
            new Field('location.parent_name', 'Location', 'Location', FieldType::String,
                "COALESCE(plc.name, ll.name, 'Unassigned')", $names),
            new Field('location.sublocation_name', 'Sublocation', 'Location', FieldType::String,
                "CASE WHEN plc.id IS NULL THEN 'Primary' ELSE ll.name END", $names),
            new Field('location.id', 'Location or sublocation', 'Location', FieldType::Location,
                "{$lineAlias}.location_id", ['leaf_location'], parentExpression: 'll.parent_id', selectable: false),
        ];
    }

    /**
     * @return array<string, Join>
     */
    public static function joins(string $lineAlias): array
    {
        return [
            'leaf_location' => new Join('locations', 'll', 'll.id', "{$lineAlias}.location_id"),
            'parent_location' => new Join('locations', 'plc', 'plc.id', 'll.parent_id', dependsOn: ['leaf_location']),
        ];
    }
}
