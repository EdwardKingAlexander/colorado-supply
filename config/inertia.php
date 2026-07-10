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
    | `connect_timeout`/`request_timeout` are app-specific additions consumed
    | by App\Inertia\Ssr\FastHttpGateway (bound in AppServiceProvider) to
    | apply aggressive timeouts on top of the stock gateway.
    |
    */

    'ssr' => [
        'enabled' => (bool) env('INERTIA_SSR_ENABLED', true),
        'runtime' => env('INERTIA_SSR_RUNTIME', 'node'),
        'ensure_runtime_exists' => (bool) env('INERTIA_SSR_ENSURE_RUNTIME_EXISTS', false),
        'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),
        'ensure_bundle_exists' => (bool) env('INERTIA_SSR_ENSURE_BUNDLE_EXISTS', true),
        'bundle' => env('INERTIA_SSR_BUNDLE', base_path('bootstrap/ssr/ssr.js')),
        'throw_on_error' => (bool) env('INERTIA_SSR_THROW_ON_ERROR', false),
        'connect_timeout' => (float) env('INERTIA_SSR_CONNECT_TIMEOUT', 1.5),
        'request_timeout' => (float) env('INERTIA_SSR_REQUEST_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | Set `ensure_pages_exist` to true if you want to enforce that Inertia
    | page components exist on disk when rendering a page. The `paths` and
    | `extensions` options define where to look for page components and
    | which file extensions to consider.
    |
    */

    'pages' => [
        'ensure_pages_exist' => false,

        'paths' => [
            resource_path('js/Pages'),
        ],

        'extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    |
    | When using `assertInertia`, the assertion attempts to locate the
    | component as a file relative to `pages.paths` with any of the
    | `pages.extensions` specified above.
    |
    */

    'testing' => [
        'ensure_pages_exist' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Expose Shared Prop Keys
    |--------------------------------------------------------------------------
    |
    | When enabled, each page response includes a `sharedProps` metadata key
    | listing the top-level prop keys that were registered via
    | `Inertia::share`. The frontend can use this to carry shared props over
    | during instant visits.
    |
    */

    'expose_shared_prop_keys' => true,

    'history' => [
        'encrypt' => (bool) env('INERTIA_ENCRYPT_HISTORY', false),
    ],

];
