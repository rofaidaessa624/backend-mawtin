<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $clientId = Auth::id();
            $limit = $request->get('limit', 20);
            $onlyUnread = $request->get('only_unread', false);

            $notifications = $this->notificationService->getNotifications(
                $clientId,
                $limit,
                $onlyUnread
            );

            $unreadCount = $this->notificationService->getUnreadCount($clientId);

            return response()->json([
                'status' => 'success',
                'data' => $notifications,
                'unread_count' => $unreadCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch notifications',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function markAsRead($id): JsonResponse
    {
        try {
            $clientId = Auth::id();
            $success = $this->notificationService->markAsRead($id, $clientId);

            if (!$success) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Notification not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Notification marked as read',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function markAllAsRead(): JsonResponse
    {
        try {
            $clientId = Auth::id();
            $count = $this->notificationService->markAllAsRead($clientId);

            return response()->json([
                'status' => 'success',
                'message' => 'All notifications marked as read',
                'marked_count' => $count,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to mark all as read',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function saveDeviceToken(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'device_token' => 'required|string',
            ]);

            $client = Auth::user();
            $client->update([
                'device_token' => $request->device_token,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Device token saved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save device token',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
