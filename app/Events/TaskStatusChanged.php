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
    public $progress; // Thêm thông tin progress
    public $counts;   // Thêm thông tin số lượng task [completed, pending]

    public function __construct($task, $projectId, $status, $userId = null, $progress = null, $counts = null)
    {
        $this->task = $task;
        $this->projectId = $projectId;
        $this->taskId = $task?->id;
        $this->status = $status;
        $this->userId = $userId;
        $this->updatedAt = now()->toISOString();
        $this->progress = $progress;
        $this->counts = $counts;
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
            'progress' => $this->progress,
            'counts' => $this->counts,
            'eventType' => 'task_status_changed',
            'eventName' => 'TaskStatusChanged'
        ];
    }
}

