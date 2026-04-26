<?php

namespace App\Http\Controllers\admin;

use App\Models\AdminNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $adminId = Auth::guard('admin')->id();
        if (!$adminId) {
            return response()->json([
                'notifications' => [],
                'unread_count' => 0,
            ], 401);
        }

        $notifications = AdminNotification::query()
            ->where('admin_id', $adminId)
            ->latest()
            ->take(10)
            ->get()
            ->map(function (AdminNotification $notification) {
                return [
                    'id' => $notification->id,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at,
                    'data' => [
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'type' => $notification->type,
                        'link' => $notification->link,
                    ],
                ];
            })
            ->values();

        $unreadCount = AdminNotification::query()
            ->where('admin_id', $adminId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markAsRead($id)
    {
        $adminId = Auth::guard('admin')->id();
        if (!$adminId) {
            return response()->json(['success' => false], 401);
        }

        $notification = AdminNotification::query()
            ->where('admin_id', $adminId)
            ->findOrFail($id);

        if (!$notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function markAllAsRead()
    {
        $adminId = Auth::guard('admin')->id();
        if (!$adminId) {
            return response()->json(['success' => false], 401);
        }

        AdminNotification::query()
            ->where('admin_id', $adminId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}
