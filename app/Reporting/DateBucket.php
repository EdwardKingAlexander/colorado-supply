<?php

namespace App\Reporting;

enum DateBucket: string
{
    case Day = 'day';

    /** Keyed by the Monday the (ISO) week starts on, e.g. 2025-12-29. */
    case Week = 'week';

    case Month = 'month';
    case Quarter = 'quarter';
    case Year = 'year';
}
