<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrackCompletedAndPending implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tasks;
    public $projectId;
    public $taskId;
    public $updatedAt;
    public $userId;

    public function __construct($tasks, $projectId = null, $taskId = null, $userId = null)
    {
        $this->tasks = $tasks;
        $this->projectId = $projectId;
        $this->taskId = $taskId;
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
            'tasks' => $this->tasks,
            'projectId' => $this->projectId,
            'taskId' => $this->taskId,
            'userId' => $this->userId,
            'updatedAt' => $this->updatedAt,
            'eventType' => 'task_status_changed',
            'eventName' => 'TrackCompletedAndPending'
        ];
    }
}
