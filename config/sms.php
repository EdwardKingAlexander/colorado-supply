<?php

/*
| Text message alerts (ai/modules/will-call-notifications-complete, phase 6).
| Off until the Twilio A2P 10DLC registration is approved: nothing is ever
| texted while SMS_ENABLED is false. Twilio credentials live in
| config/twilio-notification-channel.php.
*/

return [
    'enabled' => (bool) env('SMS_ENABLED', false),

    // Texts that would land in this window wait until it ends (business timezone).
    'quiet_hours' => [
        'start' => env('SMS_QUIET_START', '21:00'),
        'end' => env('SMS_QUIET_END', '08:00'),
        'timezone' => env('SMS_TIMEZONE', 'America/Denver'),
    ],

    // Shown next to the consent checkbox and stored with each consent.
    'consent_version' => '2026-10-12',
    'consent_text' => 'I agree to receive text messages from Colorado Supply & Procurement about my orders and pickups (or, for staff, store alerts). Message frequency varies. Message and data rates may apply. Reply STOP to cancel or HELP for help. Consent is not a condition of purchase.',

    'help_reply' => 'Colorado Supply & Procurement order alerts. For help, reply to your order email or visit our site. Msg & data rates may apply. Reply STOP to cancel.',

    'verification' => [
        'ttl_minutes' => 10,
        'max_attempts' => 5,
        'max_sends_per_hour' => 3,
    ],
];
