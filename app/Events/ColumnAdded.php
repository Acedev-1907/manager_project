<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ColumnAdded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $column;
    public $projectId;
    public $userId;
    public $updatedAt;

    public function __construct($column, $projectId, $userId = null)
    {
        $this->column = $column;
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
            'column' => $this->column,
            'projectId' => $this->projectId,
            'userId' => $this->userId,
            'updatedAt' => $this->updatedAt,
            'eventType' => 'column_added',
            'eventName' => 'ColumnAdded'
        ];
    }
}

