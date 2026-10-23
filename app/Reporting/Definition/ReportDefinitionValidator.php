<?php

namespace App\Reporting\Definition;

use App\Reporting\DatasetRegistry;
use App\Reporting\Datasets\Dataset;
use App\Reporting\DateBucket;
use App\Reporting\Fields\Field;
use App\Reporting\FieldType;
use App\Reporting\Limits;
use App\Reporting\Operator;
use App\Reporting\RelativeDate;

/**
 * Checks a raw definition against the dataset's field catalog. Everything a
 * definition can name (dataset, field, operator, bucket, sort column) must be
 * on a whitelist here; values are type-checked before they are ever bound.
 */
class ReportDefinitionValidator
{
    /** @var array<string, string> */
    private array $errors = [];

    private int $ruleCount = 0;

    public function __construct(private DatasetRegistry $registry) {}

    /**
     * @throws InvalidReportDefinition
     */
    public function validated(mixed $input): ReportDefinition
    {
        $errors = $this->validate($input);

        if ($errors !== []) {
            throw new InvalidReportDefinition($errors);
        }

        return ReportDefinition::fromArray($input);
    }

    /**
     * @return array<string, string> JSON path => message
     */
    public function validate(mixed $input): array
    {
        $this->errors = [];
        $this->ruleCount = 0;

        if (! is_array($input)) {
            return ['definition' => 'The report definition must be an object.'];
        }

        if (isset($input['version']) && $input['version'] !== ReportDefinition::VERSION) {
            $this->fail('version', 'This report was saved in an unsupported format version.');
        }

        $dataset = is_string($input['dataset'] ?? null) ? $this->registry->find($input['dataset']) : null;

        if ($dataset === null) {
            return ['dataset' => 'Choose a data source.'] + $this->errors;
        }

        $mode = $input['mode'] ?? 'table';
        if (! in_array($mode, ReportDefinition::MODES, true)) {
            $this->fail('mode', 'This report type is not available.');
        }

        $keys = $this->validateColumns($dataset, $input['columns'] ?? null);

        if (isset($input['filters'])) {
            $this->validateGroup($dataset, $input['filters'], 'filters', 1);
        }

        $this->validateSort($input['sort'] ?? [], $keys);
        $this->validateLimit($input['limit'] ?? null);

        return $this->errors;
    }

    /**
     * @return list<string> the column keys, for sort validation
     */
    private function validateColumns(Dataset $dataset, mixed $columns): array
    {
        if (! is_array($columns) || ! array_is_list($columns) || $columns === []) {
            $this->fail('columns', 'Add at least one column.');

            return [];
        }

        if (count($columns) > Limits::MAX_COLUMNS) {
            $this->fail('columns', 'A report can have at most '.Limits::MAX_COLUMNS.' columns.');

            return [];
        }

        $keys = ColumnSpec::keysFor($columns);

        foreach ($columns as $index => $column) {
            $path = "columns.{$index}";

            if (! is_array($column)) {
                $this->fail($path, 'Each column must be an object.');

                continue;
            }

            $field = is_string($column['field'] ?? null) ? $dataset->field($column['field']) : null;

            if ($field === null || ! $field->selectable) {
                $this->fail("{$path}.field", 'Unknown field.');

                continue;
            }

            if (isset($column['key']) && (! is_string($column['key']) || ! preg_match(ColumnSpec::KEY_PATTERN, $column['key']))) {
                $this->fail("{$path}.key", 'Column keys must be lowercase letters, digits and underscores, starting with a letter.');
            }

            if (isset($column['label']) && (! is_string($column['label']) || mb_strlen($column['label']) > 120)) {
                $this->fail("{$path}.label", 'Column labels must be text of at most 120 characters.');
            }

            if (isset($column['bucket'])) {
                if (! is_string($column['bucket']) || DateBucket::tryFrom($column['bucket']) === null) {
                    $this->fail("{$path}.bucket", 'Unknown date grouping.');
                } elseif (! $field->type->isTemporal()) {
                    $this->fail("{$path}.bucket", 'Only date fields can be grouped by period.');
                }
            }

            if (isset($column['format']) && ! in_array($column['format'], ColumnSpec::FORMATS, true)) {
                $this->fail("{$path}.format", 'Unknown format.');
            }

            if (isset($column['aggregate'])) {
                $this->fail("{$path}.aggregate", 'Totals are not available in table reports.');
            }
        }

        if (count(array_unique($keys)) !== count($keys)) {
            $this->fail('columns', 'Column keys must be unique.');
        }

        return $keys;
    }

    private function validateGroup(Dataset $dataset, mixed $group, string $path, int $depth): void
    {
        if (! is_array($group)) {
            $this->fail($path, 'Filters must be a group of rules.');

            return;
        }

        if ($depth > Limits::MAX_FILTER_DEPTH) {
            $this->fail($path, 'Filter groups can be nested at most '.Limits::MAX_FILTER_DEPTH.' levels deep.');

            return;
        }

        if (! in_array($group['logic'] ?? 'and', ['and', 'or'], true)) {
            $this->fail("{$path}.logic", 'Filter groups match all (and) or any (or) of their rules.');
        }

        $rules = $group['rules'] ?? [];

        if (! is_array($rules) || ! array_is_list($rules)) {
            $this->fail("{$path}.rules", 'Filter rules must be a list.');

            return;
        }

        foreach ($rules as $index => $rule) {
            $rulePath = "{$path}.rules.{$index}";

            if (is_array($rule) && array_key_exists('rules', $rule)) {
                $this->validateGroup($dataset, $rule, $rulePath, $depth + 1);

                continue;
            }

            if (++$this->ruleCount > Limits::MAX_FILTER_RULES) {
                $this->fail('filters', 'A report can have at most '.Limits::MAX_FILTER_RULES.' filter rules.');

                return;
            }

            $this->validateRule($dataset, $rule, $rulePath);
        }
    }

