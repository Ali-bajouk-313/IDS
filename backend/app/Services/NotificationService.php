<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class NotificationService
{
    public function createForUser(User $user, string $type, string $title, string $message, ?array $data = null): ?Notification
    {
        if (!Schema::hasTable('notifications')) {
            return null;
        }

        // For assignment notifications, include the assigned support name and append to the message
        $payload = [
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ];

        if ($type === 'ticket_assigned') {
            $assignedName = $user->fullName ?? ($user->name ?? null);
            if ($assignedName) {
                if (Schema::hasColumn('notifications', 'assigned_support_name')) {
                    $payload['assigned_support_name'] = $assignedName;
                }
                // append a readable line to the message always
                $payload['message'] = trim($payload['message']) . "\nSupport Agent: " . $assignedName;
            }
        }

        return Notification::create($payload);
    }

    public function createForUsers(iterable $users, string $type, string $title, string $message, ?array $data = null): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        foreach ($users as $user) {
            $this->createForUser($user, $type, $title, $message, $data);
        }
    }

    public function notifyManagers(string $type, string $title, string $message, ?array $data = null, ?int $excludeUserId = null): void
    {
        $managers = User::whereHas('role', function ($query) {
            $query->where('roleName', 'Manager');
        })
            ->when($excludeUserId !== null, fn ($query) => $query->where('id', '!=', $excludeUserId))
            ->get();

        $this->createForUsers($managers, $type, $title, $message, $data);
    }
}
