<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'notifications' => $user->notifications()
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($notification) => [
                    'id' => $notification->id,
                    'order_number' => $notification->data['order_number'] ?? null,
                    'label' => $notification->data['label'] ?? 'Order update',
                    'tracker_url' => $notification->data['tracker_url'] ?? null,
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                    'created_human' => $notification->created_at?->diffForHumans(),
                ]),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }
}
