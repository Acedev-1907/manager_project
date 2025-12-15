<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDragOverColumn implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $taskId;
    public $projectId;
    public $columnId;
    public $columnStatus;
    public $userId;
    public $userName;
    public $userAvatar;
    public $timestamp;

    public function __construct($taskId, $projectId, $columnId, $columnStatus, $userId, $userName = null, $userAvatar = null)
    {
        $this->taskId = $taskId;
        $this->projectId = $projectId;
        $this->columnId = $columnId;
        $this->columnStatus = $columnStatus;
        $this->userId = $userId;
        $this->userName = $userName;
        $this->userAvatar = $userAvatar;
        $this->timestamp = now()->toISOString();
    }

    public function broadcastOn(): array
    {
        if (!$this->projectId) {
            return [];
        }

        return [
            new PrivateChannel('project.' . $this->projectId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'taskId' => $this->taskId,
            'projectId' => $this->projectId,
            'columnId' => $this->columnId,
            'columnStatus' => $this->columnStatus,
            'userId' => $this->userId,
            'userName' => $this->userName,
            'userAvatar' => $this->userAvatar,
            'timestamp' => $this->timestamp,
            'eventType' => 'task_drag_over_column',
            'eventName' => 'TaskDragOverColumn'
        ];
    }
}