    private function validateRule(Dataset $dataset, mixed $rule, string $path): void
    {
        if (! is_array($rule)) {
            $this->fail($path, 'Each filter must be an object.');

            return;
        }

        $field = is_string($rule['field'] ?? null) ? $dataset->field($rule['field']) : null;

        if ($field === null || ! $field->filterable) {
            $this->fail("{$path}.field", 'Unknown field.');

            return;
        }

        $operator = is_string($rule['op'] ?? null) ? Operator::tryFrom($rule['op']) : null;

        if ($operator === null || ! $field->allows($operator)) {
            $this->fail("{$path}.op", "That comparison isn't available for {$field->label}.");

            return;
        }

        $message = $this->valueError($field, $operator, $rule['value'] ?? null);

        if ($message !== null) {
            $this->fail("{$path}.value", $message);
        }
    }

    private function valueError(Field $field, Operator $operator, mixed $value): ?string
    {
        switch ($operator->valueShape()) {
            case 'none':
                return null;

            case 'relative':
                return is_string($value) && RelativeDate::tryFrom($value) !== null ? null : 'Choose a date range.';

            case 'list':
                if (! is_array($value) || ! array_is_list($value) || $value === []) {
                    return 'Choose at least one value.';
                }

                if (count($value) > Limits::MAX_LIST_VALUES) {
                    return 'Choose at most '.Limits::MAX_LIST_VALUES.' values.';
                }

                foreach ($value as $item) {
                    if (($error = $this->scalarError($field, $operator, $item)) !== null) {
                        return $error;
                    }
                }

                return null;

            case 'pair':
                if (! is_array($value) || ! array_is_list($value) || count($value) !== 2) {
                    return 'Enter a start and an end.';
                }

                foreach ($value as $item) {
                    if (($error = $this->scalarError($field, $operator, $item)) !== null) {
                        return $error;
                    }
                }

                $outOfOrder = $field->type->isTemporal()
                    ? DateValue::parse($value[0])->start()->gt(DateValue::parse($value[1])->end())
                    : (float) $value[0] > (float) $value[1];

                return $outOfOrder ? 'The start must come before the end.' : null;

            default:
                return $this->scalarError($field, $operator, $value);
        }
    }

    private function scalarError(Field $field, Operator $operator, mixed $value): ?string
    {
        return match ($field->type) {
            FieldType::String => match (true) {
                ! is_string($value) && ! is_int($value) && ! is_float($value) => 'Enter text.',
                mb_strlen((string) $value) > Limits::MAX_STRING_LENGTH => 'Text values can be at most '.Limits::MAX_STRING_LENGTH.' characters.',
                (string) $value === '' && in_array($operator, [Operator::Contains, Operator::StartsWith], true) => 'Enter some text.',
                default => null,
            },
            FieldType::Enum => (is_string($value) || is_int($value)) && array_key_exists((string) $value, $field->options)
                ? null : 'Choose one of the listed values.',
            FieldType::Number, FieldType::Money => (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value)))
                && is_finite((float) $value) ? null : 'Enter a number.',
            FieldType::Date, FieldType::DateTime => DateValue::parse($value) !== null ? null : 'Enter a date as YYYY-MM-DD.',
            FieldType::Location => (is_int($value) && $value > 0) || (is_string($value) && ctype_digit($value) && (int) $value > 0)
                ? null : 'Choose a location.',
            FieldType::Boolean => null,
        };
    }

    /**
     * @param  list<string>  $keys
     */
    private function validateSort(mixed $sort, array $keys): void
    {
        if (! is_array($sort) || ! array_is_list($sort)) {
            $this->fail('sort', 'Sort must be a list.');

            return;
        }

        if (count($sort) > Limits::MAX_SORTS) {
            $this->fail('sort', 'Sort by at most '.Limits::MAX_SORTS.' columns.');

            return;
        }

        foreach ($sort as $index => $item) {
            if (! is_array($item) || ! in_array($item['column'] ?? null, $keys, true)) {
                $this->fail("sort.{$index}.column", 'Sort by one of the report columns.');

                continue;
            }

            if (! in_array($item['dir'] ?? 'asc', ['asc', 'desc'], true)) {
                $this->fail("sort.{$index}.dir", 'Sort ascending or descending.');
            }
        }
    }

    private function validateLimit(mixed $limit): void
    {
        if ($limit !== null && (! is_int($limit) || $limit < 1 || $limit > Limits::MAX_ROW_LIMIT)) {
            $this->fail('limit', 'The row limit must be between 1 and '.number_format(Limits::MAX_ROW_LIMIT).'.');
        }
    }

    private function fail(string $path, string $message): void
    {
        $this->errors[$path] ??= $message;
    }
}
