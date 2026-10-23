<?php

namespace App\Reporting\Fields;

use App\Reporting\FieldType;
use App\Reporting\Operator;
use App\Reporting\Sql\SqlDialect;
use BackedEnum;
use Closure;

/**
 * One reportable field. The SQL expression is fixed server-side and never
 * comes from a report definition; definitions only name the field by key.
 */
final class Field
{
    /**
     * @param  string|Closure(SqlDialect): string  $expression  SQL over the dataset's join aliases
     * @param  list<string>  $joins  join keys the expression needs
     * @param  array<string, string>  $options  enum value => label
     * @param  ?string  $parentExpression  Location fields: the matched location's parent id column
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $group,
        public readonly FieldType $type,
        private readonly string|Closure $expression,
        public readonly array $joins = [],
        public readonly array $options = [],
        public readonly ?string $parentExpression = null,
        public readonly bool $selectable = true,
        public readonly bool $filterable = true,
    ) {}

    public function sql(SqlDialect $dialect): string
    {
        return $this->expression instanceof Closure
            ? ($this->expression)($dialect)
            : $this->expression;
    }

    public function allows(Operator $operator): bool
    {
        return $this->type->allows($operator);
    }

    /**
     * @param  class-string<BackedEnum>  $enum  an enum with a label() method
     * @return array<string, string>
     */
    public static function enumOptions(string $enum): array
    {
        $options = [];

        foreach ($enum::cases() as $case) {
            $options[(string) $case->value] = $case->label();
        }

        return $options;
    }
}
