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
}
