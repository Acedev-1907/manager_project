<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class TaskCommented extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $task;
    protected $comment;

    /**
     * Khởi tạo notification với thông tin task và comment
     */
    public function __construct($task, $comment)
    {
        $this->task = $task;
        $this->comment = $comment;
    }

    /**
     * Gửi notification qua database và broadcast
     */
    public function via()
    {
        return ['database', 'broadcast'];
    }

    /**
     * Dữ liệu lưu vào bảng notifications
     */
    public function toDatabase()
    {
        return [
            'message' => 'New comment on task: ' . $this->task->name,
            'task_id' => $this->task->id,
            'comment_id' => $this->comment->id,
        ];
    }

    /**
     * Dữ liệu gửi qua broadcast (Pusher)
     */
    public function toBroadcast()
    {
        return new BroadcastMessage([
            'message' => 'New comment on task: ' . $this->task->name,
            'task_id' => $this->task->id,
            'comment_id' => $this->comment->id,
        ]);
    }

    /**
     * Channel broadcast (theo user id)
     */
    public function broadcastOn($notifiable = null)
    {
        $userId = is_object($notifiable) && property_exists($notifiable, 'id') ? $notifiable->id : (auth()->id() ?? 0);
        return ['user-notification.' . $userId];
    }
}
