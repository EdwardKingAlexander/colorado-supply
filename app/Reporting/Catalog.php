<?php

namespace App\Reporting;

use App\Models\Location;
use App\Models\User;
use App\Reporting\Definition\ColumnSpec;
use App\Reporting\Definition\FormatRule;
use App\Reporting\Definition\PostProcessorSpec;
use App\Reporting\Fields\Field;
use App\Reporting\Formula\Parser;

/**
 * Everything the builder UI needs to offer valid choices: datasets and their
 * fields, and the vocabulary of operators, totals, buckets and functions.
 * Location choices are limited to the user's own company.
 */
class Catalog
{
    public function __construct(private DatasetRegistry $registry) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        $datasets = [];

        foreach ($this->registry->all() as $dataset) {
            $datasets[] = [
                'key' => $dataset->key(),
                'label' => $dataset->label(),
                'description' => $dataset->description(),
                'fields' => array_values(array_map(fn (Field $field) => [
                    'key' => $field->key,
                    'label' => $field->label,
                    'group' => $field->group,
                    'type' => $field->type->value,
                    'selectable' => $field->selectable,
                    'filterable' => $field->filterable,
                    'aggregates' => array_map(fn (Aggregate $aggregate) => $aggregate->value, Aggregate::allowedFor($field->type)),
                    'operators' => $field->filterable ? array_map(fn (Operator $operator) => $operator->value, $field->type->operators()) : [],
                    'options' => array_map(
                        fn (string $value, string $label) => ['value' => $value, 'label' => $label],
                        array_keys($field->options),
                        $field->options,
                    ),
                ], $dataset->fields())),
            ];
        }

        return [
            'datasets' => $datasets,
            'modes' => [
                ['value' => 'table', 'label' => 'Table'],
                ['value' => 'summary', 'label' => 'Summary'],
                ['value' => 'pivot', 'label' => 'Pivot'],
            ],
            'aggregates' => array_map(fn (Aggregate $aggregate) => ['value' => $aggregate->value, 'label' => $aggregate->label()], Aggregate::cases()),
            'buckets' => array_map(fn (DateBucket $bucket) => ['value' => $bucket->value, 'label' => ucfirst($bucket->value)], DateBucket::cases()),
            'relative_dates' => array_map(fn (RelativeDate $date) => ['value' => $date->value, 'label' => ucfirst(str_replace('_', ' ', $date->value))], RelativeDate::cases()),
            'formats' => ColumnSpec::FORMATS,
            'post_processors' => array_map(fn (string $value, string $label) => ['value' => $value, 'label' => $label], array_keys(PostProcessorSpec::OPERATIONS), PostProcessorSpec::OPERATIONS),
            'formula_functions' => [...array_keys(Parser::FUNCTIONS), ...array_map(fn (Aggregate $aggregate) => $aggregate->formulaName(), Aggregate::cases())],
            'format_rules' => ['operators' => FormatRule::OPERATORS, 'styles' => FormatRule::STYLES],
            'locations' => $this->locations($user),
            'limits' => [
                'columns' => Limits::MAX_COLUMNS,
                'filter_depth' => Limits::MAX_FILTER_DEPTH,
                'rows' => Limits::MAX_ROW_LIMIT,
                'groups' => Limits::MAX_GROUPS,
                'preview_rows' => Limits::PREVIEW_ROWS,
                'pivot_columns' => Limits::MAX_PIVOT_COLUMNS,
                'pivot_row_fields' => Limits::MAX_PIVOT_ROW_FIELDS,
                'pivot_values' => Limits::MAX_PIVOT_VALUES,
                'calculated' => Limits::MAX_CALCULATED,
                'formula_length' => Parser::MAX_LENGTH,
            ],
        ];
    }

    /**
     * Parent locations with their sublocations, including archived ones so
     * historical orders can still be filtered.
     *
     * @return list<array{id: int, name: string, archived: bool, children: list<array{id: int, name: string, archived: bool}>}>
     */
    private function locations(User $user): array
    {
        if (! $user->company_id) {
            return [];
        }

        $locations = Location::query()->withTrashed()
            ->where('company_id', $user->company_id)
            ->orderBy('name')
            ->get();

        $node = fn (Location $location) => ['id' => $location->id, 'name' => $location->name, 'archived' => $location->trashed()];

        return $locations->whereNull('parent_id')->values()->map(fn (Location $parent) => [
            ...$node($parent),
            'children' => $locations->where('parent_id', $parent->id)->values()->map($node)->all(),
        ])->all();
    }
}
