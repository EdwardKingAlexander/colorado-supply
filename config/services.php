<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'recaptcha' => [
            'site_key' => env('GOOGLE_RECAPTCHA_SITE_KEY'),
            'secret_key' => env('GOOGLE_RECAPTCHA_SECRET_KEY'),
            'score_threshold' => (float) env('GOOGLE_RECAPTCHA_SCORE_THRESHOLD', 0.5),
        ],

        'places' => [
            'api_key' => env('GOOGLE_PLACES_API_KEY'),
        ],
    ],

    'sam' => [
        'api_key' => env('SAM_API_KEY'),

        // The documented public host, https://api.sam.gov/opportunities/v2/search,
        // began returning an empty-bodied 404 between 2026-06-12 and 2026-07-09.
        // Most likely not an outage: api.sam.gov still answers old bare-UUID
        // keys that way, and sam.gov/api/prod later began rejecting them too
        // (400 "Validation failure", 2026-11-13). With a new "SAM-" key both hosts serve the identical v2
        // payload (verified 2026-11-13, see ai/modules/sam-api-key-format-fix-complete).
        // sam.gov/api/prod stays the default; set SAM_API_BASE_URL to switch.
        'base_url' => env('SAM_API_BASE_URL', 'https://sam.gov/api/prod/opportunities/v2/search'),

        // Entity Management API (CAGE lookups). Same host as base_url.
        'entity_url' => env('SAM_ENTITY_API_URL', 'https://sam.gov/api/prod/entity-information/v3/entities'),

        'timeout' => (int) env('SAM_API_TIMEOUT', 30),

        // Cap on paginated requests per NAICS code (page size is 1000).
        'max_pages' => (int) env('SAM_API_MAX_PAGES', 10),

        // Where fetch-failure alerts go. Falls back to the Business Hub address
        // so alerting works without additional configuration.
        'alert_email' => env('SAM_ALERT_EMAIL', env('BUSINESS_HUB_NOTIFICATION_EMAIL')),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    'python' => [
        // Path to Python executable. Uses venv if available, otherwise system python.
        'path' => env('PYTHON_PATH', base_path('.venv/Scripts/python')),
    ],

];
