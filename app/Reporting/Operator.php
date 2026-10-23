<?php

namespace App\Reporting;

enum Operator: string
{
    case Equals = 'eq';
    case NotEquals = 'neq';
    case In = 'in';
    case NotIn = 'not_in';
    case GreaterThan = 'gt';
    case GreaterThanOrEqual = 'gte';
    case LessThan = 'lt';
    case LessThanOrEqual = 'lte';
    case Between = 'between';
    case Contains = 'contains';
    case StartsWith = 'starts_with';
    case IsNull = 'is_null';
    case NotNull = 'not_null';
    case Relative = 'relative';
    case IsTrue = 'is_true';
    case IsFalse = 'is_false';

    /**
     * What the rule's `value` must look like: nothing, one scalar, a list, a
     * [min, max] pair, or a relative-date preset key.
     */
    public function valueShape(): string
    {
        return match ($this) {
            self::IsNull, self::NotNull, self::IsTrue, self::IsFalse => 'none',
            self::In, self::NotIn => 'list',
            self::Between => 'pair',
            self::Relative => 'relative',
            default => 'single',
        };
    }
}
