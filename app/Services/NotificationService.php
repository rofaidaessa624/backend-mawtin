<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private FCMService $fcmService;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    public function notify(
        ?int $clientId,
        string $title,
        string $body,
        string $type = 'info',
        array $data = [],
        bool $sendPush = true
    ): Notification {
        try {
            $notification = Notification::create([
                'client_id' => $clientId,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'data' => $data,
            ]);

            if ($sendPush && $clientId) {
                $this->sendPushNotification($clientId, $title, $body, $data);
            }

            return $notification;
        } catch (\Exception $e) {
            Log::error('Notification creation failed', [
                'error' => $e->getMessage(),
                'client_id' => $clientId,
            ]);
            throw $e;
        }
    }

    public function notifyMultiple(
        array $clientIds,
        string $title,
        string $body,
        string $type = 'info',
        array $data = [],
        bool $sendPush = true
    ): array {
        $notifications = [];

        try {
            DB::beginTransaction();

            foreach ($clientIds as $clientId) {
                $notifications[] = Notification::create([
                    'client_id' => $clientId,
                    'title' => $title,
                    'body' => $body,
                    'type' => $type,
                    'data' => $data,
                ]);
            }

            DB::commit();

            if ($sendPush) {
                $this->fcmService->sendToClients($clientIds, $title, $body, $data);
            }

            return $notifications;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk notification creation failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function sendPushNotification(int $clientId, string $title, string $body, array $data = []): bool
    {
        try {
            $client = Client::find($clientId);

            if (!$client || !$client->device_token) {
                Log::warning('Client not found or has no device token', [
                    'client_id' => $clientId,
                ]);
                return false;
            }

            return $this->fcmService->sendToDevice(
                $client->device_token,
                $title,
                $body,
                $data
            );
        } catch (\Exception $e) {
            Log::error('Push notification send failed', [
                'error' => $e->getMessage(),
                'client_id' => $clientId,
            ]);
            return false;
        }
    }

    public function getNotifications(int $clientId, int $limit = 20, bool $onlyUnread = false): array
    {
        $query = Notification::forClient($clientId);

        if ($onlyUnread) {
            $query->unread();
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getUnreadCount(int $clientId): int
    {
        return Notification::forClient($clientId)->unread()->count();
    }

    public function markAsRead(int $notificationId, int $clientId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('client_id', $clientId)
            ->first();

        if (!$notification) {
            return false;
        }

        return $notification->markAsRead();
    }

    public function markAllAsRead(int $clientId): int
    {
        return Notification::forClient($clientId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
