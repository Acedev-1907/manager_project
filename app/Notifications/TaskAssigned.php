<?php
// Thông báo khi user được giao task mới, hỗ trợ broadcast qua Pusher
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class TaskAssigned extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $task;

    /**
     * Khởi tạo notification với thông tin task
     */
    public function __construct($task)
    {
        $this->task = $task;
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
            'message' => 'You have been assigned a new task: ' . $this->task->name,
            'task_id' => $this->task->id,
            'project_id' => $this->task->project_id,
        ];
    }

    /**
     * Dữ liệu gửi qua broadcast (Pusher)
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => 'You have been assigned a new task: ' . $this->task->name,
            'task_id' => $this->task->id,
            'project_id' => $this->task->project_id,
        ]);
    }

    /**
     * Channel broadcast (theo user id)
     */
    public function broadcastOn($notifiable = null)
    {
        $userId = is_object($notifiable) && property_exists($notifiable, 'id') ? $notifiable->id : (auth()->id() ?? 0);
        return ['App.Models.User.' . $userId];
    }
}
