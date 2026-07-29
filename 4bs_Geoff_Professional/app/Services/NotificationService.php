<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    public function send(
        int|User $user,
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null
    ): Notification {
        $userId = $user instanceof User ? $user->id : $user;

        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
        ]);
    }

    public function sendToAdmins(
        string $type,
        string $title,
        ?string $body = null,
        ?string $actionUrl = null
    ): void {
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $this->send($admin, $type, $title, $body, $actionUrl);
        }
    }

    public function unreadCount(int $userId): int
    {
        return Notification::forUser($userId)->unread()->count();
    }
}
