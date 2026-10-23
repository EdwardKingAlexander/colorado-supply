<?php

namespace App\Reporting\Query;

use App\Models\User;
use App\Reporting\DatasetRegistry;
use App\Reporting\Datasets\Dataset;
use App\Reporting\Definition\DateValue;
use App\Reporting\Definition\FilterGroup;
use App\Reporting\Definition\FilterRule;
use App\Reporting\Definition\ReportDefinition;
use App\Reporting\Fields\Field;
use App\Reporting\FieldType;
use App\Reporting\Operator;
use App\Reporting\RelativeDate;
use App\Reporting\Sql\SqlDialect;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Turns a validated definition into a query. Only field expressions from the
 * catalog reach the SQL; every user value is a binding. User filters are
 * wrapped in their own group so an OR can never widen the tenant scope.
 */
class QueryCompiler
{
    private const DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function __construct(private DatasetRegistry $registry) {}

    public function compile(ReportDefinition $definition, User $user): CompiledReport
    {
        $dataset = $this->registry->get($definition->dataset);
        $dialect = SqlDialect::for(DB::connection());
        $query = $dataset->newQuery();

        $fields = array_map(fn ($column) => $dataset->field($column->field), $definition->columns);
        $filterFields = array_map(fn (FilterRule $rule) => $dataset->field($rule->field), $definition->filters?->flatRules() ?? []);

        $dataset->addJoins($query, array_values(array_unique(array_merge(
            ...array_map(fn (Field $field) => $field->joins, [...$fields, ...$filterFields]),
        ))));
        $dataset->applyScope($query, $user);

        $columns = [];

        foreach ($definition->columns as $index => $column) {
            $field = $fields[$index];
            $expression = $field->sql($dialect);

            if ($column->bucket !== null) {
                $expression = $dialect->dateBucket($expression, $column->bucket);
            }

            $query->selectRaw("{$expression} as c{$index}");

            $columns[] = [
                'key' => $column->key,
                'label' => $column->label ?? $field->label,
                'field' => $field->key,
                // A bucketed date is a period label, not a date.
                'type' => $column->bucket !== null ? FieldType::String->value : $field->type->value,
                'format' => $column->format,
                'bucket' => $column->bucket?->value,
                'options' => $field->options,
            ];
        }

        if ($definition->filters !== null) {
            $this->applyGroup($query, $definition->filters, $dataset, $dialect, 'and');
        }

        $keys = array_column($columns, 'key');

        foreach ($definition->sort as $sort) {
            $query->orderBy('c'.array_search($sort->column, $keys, true), $sort->direction);
        }

        // Stable order so previews and exports agree row for row.
        $query->orderBy("{$dataset->alias()}.id");

        return new CompiledReport($query, $columns);
    }

    private function applyGroup(Builder $query, FilterGroup $group, Dataset $dataset, SqlDialect $dialect, string $boolean): void
    {
        $query->where(function (Builder $nested) use ($group, $dataset, $dialect) {
            foreach ($group->rules as $rule) {
                $rule instanceof FilterGroup
                    ? $this->applyGroup($nested, $rule, $dataset, $dialect, $group->logic)
                    : $this->applyRule($nested, $rule, $dataset->field($rule->field), $dialect, $group->logic);
            }
        }, boolean: $boolean);
    }

