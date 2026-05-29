<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // جلب إشعارات العميل
    public function index(Request $request)
    {
        $client = $request->user();
        
        $notifications = Notification::where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $unreadCount = Notification::where('client_id', $client->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    // تعليم إشعار كمقروء
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    // تعليم الكل كمقروء
    public function markAllAsRead(Request $request)
    {
        $client = $request->user();
        Notification::where('client_id', $client->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

public function saveDeviceToken(Request $request)
{
    $request->validate([
        'device_token' => 'required'
    ]);

    $client = $request->user();

    $client->update([
        'device_token' => $request->device_token
    ]);

    return response()->json([
        'success' => true
    ]);
}
}