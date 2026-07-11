<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\Quote;

/*
|--------------------------------------------------------------------------
| Privacy & Cookie Compliance Registry
|--------------------------------------------------------------------------
|
| Single source of truth for the privacy program. The cookie policy page,
| consent banner categories, DSR deadlines, and retention purge jobs all
| read from this file. When adding any cookie or tracker to the app, add
| it here first — see ai/modules/privacy-compliance/data-inventory.md.
|
*/

return [

    /*
    | Bump when the privacy/cookie policy or cookie registry changes in a
    | way that requires re-consent. Stored consent with an older version
    | triggers the banner again.
    */
    'policy_version' => '2026-07-10',

    'consent_cookie' => [
        'name' => 'cs_privacy_consent',
        'lifetime_days' => 365,
    ],

    /*
    | Consent categories. `locked` categories cannot be disabled by the
    | visitor (strictly necessary). Keys are stored in consent receipts.
    */
    'categories' => [
        'essential' => [
            'label' => 'Strictly Necessary',
            'description' => 'Required for the site to function: sign-in sessions, security tokens, and remembering your privacy choices. Always active.',
            'locked' => true,
        ],
        'analytics' => [
            'label' => 'Analytics',
            'description' => 'Helps us understand how the site is used (Google Analytics). Disabled automatically when your browser sends a Global Privacy Control signal.',
            'locked' => false,
        ],
        'marketing' => [
            'label' => 'Marketing',
            'description' => 'Used for advertising and cross-site tracking. Colorado Supply does not currently set any marketing cookies.',
            'locked' => false,
        ],
    ],

    /*
    | Cookie registry — rendered verbatim on the cookie policy page.
    */
    'cookies' => [
        [
            'name' => 'colorado_supply_session',
            'provider' => 'Colorado Supply (Laravel)',
            'category' => 'essential',
            'purpose' => 'Keeps you signed in and secures forms during your visit.',
            'lifetime' => '2 hours',
        ],
        [
            'name' => 'XSRF-TOKEN',
            'provider' => 'Colorado Supply (Laravel)',
            'category' => 'essential',
            'purpose' => 'Protects forms and requests against cross-site request forgery.',
            'lifetime' => '2 hours',
        ],
        [
            'name' => 'remember_web_*',
            'provider' => 'Colorado Supply (Laravel)',
            'category' => 'essential',
            'purpose' => 'Keeps you signed in when you choose "remember me".',
            'lifetime' => '5 years',
        ],
        [
            'name' => 'remember_admin_*',
            'provider' => 'Colorado Supply (Laravel)',
            'category' => 'essential',
            'purpose' => 'Persistent sign-in for staff administrators.',
            'lifetime' => '5 years',
        ],
        [
            'name' => 'cs_privacy_consent',
            'provider' => 'Colorado Supply',
            'category' => 'essential',
            'purpose' => 'Remembers your cookie consent choices and whether a Global Privacy Control signal was honored.',
            'lifetime' => '1 year',
        ],
        [
            'name' => '_ga',
            'provider' => 'Google Analytics',
            'category' => 'analytics',
            'purpose' => 'Distinguishes visitors for usage statistics.',
            'lifetime' => '2 years',
        ],
        [
            'name' => '_ga_RZ06XS51X0',
            'provider' => 'Google Analytics',
            'category' => 'analytics',
            'purpose' => 'Maintains analytics session state for this site.',
            'lifetime' => '2 years',
        ],
    ],

    /*
    | Browser storage that is not a cookie but is disclosed alongside them.
    */
    'local_storage' => [
        [
            'key' => 'colorado-supply.cart',
            'purpose' => 'Stores your cart contents between visits.',
        ],
    ],

    /*
    | Data subject request handling (statutory clocks per CO/CA baseline).
    */
    'dsr' => [
        'response_days' => 45,
        'extension_days' => 45,
        'verification_ttl_hours' => 72,
    ],

    /*
    | Retention windows enforced by the scheduled purge (Phase 5).
    */
    'retention' => [
        'consent_receipts_years' => 5,
        'completed_dsr_months' => 24,
        'unverified_dsr_days' => 30,
    ],

    /*
    | Records that are anonymized rather than deleted when fulfilling a
    | deletion request (tax, accounting, and federal contract retention).
    */
    'legal_hold' => [
        Order::class,
        Payment::class,
        Quote::class,
    ],
];
