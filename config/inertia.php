<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Inertia SSR
    |--------------------------------------------------------------------------
    |
    | Here you can configure the server-side rendering (SSR) settings for
    | Inertia. Make sure your SSR bundle exists at the given path.
    |
    */

    'ssr' => [
        'enabled' => true,
        'bundle' => base_path('bootstrap/ssr/ssr.mjs'),
    ],

];
