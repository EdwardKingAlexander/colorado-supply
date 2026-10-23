<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The buyer's bell and notifications page (ai/modules/will-call-notifications-complete,
 * phase 3). Everything is scoped to the signed-in user's own notifications.
 */
class NotificationController extends Controller
{
    private const PER_PAGE = 15;

    /**
     * JSON for the bell (axios), or the full notifications page (Inertia).
     */
    public function index(Request $request): JsonResponse|Response
    {
        $user = $request->user();
        $unreadOnly = $request->query('filter') === 'unread';

        $page = ($unreadOnly ? $user->unreadNotifications() : $user->notifications())
            ->latest()
            ->paginate(self::PER_PAGE);

        $payload = [
            'notifications' => collect($page->items())->map(fn (DatabaseNotification $notification) => $this->present($notification))->values(),
            'unread_count' => $user->unreadNotifications()->count(),
            'next_page' => $page->hasMorePages() ? $page->currentPage() + 1 : null,
        ];

        if ($request->wantsJson()) {
            return response()->json($payload);
        }

        return Inertia::render('Notifications/Index', $payload + ['filter' => $unreadOnly ? 'unread' : 'all']);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        $this->find($request, $notification)->markAsRead();

        return $this->unreadCount($request);
    }

    public function markUnread(Request $request, string $notification): JsonResponse
    {
        $this->find($request, $notification)->markAsUnread();

        return $this->unreadCount($request);
    }

    public function destroy(Request $request, string $notification): JsonResponse
    {
        $this->find($request, $notification)->delete();

        return $this->unreadCount($request);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['unread_count' => 0]);
    }

    /**
     * One shape for every row. Rows written before the shared payload only
     * have order keys (label, tracker_url), so fall back to those.
     */
    private function present(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'type' => $data['type'] ?? $data['transition'] ?? null,
            'title' => $data['title'] ?? $data['label'] ?? 'Order update',
            'body' => $data['body'] ?? null,
            'url' => $data['url'] ?? $data['tracker_url'] ?? null,
            'icon' => $data['icon'] ?? null,
            'order_number' => $data['order_number'] ?? null,
            'label' => $data['label'] ?? $data['title'] ?? 'Order update',
            'tracker_url' => $data['tracker_url'] ?? null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
            'created_human' => $notification->created_at?->diffForHumans(),
        ];
    }

    private function find(Request $request, string $id): DatabaseNotification
    {
        return $request->user()->notifications()->whereKey($id)->firstOrFail();
    }

    private function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['unread_count' => $request->user()->unreadNotifications()->count()]);
    }
}
