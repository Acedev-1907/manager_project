<?php
// Thông báo khi user tạo post thành công, hỗ trợ broadcast qua Pusher
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Support\Facades\Auth;

class PostCreated extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $post;
    protected $user;

    /**
     * Khởi tạo notification với thông tin post và user
     */
    public function __construct($post, $user)
    {
        $this->post = $post;
        $this->user = $user;
    }

    /**
     * Gửi notification qua database và broadcast
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    /**
     * Dữ liệu lưu vào bảng notifications
     */
    public function toDatabase($notifiable)
    {
        return [
            'message' => 'Your post has been successfully published!',
            'post_id' => $this->post->id,
            'sender_avatar' => $this->user->avatar ?? null,
            'sender_name' => $this->user->name ?? 'You',
            'type' => 'post_created',
        ];
    }

    /**
     * Dữ liệu gửi qua broadcast (Pusher)
     */
    public function toBroadcast($notifiable)
    {
        $unreadCount = $notifiable->unreadNotifications()->count();
        
        return new BroadcastMessage([
            'message' => 'Your post has been successfully published!',
            'post_id' => $this->post->id,
            'sender_avatar' => $this->user->avatar ?? null,
            'sender_name' => $this->user->name ?? 'You',
            'type' => 'post_created',
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Channel broadcast (theo user id)
     */
    public function broadcastOn($notifiable = null)
    {
        if ($notifiable && method_exists($notifiable, 'getKey')) {
            $userId = $notifiable->getKey();
        } else {
            $userId = Auth::id() ?? 0;
        }
        return ['App.Models.User.' . $userId];
    }
}

