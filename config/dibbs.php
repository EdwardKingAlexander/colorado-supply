<?php

/*
|--------------------------------------------------------------------------
| DIBBS (DLA Internet Bid Board System) RFQ ingestion
|--------------------------------------------------------------------------
|
| See ai/modules/dibbs-rfq-tracking (findings.md for the verified access
| contract). DLA publishes daily RFQ files for vendors to download; the app
| downloads them politely, never stores DIBBS credentials, and never submits
| quotes.
|
*/

return [

    // Turns the scheduled download on/off. Manual upload always works.
    'enabled' => (bool) env('DIBBS_ENABLED', true),

    // The RFQ listing ("Recent RFQs") lives on www; the files on dibbs2.
    'listing_url' => env('DIBBS_LISTING_URL', 'https://www.dibbs.bsm.dla.mil/RFQ/RFQDates.aspx?category=recent'),
    'files_base_url' => env('DIBBS_FILES_BASE_URL', 'https://dibbs2.bsm.dla.mil/Downloads/RFQ/Archive/'),

    // Honest identification. Never a browser User-Agent. Add a contact
    // address, e.g. "ColoradoSupplyRfqTracker/1.0 (+ops@example.com)".
    'user_agent' => env('DIBBS_USER_AGENT', 'ColoradoSupplyRfqTracker/1.0'),

    // Seconds between requests to DIBBS (politeness). 0 in tests.
    'request_interval_seconds' => (int) env('DIBBS_REQUEST_INTERVAL_SECONDS', 2),

    'timeout_seconds' => (int) env('DIBBS_TIMEOUT_SECONDS', 60),

    // How many of the most recent listed posting days the job will catch up on.
    'backfill_days' => (int) env('DIBBS_BACKFILL_DAYS', 5),

    // Where downloaded/uploaded raw files are kept (local disk, private).
    'storage_directory' => 'dibbs',

    // Ingestion failure alerts; falls back to the SAM alert address.
    'alert_email' => env('DIBBS_ALERT_EMAIL', env('SAM_ALERT_EMAIL', env('BUSINESS_HUB_NOTIFICATION_EMAIL'))),

    /*
    | Retention (Phase 7, `php artisan dibbs:prune`, weekly). RFQs anyone acted
    | on, or that match the catalog, are never pruned.
    */
    'retention' => [
        'closed_rfq_days' => (int) env('DIBBS_RETENTION_CLOSED_RFQ_DAYS', 180),
        'raw_file_days' => (int) env('DIBBS_RETENTION_RAW_FILE_DAYS', 60),
    ],

    /*
    | Morning digest (Phase 5): new RFQs that fit us, and RFQs closing soon that
    | nobody has reviewed. Sent after the daily download imports a new file,
    | with a fallback run later in the morning; at most once per day.
    */
    'digest' => [
        'enabled' => (bool) env('DIBBS_DIGEST_ENABLED', true),

        // Comma-separated; falls back to the alert address.
        'recipients' => array_values(array_filter(array_map('trim', explode(',', (string) env('DIBBS_DIGEST_EMAILS', ''))))),

        // Weakest signal that earns a place in the digest:
        // 'product_codes' (any RFQ in our PSC lists), 'approved_source', or 'catalog'.
        'min_match' => env('DIBBS_DIGEST_MIN_MATCH', 'product_codes'),

        // RFQs listed per section before "and N more".
        'section_limit' => (int) env('DIBBS_DIGEST_SECTION_LIMIT', 25),

        // "Closing soon" window, in days.
        'closing_within_days' => (int) env('DIBBS_DIGEST_CLOSING_DAYS', 3),
    ],

];