    private function applyRule(Builder $query, FilterRule $rule, Field $field, SqlDialect $dialect, string $boolean): void
    {
        $expression = $field->sql($dialect);
        $value = $rule->value;

        if ($field->type === FieldType::Location) {
            $this->applyLocationRule($query, $rule, $expression, $field->parentExpression, $boolean);

            return;
        }

        if ($field->type->isTemporal()) {
            $this->applyDateRule($query, $rule, $expression, $boolean);

            return;
        }

        $placeholder = $field->type->isNumeric() ? $dialect->numericPlaceholder() : '?';
        $list = fn (array $values) => implode(', ', array_fill(0, count($values), $placeholder));

        match ($rule->operator) {
            Operator::Equals => $query->whereRaw("{$expression} = {$placeholder}", [$value], $boolean),
            // "Is not X" keeps blanks, which is what people mean by it.
            Operator::NotEquals => $query->whereRaw("({$expression} <> {$placeholder} OR {$expression} IS NULL)", [$value], $boolean),
            Operator::In => $query->whereRaw("{$expression} IN ({$list($value)})", array_values($value), $boolean),
            Operator::NotIn => $query->whereRaw("({$expression} NOT IN ({$list($value)}) OR {$expression} IS NULL)", array_values($value), $boolean),
            Operator::GreaterThan => $query->whereRaw("{$expression} > {$placeholder}", [$value], $boolean),
            Operator::GreaterThanOrEqual => $query->whereRaw("{$expression} >= {$placeholder}", [$value], $boolean),
            Operator::LessThan => $query->whereRaw("{$expression} < {$placeholder}", [$value], $boolean),
            Operator::LessThanOrEqual => $query->whereRaw("{$expression} <= {$placeholder}", [$value], $boolean),
            Operator::Between => $query->whereRaw("{$expression} BETWEEN {$placeholder} AND {$placeholder}", array_values($value), $boolean),
            Operator::Contains => $query->whereRaw("{$expression} LIKE ? ESCAPE '!'", ['%'.$this->escapeLike((string) $value).'%'], $boolean),
            Operator::StartsWith => $query->whereRaw("{$expression} LIKE ? ESCAPE '!'", [$this->escapeLike((string) $value).'%'], $boolean),
            Operator::IsNull => $query->whereRaw("{$expression} IS NULL", [], $boolean),
            Operator::NotNull => $query->whereRaw("{$expression} IS NOT NULL", [], $boolean),
            Operator::IsTrue => $query->whereRaw("COALESCE({$expression}, 0) = 1", [], $boolean),
            Operator::IsFalse => $query->whereRaw("COALESCE({$expression}, 0) = 0", [], $boolean),
            Operator::Relative => null, // temporal only, handled above
        };
    }

    private function applyDateRule(Builder $query, FilterRule $rule, string $expression, string $boolean): void
    {
        $format = fn (CarbonImmutable $date) => $date->format(self::DATETIME_FORMAT);
        $value = $rule->value;

        if ($rule->operator === Operator::Relative || $rule->operator === Operator::Between) {
            [$start, $end] = $rule->operator === Operator::Relative
                ? RelativeDate::from($value)->range()
                : [DateValue::parse($value[0])->start(), DateValue::parse($value[1])->end()];

            $query->whereRaw("{$expression} BETWEEN ? AND ?", [$format($start), $format($end)], $boolean);

            return;
        }

        if ($rule->operator === Operator::IsNull || $rule->operator === Operator::NotNull) {
            $query->whereRaw($expression.($rule->operator === Operator::IsNull ? ' IS NULL' : ' IS NOT NULL'), [], $boolean);

            return;
        }

        // A bare date means the whole day: "after Jan 5" starts on Jan 6.
        $date = DateValue::parse($value);

        match ($rule->operator) {
            Operator::Equals => $query->whereRaw("{$expression} BETWEEN ? AND ?", [$format($date->start()), $format($date->end())], $boolean),
            Operator::GreaterThan => $query->whereRaw("{$expression} > ?", [$format($date->end())], $boolean),
            Operator::GreaterThanOrEqual => $query->whereRaw("{$expression} >= ?", [$format($date->start())], $boolean),
            Operator::LessThan => $query->whereRaw("{$expression} < ?", [$format($date->start())], $boolean),
            Operator::LessThanOrEqual => $query->whereRaw("{$expression} <= ?", [$format($date->end())], $boolean),
        };
    }

    /**
     * Matches the chosen locations and everything beneath them.
     */
    private function applyLocationRule(Builder $query, FilterRule $rule, string $leaf, string $parent, string $boolean): void
    {
        if ($rule->operator === Operator::IsNull || $rule->operator === Operator::NotNull) {
            $query->whereRaw($leaf.($rule->operator === Operator::IsNull ? ' IS NULL' : ' IS NOT NULL'), [], $boolean);

            return;
        }

        $ids = array_map('intval', array_values($rule->value));
        $list = implode(', ', array_fill(0, count($ids), '?'));

        $rule->operator === Operator::In
            ? $query->whereRaw("({$leaf} IN ({$list}) OR {$parent} IN ({$list}))", [...$ids, ...$ids], $boolean)
            : $query->whereRaw(
                "(({$leaf} IS NULL OR {$leaf} NOT IN ({$list})) AND ({$parent} IS NULL OR {$parent} NOT IN ({$list})))",
                [...$ids, ...$ids],
                $boolean,
            );
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
