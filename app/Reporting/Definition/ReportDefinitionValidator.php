<?php

namespace App\Reporting\Definition;

use App\Reporting\Aggregate;
use App\Reporting\DatasetRegistry;
use App\Reporting\Datasets\Dataset;
use App\Reporting\DateBucket;
use App\Reporting\Fields\Field;
use App\Reporting\FieldType;
use App\Reporting\Formula\AggregateNode;
use App\Reporting\Formula\FormulaException;
use App\Reporting\Formula\Node;
use App\Reporting\Formula\Parser;
use App\Reporting\Formula\ReferenceNode;
use App\Reporting\Limits;
use App\Reporting\Operator;
use App\Reporting\RelativeDate;

/**
 * Checks a raw definition against the dataset's field catalog. Everything a
 * definition can name (dataset, field, operator, aggregate, function, bucket,
 * column key) must be on a whitelist here; values are type-checked before
 * they are ever bound.
 */
class ReportDefinitionValidator
{
    /** @var array<string, string> */
    private array $errors = [];

    private int $ruleCount = 0;

    private string $mode = 'table';

    /**
     * Every key a later section may refer to, with what it is.
     *
     * @var array<string, array{kind: 'dimension'|'measure'|'raw'|'calculated'|'post', numeric: bool}>
     */
    private array $keys = [];

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
        $this->keys = [];

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

        $this->mode = $input['mode'] ?? 'table';
        if (! in_array($this->mode, ReportDefinition::MODES, true)) {
            $this->fail('mode', 'Choose a table, summary or pivot report.');

            return $this->errors;
        }

        $this->validateColumns($dataset, $input['columns'] ?? null);
        $this->validateCalculated($dataset, $input['calculated'] ?? []);
        $this->validatePost($input['post'] ?? []);

        if (isset($input['filters'])) {
            $this->validateGroup($dataset, $input['filters'], 'filters', 1);
        }

        $this->validatePivot($input['pivot'] ?? null);
        $this->validateSort($input['sort'] ?? []);
        $this->validateLimit($input['limit'] ?? null);
        $this->validateTotals($input['totals'] ?? null);
        $this->validateFormatting($input['formatting'] ?? []);

