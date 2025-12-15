<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ColumnDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $columnId;
    public $projectId;
    public $userId;
    public $updatedAt;

    public function __construct($columnId, $projectId, $userId = null)
    {
        $this->columnId = $columnId;
        $this->projectId = $projectId;
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
            'columnId' => $this->columnId,
            'projectId' => $this->projectId,
            'userId' => $this->userId,
            'updatedAt' => $this->updatedAt,
            'eventType' => 'column_deleted',
            'eventName' => 'ColumnDeleted'
        ];
    }
}

