<?php

namespace App\Reporting;

use Carbon\CarbonImmutable;

enum RelativeDate: string
{
    case Today = 'today';
    case Yesterday = 'yesterday';
    case Last7Days = 'last_7_days';
    case Last30Days = 'last_30_days';
    case Last90Days = 'last_90_days';
    case ThisMonth = 'this_month';
    case LastMonth = 'last_month';
    case ThisQuarter = 'this_quarter';
    case LastQuarter = 'last_quarter';
    case ThisYear = 'this_year';
    case LastYear = 'last_year';
    case YearToDate = 'year_to_date';

    /**
     * Inclusive start and end of the range, in the app timezone. "Last N days"
     * includes today.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $today = $now->startOfDay();

        return match ($this) {
            self::Today => [$today, $now->endOfDay()],
            self::Yesterday => [$today->subDay(), $today->subDay()->endOfDay()],
            self::Last7Days => [$today->subDays(6), $now->endOfDay()],
            self::Last30Days => [$today->subDays(29), $now->endOfDay()],
            self::Last90Days => [$today->subDays(89), $now->endOfDay()],
            self::ThisMonth => [$now->startOfMonth(), $now->endOfMonth()],
            self::LastMonth => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            self::ThisQuarter => [$now->startOfQuarter(), $now->endOfQuarter()],
            self::LastQuarter => [$now->startOfQuarter()->subQuarter()->startOfQuarter(), $now->startOfQuarter()->subQuarter()->endOfQuarter()],
            self::ThisYear => [$now->startOfYear(), $now->endOfYear()],
            self::LastYear => [$now->subYear()->startOfYear(), $now->subYear()->endOfYear()],
            self::YearToDate => [$now->startOfYear(), $now->endOfDay()],
        };
    }
}
