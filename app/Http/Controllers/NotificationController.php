<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get the current user's notification in the list
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()->latest()->take(10)->get();

        $unreadCount = $user->unreadNotifications()->count();
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Đánh dấu notification đã đọc
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
            return response()->json(['message' => 'Notification marked as read']);
        }
        return response()->json(['message' => 'Notification not found'], 404);
    }

    /**
     * Delete the user's current notification
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->find($id);
        if ($notification) {
            $notification->delete();
            return response()->json(['message' => 'Notification deleted']);
        }
        return response()->json(['message' => 'Notification not found'], 404);
    }

    /**
     * Get all notifications for the current user (no limit)
     */
    public function all(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->get();
        $unreadCount = $user->unreadNotifications()->count();
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
    }

    /**
     * Mark all notifications as read for the current user
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();
        return response()->json([
            'message' => 'All notifications marked as read',
            'unread_count' => 0
        ]);
    }
}
