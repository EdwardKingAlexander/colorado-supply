<?php

namespace App\Reporting\Processing;

use App\Reporting\Definition\CalculatedField;
use App\Reporting\Formula\Evaluator;
use App\Reporting\Formula\Node;
use App\Reporting\Formula\ReferenceNode;

/**
 * Adds calculated fields to result rows, evaluating formulas that use other
 * calculated fields after the ones they depend on.
 */
final class FormulaCalculator
{
    /** @var list<CalculatedField> */
    private array $ordered;

    /**
     * @param  list<CalculatedField>  $fields  validated (no cycles)
     */
    public function __construct(array $fields)
    {
        $byKey = [];
        foreach ($fields as $field) {
            $byKey[$field->key] = $field;
        }

        $this->ordered = [];
        $done = [];

        $visit = function (CalculatedField $field) use (&$visit, &$done, $byKey) {
            if (isset($done[$field->key])) {
                return;
            }

            $done[$field->key] = true;

            foreach (self::references($field->ast()) as $name) {
                if (isset($byKey[$name])) {
                    $visit($byKey[$name]);
                }
            }

            $this->ordered[] = $field;
        };

        array_map($visit, $fields);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function apply(array $row): array
    {
        foreach ($this->ordered as $field) {
            $value = Evaluator::evaluate($field->ast(), $row);

            $row[$field->key] = $value === null ? null : round($value, $field->format === 'currency' ? 2 : 6);
        }

        return $row;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    public function applyAll(array $rows): array
    {
        return array_map(fn (array $row) => $this->apply($row), $rows);
    }

    /**
     * @return list<string>
     */
    private static function references(Node $node): array
    {
        $names = $node instanceof ReferenceNode && ! $node->isField() ? [$node->name] : [];

        foreach ($node->children() as $child) {
            array_push($names, ...self::references($child));
        }

        return $names;
    }
}
