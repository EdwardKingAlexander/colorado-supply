<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * No texts overnight (ai/modules/will-call-notifications, phase 6). A text
 * due in the quiet window (default 9pm–8am Denver time) waits until it ends.
 */
class SmsQuietHours
{
    /**
     * When a text queued now may go out: null if it can go now.
     */
    public static function sendAt(?Carbon $now = null): ?Carbon
    {
        $timezone = config('sms.quiet_hours.timezone', 'America/Denver');
        $now = ($now ?? now())->copy()->setTimezone($timezone);

        $start = $now->copy()->setTimeFromTimeString(config('sms.quiet_hours.start', '21:00'));
        $end = $now->copy()->setTimeFromTimeString(config('sms.quiet_hours.end', '08:00'));

        // Window crosses midnight (21:00–08:00).
        if ($start->greaterThan($end)) {
            if ($now->greaterThanOrEqualTo($start)) {
                return $end->addDay();
            }

            return $now->lessThan($end) ? $end : null;
        }

        return $now->greaterThanOrEqualTo($start) && $now->lessThan($end) ? $end : null;
    }
}
