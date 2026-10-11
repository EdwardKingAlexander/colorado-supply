<?php

/*
| Will call pickup reminders (ai/modules/will-call-notifications, phase 4).
| Days are counted from when the order was marked ready for pickup.
*/

return [
    // Remind the buyer after this many days uncollected.
    'buyer_reminder_days' => [3, 7],

    // Alert admins once the order has waited this many days.
    'admin_alert_days' => 10,
];
