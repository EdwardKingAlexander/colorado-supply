<?php

namespace App\Support;

/**
 * Locations of the shared SAM state files (see config sam_opportunities.state_directory).
 */
class SamStatePath
{
    public static function directory(): string
    {
        return rtrim(config('sam_opportunities.state_directory', app_path('Mcp/Servers/Business/State')), '/\\');
    }

    /**
     * Latest fetch result, read by the control panel and the Excel export.
     */
    public static function opportunities(): string
    {
        return self::directory().DIRECTORY_SEPARATOR.'sam-opportunities.json';
    }

    public static function complianceReport(): string
    {
        return self::directory().DIRECTORY_SEPARATOR.'compliance-report.json';
    }
}
