<?php

namespace App\Support;

use App\Models\McpSetting;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class McpSettings
{
    /**
     * Retrieve a decrypted setting payload.
     */
    public static function for(string $key, array $default = []): array
    {
        if (! Schema::hasTable('mcp_settings')) {
            return $default;
        }

        try {
            $setting = McpSetting::query()->firstWhere('key', $key);
        } catch (\Throwable $e) {
            report($e);

            return $default;
        }

        return $setting ? (array) $setting->value : $default;
    }

    /**
     * Persist the given payload, keeping descriptions optional.
     */
    public static function put(string $key, array $value, ?string $description = null): McpSetting
    {
        if (! Schema::hasTable('mcp_settings')) {
            throw new RuntimeException('The MCP settings table is missing. Run php artisan migrate.');
        }

        return McpSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
            ]
        );
    }
}
