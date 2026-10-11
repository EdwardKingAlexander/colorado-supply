<?php

return [
    // Database connection for the catalog tables; null uses the app default.
    // Set to 'publog' to keep the multi-GB catalog in its own MySQL database.
    'connection' => env('PUBLOG_DB_CONNECTION'),
    'directory' => storage_path('app/private/publog'),
    // Status and replacement history (V_FLIS_STANDARDIZATION, V_FLIS_CANCELLED_NIIN):
    // ~6 GB per release and only shown in a collapsed lookup section. Off keeps
    // production at one ~14 GB copy (phase 5).
    'import_relationships' => (bool) env('PUBLOG_IMPORT_RELATIONSHIPS', false),
    // Skip MySQL's binary log for catalog import/swap sessions (see PubLogDb).
    'skip_binlog' => (bool) env('PUBLOG_SKIP_BINLOG', false),
    'batch_size' => 500,
    'max_archive_bytes' => 1073741824,
    'max_member_bytes' => 2147483648,
    'max_total_bytes' => 8589934592,
    'minimum_free_bytes' => 2147483648,
    'max_release_spread_days' => 7,
    'minimum_previous_count_ratio' => 0.8,
    'max_orphan_references' => 0,
    'stale_after_days' => 45,
    // Checking metadata never imports or activates a release.
    'check_enabled' => (bool) env('PUBLOG_CHECK_ENABLED', false),
    'request_timeout' => 10,
    'user_agent' => 'ColoradoSupplyPubLog/1.0',
    'source_base_url' => 'https://www.dla.mil/Portals/104/Documents/InformationOperations/LogisticsInformationServices/FOIA/PUBLOG/',
];
