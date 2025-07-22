<?php

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

class NewProjectAssigned extends Notification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $project;
    public $memberId;
    public $message;
    public $creator;
    public $creatorAvatar;
    public $creatorName;

    /**
     * Create a new notification instance.
     */
    public function __construct(Project $project, int $memberId)
    {
        $this->project = $project;
        $this->memberId = $memberId;
        $this->creator = $project->creator; // relation đã có
        $this->creatorAvatar = $this->creator ? $this->creator->avatar : null;
        $this->creatorName = $this->creator ? $this->creator->name : null;

        if ($memberId === $project->creator_id) {
            $this->message = 'You have created a new project: ' . $project->name;
        } else {
            $this->message = $this->creatorName . ' added you to a new project: ' . $project->name;
        }
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification for database.
     */
    public function toDatabase(): array
    {
        return [
            'message' => $this->message,
            'project_id' => $this->project->id,
            'slug' => $this->project->slug,
            'avatar' => $this->creatorAvatar,
            'creator_name' => $this->creatorName,
        ];
    }

    /**
     * Get the array representation of the notification for broadcast.
     */
    public function toBroadcast(): array
    {
        $unreadCount = 0;
        if ($this->memberId) {
            $userModel = \App\Models\User::find($this->memberId);
            if ($userModel) {
                $unreadCount = $userModel->unreadNotifications()->count();
            }
        }
        return [
            'message' => $this->message,
            'project_id' => $this->project->id,
            'slug' => $this->project->slug,
            'unread_count' => $unreadCount,
            'avatar' => $this->creatorAvatar,
            'creator_name' => $this->creatorName,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('user-notification.' . $this->memberId)];
    }
}
