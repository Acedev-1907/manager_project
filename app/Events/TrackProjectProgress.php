<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrackProjectProgress implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $projectProgress;
    public $projectId;
    public $updatedAt;
    public $userId;

    public function __construct($projectProgress, $projectId = null, $userId = null)
    {
        $this->projectProgress = $projectProgress;
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
            'projectProgress' => $this->projectProgress,
            'projectId' => $this->projectId,
            'userId' => $this->userId,
            'updatedAt' => $this->updatedAt,
            'eventType' => 'project_progress_updated',
            'eventName' => 'TrackProjectProgress'
        ];
    }
}
