<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
| Private notification channels (ai/modules/will-call-notifications, phase 3).
| Each channel is checked against its own guard, so a portal user can never
| subscribe to an admin's channel (or another user's), and vice versa.
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return $user instanceof User && (int) $user->id === (int) $id;
}, ['guards' => ['web']]);

Broadcast::channel('App.Models.Admin.{id}', function ($user, $id) {
    return $user instanceof Admin && (int) $user->id === (int) $id;
}, ['guards' => ['admin']]);
