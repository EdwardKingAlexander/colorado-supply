<?php

namespace App\Reporting;

enum FieldType: string
{
    case String = 'string';
    case Enum = 'enum';
    case Number = 'number';
    case Money = 'money';
    case Date = 'date';
    case DateTime = 'datetime';
    case Boolean = 'boolean';

    /**
     * A location id filter that matches the location itself or any of its
     * sublocations. Filter-only: names are exposed as separate string fields.
     */
    case Location = 'location';

    /**
     * @return list<Operator>
     */
    public function operators(): array
    {
        return match ($this) {
            self::String => [
                Operator::Equals, Operator::NotEquals, Operator::In, Operator::NotIn,
                Operator::Contains, Operator::StartsWith, Operator::IsNull, Operator::NotNull,
            ],
            self::Enum => [
                Operator::Equals, Operator::NotEquals, Operator::In, Operator::NotIn,
                Operator::IsNull, Operator::NotNull,
            ],
            self::Number, self::Money => [
                Operator::Equals, Operator::NotEquals, Operator::GreaterThan, Operator::GreaterThanOrEqual,
                Operator::LessThan, Operator::LessThanOrEqual, Operator::Between,
                Operator::IsNull, Operator::NotNull,
            ],
            self::Date, self::DateTime => [
                Operator::Equals, Operator::GreaterThan, Operator::GreaterThanOrEqual,
                Operator::LessThan, Operator::LessThanOrEqual, Operator::Between, Operator::Relative,
                Operator::IsNull, Operator::NotNull,
            ],
            self::Boolean => [Operator::IsTrue, Operator::IsFalse],
            self::Location => [Operator::In, Operator::NotIn, Operator::IsNull, Operator::NotNull],
        };
    }

    public function allows(Operator $operator): bool
    {
        return in_array($operator, $this->operators(), true);
    }

    public function isTemporal(): bool
    {
        return $this === self::Date || $this === self::DateTime;
    }

    public function isNumeric(): bool
    {
        return $this === self::Number || $this === self::Money;
    }
}
