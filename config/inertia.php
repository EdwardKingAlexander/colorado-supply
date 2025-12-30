<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    |
    | Configure if and how Inertia should use Server Side Rendering (SSR).
    | A separate rendering service must be running before enabling SSR,
    | otherwise HTTP requests to the SSR gateway will hang.
    |
    */

    'ssr' => [
        'enabled' => (bool) env('INERTIA_SSR_ENABLED', true),
        'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),
        'ensure_bundle_exists' => (bool) env('INERTIA_SSR_ENSURE_BUNDLE_EXISTS', true),
        'bundle' => env('INERTIA_SSR_BUNDLE', base_path('bootstrap/ssr/ssr.mjs')),
        'connect_timeout' => (float) env('INERTIA_SSR_CONNECT_TIMEOUT', 1.5),
        'request_timeout' => (float) env('INERTIA_SSR_REQUEST_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | Ensure that page components exist on disk, and configure where Inertia
    | should search for them along with the supported file extensions.
    |
    */

    'ensure_pages_exist' => false,

    'page_paths' => [
        resource_path('js/Pages'),
    ],

    'page_extensions' => [
        'js',
        'jsx',
        'svelte',
        'ts',
        'tsx',
        'vue',
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    |
    | When running tests we typically want to ensure that Inertia components
    | exist on disk. These settings mirror the page configuration above.
    |
    */

    'testing' => [
        'ensure_pages_exist' => true,

        'page_paths' => [
            resource_path('js/Pages'),
        ],

        'page_extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],
    ],

    'history' => [
        'encrypt' => (bool) env('INERTIA_ENCRYPT_HISTORY', false),
    ],

];
