<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskDragEnded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $taskId;
    public $projectId;
    public $userId;
    public $timestamp;

    public function __construct($taskId, $projectId, $userId = null)
    {
        $this->taskId = $taskId;
        $this->projectId = $projectId;
        $this->userId = $userId;
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
            'userId' => $this->userId,
            'timestamp' => $this->timestamp,
            'eventType' => 'task_drag_ended',
            'eventName' => 'TaskDragEnded'
        ];
    }
}

