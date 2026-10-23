<?php

namespace App\Http\Controllers;

use App\Notifications\NotificationChannel;
use App\Support\SmsSubscriptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A buyer turning text alerts on (consent + code) and off
 * (ai/modules/will-call-notifications-complete, phase 6). Unavailable until SMS is
 * enabled on the server.
 */
class SmsSubscriptionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(SmsSubscriptions::status($request->user()));
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureEnabled();

        SmsSubscriptions::start($request->user(), $request->input('phone'), $request->boolean('consent'), $request->ip());

        return response()->json(SmsSubscriptions::status($request->user()));
    }

    public function verify(Request $request): JsonResponse
    {
        $this->ensureEnabled();

        SmsSubscriptions::verify($request->user(), $request->input('code'));

        return response()->json(SmsSubscriptions::status($request->user()));
    }

    public function destroy(Request $request): JsonResponse
    {
        SmsSubscriptions::stop($request->user());

        return response()->json(SmsSubscriptions::status($request->user()));
    }

    private function ensureEnabled(): void
    {
        abort_unless(NotificationChannel::Sms->isReady(), 404);
    }
}
