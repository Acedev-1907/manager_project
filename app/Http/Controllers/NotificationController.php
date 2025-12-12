<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiController;

/**
 * Notification Controller
 * 
 * Handles all notification-related operations including retrieving,
 * marking as read, and deleting notifications.
 */
class NotificationController extends ApiController
{
    /**
     * Get the current user's notifications (limited to 10)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->take(10)->get();
        $unreadCount = $user->unreadNotifications()->count();
        
        return $this->respondWithData([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ], 'Notifications retrieved successfully');
    }

    /**
     * Mark a notification as read
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request, string $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->find($id);
        
        if (!$notification) {
            return $this->respondNotFound('Notification not found');
        }
        
        $notification->markAsRead();
        return $this->respondUpdated('Notification marked as read');
    }

    /**
     * Delete a notification
     * 
     * @param Request $request
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->find($id);
        
        if (!$notification) {
            return $this->respondNotFound('Notification not found');
        }
        
        $notification->delete();
        return $this->respondDeleted('Notification deleted');
    }

    /**
     * Get all notifications for the current user (no limit)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function all(Request $request)
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->get();
        $unreadCount = $user->unreadNotifications()->count();
        
        return $this->respondWithData([
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ], 'All notifications retrieved successfully');
    }

    /**
     * Mark all notifications as read for the current user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();
        
        return $this->respondUpdatedWithData('All notifications marked as read', [
            'unread_count' => 0
        ]);
    }
}
