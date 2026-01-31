<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification Email
    |--------------------------------------------------------------------------
    |
    | The email address that will receive deadline reminders and document
    | expiration notifications. This should be the business owner or
    | administrator responsible for compliance.
    |
    */

    'notification_email' => env('BUSINESS_HUB_NOTIFICATION_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Default Reminder Days
    |--------------------------------------------------------------------------
    |
    | The default number of days before a deadline to send reminders.
    | These can be overridden per-deadline in the admin panel.
    |
    */

    'default_reminder_days' => [30, 14, 7, 1],

    /*
    |--------------------------------------------------------------------------
    | Document Expiration Warning Days
    |--------------------------------------------------------------------------
    |
    | Days before document expiration to send warnings.
    |
    */

    'document_warning_days' => [60, 30, 14, 7, 1],

];
