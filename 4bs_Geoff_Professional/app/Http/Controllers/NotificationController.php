<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::forUser($request->user()->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'actionUrl' => $n->action_url,
                'isRead' => $n->is_read,
                'createdAt' => $n->created_at?->diffForHumans(),
            ]);

        return response()->json(['notifications' => $notifications]);
    }

    public function unreadCount(Request $request, NotificationService $service): JsonResponse
    {
        return response()->json(['count' => $service->unreadCount($request->user()->id)]);
    }

    public function markRead(Request $request, Notification $notification): JsonResponse
    {
        abort_if($notification->user_id !== $request->user()->id, 403);

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::forUser($request->user()->id)->unread()->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }
}
