<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;
    public $projectId;
    public $taskId;
    public $status;
    public $userId;
    public $updatedAt;

    public function __construct($task, $projectId, $status, $userId = null)
    {
        $this->task = $task;
        $this->projectId = $projectId;
        $this->taskId = $task?->id;
        $this->status = $status;
        $this->userId = $userId;
        $this->updatedAt = now()->toISOString();
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
            'task' => $this->task,
            'taskId' => $this->taskId,
            'projectId' => $this->projectId,
            'status' => $this->status,
            'userId' => $this->userId,
            'updatedAt' => $this->updatedAt,
            'eventType' => 'task_status_changed',
            'eventName' => 'TaskStatusChanged'
        ];
    }
}

