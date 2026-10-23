<?php

namespace App\Reporting;

use App\Models\User;
use App\Reporting\Definition\ReportDefinition;
use App\Reporting\Query\QueryCompiler;

class ReportRunner
{
    public function __construct(private QueryCompiler $compiler) {}

    /**
     * @param  ?int  $maxRows  return at most this many rows (e.g. Limits::PREVIEW_ROWS); the definition's limit still applies
     */
    public function run(User $user, ReportDefinition $definition, ?int $maxRows = null): ReportResult
    {
        $compiled = $this->compiler->compile($definition, $user);

        $rowCount = min($definition->limit, (clone $compiled->query)->reorder()->count());
        $take = min($definition->limit, $maxRows ?? $definition->limit);

        $rows = $compiled->query->limit($take)->get()->map(function (object $row) use ($compiled) {
            $values = [];

            foreach ($compiled->columns as $index => $column) {
                $values[$column['key']] = $this->cast($row->{"c{$index}"}, FieldType::from($column['type']));
            }

            return $values;
        })->all();

        return new ReportResult($compiled->columns, $rows, $rowCount, count($rows) < $rowCount);
    }

    private function cast(mixed $value, FieldType $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            FieldType::Money => round((float) $value, 2),
            FieldType::Number => is_numeric($value) ? $value + 0 : null,
            FieldType::Boolean => (bool) (int) $value,
            default => (string) $value,
        };
    }
}
