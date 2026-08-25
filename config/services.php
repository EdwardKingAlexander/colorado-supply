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
        // began returning an empty-bodied 404 on every path between 2026-06-12
        // and 2026-07-09 (GSA-side gateway fault, still unannounced). The
        // sam.gov/api/prod host serves the identical v2 payload and is what the
        // SAM.gov web UI itself consumes. Kept in config so we can switch back
        // the moment GSA restores api.sam.gov.
        'base_url' => env('SAM_API_BASE_URL', 'https://sam.gov/api/prod/opportunities/v2/search'),

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
