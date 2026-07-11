<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mandatory MFA enforcement
    |--------------------------------------------------------------------------
    |
    | When true, every non-admin (web-guard) user must enroll a second factor
    | before accessing gated routes; unenrolled users are redirected to the
    | profile page to set one up. When false (default), MFA is opt-in — users
    | may enroll voluntarily and only enrolled users are challenged at login.
    |
    | This is a deliberate, environment-level switch: turning it on forces every
    | existing customer through enrollment at their next login. Before enabling,
    | confirm the queue worker and mail transport are healthy (email codes
    | depend on both) and that users have been given notice.
    |
    */

    'required' => env('MFA_REQUIRED', false),

];
