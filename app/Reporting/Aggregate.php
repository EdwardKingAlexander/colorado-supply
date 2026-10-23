<?php

namespace App\Reporting;

enum Aggregate: string
{
    case Sum = 'sum';
    case Avg = 'avg';
    case Min = 'min';
    case Max = 'max';
    case Count = 'count';
    case CountDistinct = 'count_distinct';

    /** Booleans: how many rows are true. */
    case CountTrue = 'count_true';

    /**
     * @return list<self>
     */
    public static function allowedFor(FieldType $type): array
    {
        return match ($type) {
            FieldType::Number, FieldType::Money => [self::Sum, self::Avg, self::Min, self::Max, self::Count, self::CountDistinct],
            FieldType::Date, FieldType::DateTime => [self::Min, self::Max, self::Count, self::CountDistinct],
            FieldType::String, FieldType::Enum, FieldType::Id => [self::Count, self::CountDistinct],
            FieldType::Boolean => [self::CountTrue, self::Count],
            FieldType::Location => [],
        };
    }

    public function allows(FieldType $type): bool
    {
        return in_array($this, self::allowedFor($type), true);
    }

    public function sql(string $expression): string
    {
        return match ($this) {
            self::Sum => "SUM({$expression})",
            self::Avg => "AVG({$expression})",
            self::Min => "MIN({$expression})",
            self::Max => "MAX({$expression})",
            self::Count => "COUNT({$expression})",
            self::CountDistinct => "COUNT(DISTINCT {$expression})",
            self::CountTrue => "SUM(CASE WHEN COALESCE({$expression}, 0) = 1 THEN 1 ELSE 0 END)",
        };
    }

    public function resultType(FieldType $type): FieldType
    {
        return match ($this) {
            self::Count, self::CountDistinct, self::CountTrue => FieldType::Number,
            default => $type,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Sum => 'Total',
            self::Avg => 'Average',
            self::Min => 'Lowest',
            self::Max => 'Highest',
            self::Count => 'Count',
            self::CountDistinct => 'Unique count',
            self::CountTrue => 'Count of yes',
        };
    }

    /**
     * The function name used for this aggregate in formulas, e.g. SUM.
     */
    public function formulaName(): string
    {
        return strtoupper($this->value);
    }

    public static function fromFormulaName(string $name): ?self
    {
        return self::tryFrom(strtolower($name));
    }
}