        return $this->errors;
    }

    private function validateColumns(Dataset $dataset, mixed $columns): void
    {
        if (! is_array($columns) || ! array_is_list($columns) || $columns === []) {
            $this->fail('columns', 'Add at least one column.');

            return;
        }

        if (count($columns) > Limits::MAX_COLUMNS) {
            $this->fail('columns', 'A report can have at most '.Limits::MAX_COLUMNS.' columns.');

            return;
        }

        $keys = ColumnSpec::keysFor($columns);

        if (count(array_unique($keys)) !== count($keys)) {
            $this->fail('columns', 'Column keys must be unique.');
        }

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

            if (isset($column['format']) && ! in_array($column['format'], ColumnSpec::FORMATS, true)) {
                $this->fail("{$path}.format", 'Unknown format.');
            }

            $aggregate = $this->validateAggregate($field, $column, $path);

            if (isset($column['bucket'])) {
                if (! is_string($column['bucket']) || DateBucket::tryFrom($column['bucket']) === null) {
                    $this->fail("{$path}.bucket", 'Unknown date grouping.');
                } elseif (! $field->type->isTemporal()) {
                    $this->fail("{$path}.bucket", 'Only date fields can be grouped by period.');
                } elseif (isset($column['aggregate'])) {
                    $this->fail("{$path}.bucket", 'A column is either grouped by period or totalled, not both.');
                }
            }

            $resultType = $aggregate?->resultType($field->type) ?? (isset($column['bucket']) ? FieldType::String : $field->type);

            $this->keys[$keys[$index]] = [
                'kind' => $aggregate !== null ? 'measure' : ($this->mode === 'table' ? 'raw' : 'dimension'),
                'numeric' => $resultType->isNumeric(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $column
     */
    private function validateAggregate(Field $field, array $column, string $path): ?Aggregate
    {
        if (! isset($column['aggregate'])) {
            return null;
        }

        $aggregate = is_string($column['aggregate']) ? Aggregate::tryFrom($column['aggregate']) : null;

        if ($aggregate === null) {
            $this->fail("{$path}.aggregate", 'Unknown total.');

            return null;
        }

        if ($this->mode === 'table') {
            $this->fail("{$path}.aggregate", 'Totals need a summary or pivot report.');

            return null;
        }

        if (! $aggregate->allows($field->type)) {
            $this->fail("{$path}.aggregate", "{$field->label} can't be totalled that way.");

            return null;
        }

        return $aggregate;
    }

    private function validateCalculated(Dataset $dataset, mixed $calculated): void
    {
        if (! $this->isList($calculated, 'calculated', 'Calculated fields must be a list.', Limits::MAX_CALCULATED)) {
            return;
        }

        $calculatedKeys = [];

        // Register every key first so formulas can refer to later fields.
        foreach ($calculated as $index => $field) {
            $key = is_array($field) ? ($field['key'] ?? null) : null;

            if (! is_string($key) || ! preg_match(ColumnSpec::KEY_PATTERN, $key)) {
                $this->fail("calculated.{$index}.key", 'Give the field a key of lowercase letters, digits and underscores.');

                continue;
            }

            if (isset($this->keys[$key])) {
                $this->fail("calculated.{$index}.key", 'That key is already used by another column.');

                continue;
            }

            $this->keys[$key] = ['kind' => 'calculated', 'numeric' => true];
            $calculatedKeys[$index] = $key;
        }

        $dependencies = [];

        foreach ($calculatedKeys as $index => $key) {
            $field = $calculated[$index];
            $path = "calculated.{$index}";

            if (isset($field['label']) && (! is_string($field['label']) || mb_strlen($field['label']) > 120)) {
                $this->fail("{$path}.label", 'Labels must be text of at most 120 characters.');
            }

            if (isset($field['format']) && ! in_array($field['format'], ColumnSpec::FORMATS, true)) {
                $this->fail("{$path}.format", 'Unknown format.');
            }

            if (! is_string($field['formula'] ?? null)) {
                $this->fail("{$path}.formula", 'Enter a formula.');

                continue;
            }

            try {
                $ast = Parser::parse($field['formula']);
            } catch (FormulaException $exception) {
                $this->fail("{$path}.formula", $exception->describe().'.');

                continue;
            }

            $dependencies[$key] = [];
            $error = $this->formulaError($dataset, $ast, $key, $dependencies[$key]);

            if ($error !== null) {
                $this->fail("{$path}.formula", $error);
            }
        }

        if (($cycle = $this->findCycle($dependencies)) !== null) {
            $index = array_search($cycle[0], $calculatedKeys, true);
            $this->fail("calculated.{$index}.formula", 'These formulas refer to each other in a loop: '.implode(' → ', $cycle).'.');
        }
    }

    /**
     * @param  list<string>  $dependencies  calculated keys this formula uses (filled in)
     */
    private function formulaError(Dataset $dataset, Node $node, string $self, array &$dependencies): ?string
    {
        if ($node instanceof AggregateNode) {
            $field = $dataset->field($node->field);

            return match (true) {
                $this->mode === 'table' => "{$node->aggregate->formulaName()}() needs a summary or pivot report.",
                $field === null => "Unknown field {$node->field}.",
                ! $node->aggregate->allows($field->type) => "{$field->label} can't be used in {$node->aggregate->formulaName()}().",
                default => null,
            };
        }

        if ($node instanceof ReferenceNode) {
            if ($node->isField()) {
                $field = $dataset->field($node->name);

                return match (true) {
                    $field === null || ! $field->selectable => "Unknown field {$node->name}.",
                    $this->mode !== 'table' => "Wrap {$node->name} in SUM(), AVG(), COUNT() or another total.",
                    default => null,
                };
            }

            if ($node->name === $self) {
                return 'A formula cannot refer to itself.';
            }

            $kind = $this->keys[$node->name]['kind'] ?? null;

            if ($kind === null || $kind === 'post') {
                return "Unknown column {$node->name}.";
            }

            if ($kind === 'calculated') {
                $dependencies[] = $node->name;
            }

            return null;
        }

        foreach ($node->children() as $child) {
            if (($error = $this->formulaError($dataset, $child, $self, $dependencies)) !== null) {
                return $error;
            }
        }

        return null;
    }

    /**
     * @param  array<string, list<string>>  $graph
     * @return ?list<string>
     */
    private function findCycle(array $graph): ?array
    {
        $state = [];

        $visit = function (string $key, array $path) use (&$visit, &$state, $graph): ?array {
            if (($state[$key] ?? null) === 'done') {
                return null;
            }

            if (($state[$key] ?? null) === 'visiting') {
                return [...array_slice($path, (int) array_search($key, $path, true)), $key];
            }

            $state[$key] = 'visiting';

            foreach ($graph[$key] ?? [] as $next) {
                if (($cycle = $visit($next, [...$path, $key])) !== null) {
                    return $cycle;
                }
            }

            $state[$key] = 'done';

            return null;
        };

        foreach (array_keys($graph) as $key) {
            if (($cycle = $visit($key, [])) !== null) {
                return $cycle;
            }
        }

        return null;
    }

    private function validatePost(mixed $post): void
    {
        if (! $this->isList($post, 'post', 'Extra columns must be a list.', Limits::MAX_POST_PROCESSORS)) {
            return;
        }

        if ($post !== [] && $this->mode === 'pivot') {
            $this->fail('post', 'Running totals and % of total are not available in pivot reports.');

            return;
        }

        foreach ($post as $index => $item) {
            $path = "post.{$index}";
            $column = is_array($item) ? ($item['column'] ?? null) : null;

            if (! is_string($column) || ! ($this->keys[$column]['numeric'] ?? false) || $this->keys[$column]['kind'] === 'post') {
                $this->fail("{$path}.column", 'Choose a number column.');

                continue;
            }

            $operation = $item['op'] ?? null;

            if (! is_string($operation) || ! array_key_exists($operation, PostProcessorSpec::OPERATIONS)) {
                $this->fail("{$path}.op", 'Unknown calculation.');

                continue;
            }

            if (isset($item['label']) && (! is_string($item['label']) || mb_strlen($item['label']) > 120)) {
                $this->fail("{$path}.label", 'Labels must be text of at most 120 characters.');
            }

            $key = $item['key'] ?? PostProcessorSpec::defaultKey($column, $operation);

            if (! is_string($key) || ! preg_match(ColumnSpec::KEY_PATTERN, $key)) {
                $this->fail("{$path}.key", 'Keys must be lowercase letters, digits and underscores.');
            } elseif (isset($this->keys[$key])) {
                $this->fail("{$path}.key", 'That key is already used by another column.');
            } else {
                $this->keys[$key] = ['kind' => 'post', 'numeric' => true];
            }
        }
    }

    private function validatePivot(mixed $pivot): void
    {
        if ($this->mode !== 'pivot') {
            if ($pivot !== null) {
                $this->fail('pivot', 'Pivot settings need a pivot report.');
            }

            return;
        }

        if (! is_array($pivot)) {
            $this->fail('pivot', 'Choose the rows, the column and the values of the pivot.');

            return;
        }

        $dimensions = array_keys(array_filter($this->keys, fn (array $key) => $key['kind'] === 'dimension'));
        $rows = $pivot['rows'] ?? [];
        $column = $pivot['column'] ?? null;
        $values = $pivot['values'] ?? null;

        if (! is_array($rows) || ! array_is_list($rows) || count($rows) > Limits::MAX_PIVOT_ROW_FIELDS
            || array_diff($rows, $dimensions) !== [] || count(array_unique($rows)) !== count($rows)) {
            $this->fail('pivot.rows', 'Rows must be up to '.Limits::MAX_PIVOT_ROW_FIELDS.' grouping columns.');

            return;
        }

        if (! is_string($column) || ! in_array($column, $dimensions, true) || in_array($column, $rows, true)) {
            $this->fail('pivot.column', 'Choose one grouping column to spread across the top.');

            return;
        }

        if (array_diff($dimensions, [...$rows, $column]) !== []) {
            $this->fail('pivot.rows', 'Every grouping column must be a pivot row or the pivot column.');
        }

        $measures = array_keys(array_filter($this->keys, fn (array $key) => in_array($key['kind'], ['measure', 'calculated'], true)));

        if (! is_array($values) || ! array_is_list($values) || $values === [] || count($values) > Limits::MAX_PIVOT_VALUES
            || array_diff($values, $measures) !== [] || count(array_unique($values)) !== count($values)) {
            $this->fail('pivot.values', 'Choose 1 to '.Limits::MAX_PIVOT_VALUES.' totalled or calculated columns for the cells.');
        }
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
            FieldType::Number, FieldType::Money => $this->isNumber($value) ? null : 'Enter a number.',
            FieldType::Date, FieldType::DateTime => DateValue::parse($value) !== null ? null : 'Enter a date as YYYY-MM-DD.',
            FieldType::Location => (is_int($value) && $value > 0) || (is_string($value) && ctype_digit($value) && (int) $value > 0)
                ? null : 'Choose a location.',
            FieldType::Boolean, FieldType::Id => null,
        };
    }

    private function validateSort(mixed $sort): void
    {
        if (! $this->isList($sort, 'sort', 'Sort must be a list.', Limits::MAX_SORTS)) {
            return;
        }

        $sortable = array_keys(array_filter($this->keys, fn (array $key) => $this->mode === 'pivot'
            ? $key['kind'] !== 'post'
            : in_array($key['kind'], ['dimension', 'measure', 'raw', 'calculated'], true)));

        foreach ($sort as $index => $item) {
            if (! is_array($item) || ! in_array($item['column'] ?? null, $sortable, true)) {
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
        $max = $this->mode === 'table' ? Limits::MAX_ROW_LIMIT : Limits::MAX_GROUPS;

        if ($limit !== null && (! is_int($limit) || $limit < 1 || $limit > $max)) {
            $this->fail('limit', 'The row limit must be between 1 and '.number_format($max).'.');
        }
    }

    private function validateTotals(mixed $totals): void
    {
        if ($totals === null) {
            return;
        }

        if (! is_array($totals) || array_diff(array_keys($totals), ['grand', 'subtotals']) !== []
            || array_filter($totals, fn ($value) => ! is_bool($value)) !== []) {
            $this->fail('totals', 'Totals settings must be yes/no switches for grand totals and subtotals.');

            return;
        }

        if (($totals['subtotals'] ?? false) === true) {
            $dimensions = array_filter($this->keys, fn (array $key) => $key['kind'] === 'dimension');

            if ($this->mode !== 'summary' || count($dimensions) < 2) {
                $this->fail('totals.subtotals', 'Subtotals need a summary report with at least two grouping columns.');
            }
        }
    }

    private function validateFormatting(mixed $formatting): void
    {
        if (! $this->isList($formatting, 'formatting', 'Formatting rules must be a list.', Limits::MAX_FORMAT_RULES)) {
            return;
        }

        foreach ($formatting as $index => $rule) {
            $path = "formatting.{$index}";
            $column = is_array($rule) ? ($rule['column'] ?? null) : null;

            if (! is_string($column) || ! isset($this->keys[$column])) {
                $this->fail("{$path}.column", 'Choose one of the report columns.');

                continue;
            }

            if (! in_array($rule['style'] ?? null, FormatRule::STYLES, true)) {
                $this->fail("{$path}.style", 'Choose a style.');
            }

            $operator = $rule['when']['op'] ?? null;
            $value = $rule['when']['value'] ?? null;

            if (! in_array($operator, FormatRule::OPERATORS, true)) {
                $this->fail("{$path}.when.op", 'Choose a condition.');

                continue;
            }

            if (in_array($operator, FormatRule::NUMERIC_OPERATORS, true) && ! $this->keys[$column]['numeric']) {
                $this->fail("{$path}.when.op", 'That condition needs a number column.');

                continue;
            }

            $error = match ($operator) {
                'negative', 'is_null' => null,
                'top_n', 'bottom_n' => is_int($value) && $value >= 1 && $value <= Limits::MAX_TOP_N ? null : 'Enter how many rows, from 1 to '.Limits::MAX_TOP_N.'.',
                'between' => is_array($value) && array_is_list($value) && count($value) === 2 && $this->isNumber($value[0]) && $this->isNumber($value[1])
                    ? null : 'Enter a lower and an upper number.',
                'eq', 'neq' => is_string($value) || $this->isNumber($value) ? null : 'Enter a value.',
                default => $this->isNumber($value) ? null : 'Enter a number.',
            };

            if ($error !== null) {
                $this->fail("{$path}.when.value", $error);
            }
        }
    }

    private function isList(mixed $value, string $path, string $message, int $max): bool
    {
        if (! is_array($value) || ! array_is_list($value)) {
            $this->fail($path, $message);

            return false;
        }

        if (count($value) > $max) {
            $this->fail($path, "At most {$max} allowed.");

            return false;
        }

        return true;
    }

    private function isNumber(mixed $value): bool
    {
        return (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) && is_finite((float) $value);
    }

    private function fail(string $path, string $message): void
    {
        $this->errors[$path] ??= $message;
    }
}
