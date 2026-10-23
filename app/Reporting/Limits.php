<?php

namespace App\Reporting;

/**
 * Hard caps on report definitions (see the module overview's "Hard limits").
 */
final class Limits
{
    public const MAX_COLUMNS = 40;

    public const MAX_FILTER_DEPTH = 3;

    public const MAX_FILTER_RULES = 100;

    public const MAX_LIST_VALUES = 200;

    public const MAX_STRING_LENGTH = 255;

    public const MAX_SORTS = 5;

    public const DEFAULT_ROW_LIMIT = 1000;

    public const MAX_ROW_LIMIT = 50000;

    public const PREVIEW_ROWS = 500;

    /** Summary and pivot reports: most groups (pivot: row groups) returned. */
    public const MAX_GROUPS = 10000;

    /** Pivot reports: most row × column cells fetched. */
    public const MAX_PIVOT_CELLS = 50000;

    /** Pivot reports: distinct column values before the rest roll into "Other". */
    public const MAX_PIVOT_COLUMNS = 50;

    public const MAX_PIVOT_ROW_FIELDS = 3;

    public const MAX_PIVOT_VALUES = 10;

    public const MAX_CALCULATED = 20;

    public const MAX_POST_PROCESSORS = 10;

    public const MAX_FORMAT_RULES = 20;

    public const MAX_TOP_N = 1000;
}
