<?php

namespace App\Modules\Core\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationService
{
    public function send(User $recipient, string $type, string $entityKey, array $data): DatabaseNotification
    {
        $dedupeKey = hash('sha256', implode('|', [$type, $entityKey, now()->toDateString()]));
        $notificationId = (string) Str::uuid();
        $now = now();

        DB::table('notifications')->insertOrIgnore([
            'id' => $notificationId,
            'type' => $type,
            'notifiable_type' => $recipient->getMorphClass(),
            'notifiable_id' => $recipient->getKey(),
            'data' => json_encode($data, JSON_THROW_ON_ERROR),
            'dedupe_key' => $dedupeKey,
            'read_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $recipient->notifications()->where('dedupe_key', $dedupeKey)->firstOrFail();
    }

    /**
     * @return array{items: Collection<int, DatabaseNotification>, unreadCount: int}
     */
    public function summaryFor(User $user): array
    {
        return [
            'items' => $user->notifications()->latest()->limit(5)->get(),
            'unreadCount' => $user->unreadNotifications()->count(),
        ];
    }

    public function markAsRead(User $user, string $notificationId): DatabaseNotification
    {
        $notification = $user->notifications()->whereKey($notificationId)->firstOrFail();

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return $notification->refresh();
    }
}
