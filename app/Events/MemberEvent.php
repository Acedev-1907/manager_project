<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MemberEvent implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $userId;      // Event recipient
    public $byUser;      // The person who performs the action
    public $action;      // Action type: removed, updated, ...
    public $payload;     // Additional data (if any)

    public function __construct($userId, $byUser, $action, $payload = [])
    {
        $this->userId = $userId;
        $this->byUser = $byUser;
        $this->action = $action;
        $this->payload = $payload;
    }

    public function broadcastOn()
    {
        // Log::info('Broadcasting MemberEvent', [
        //     'userId' => $this->userId,
        //     'byUser' => $this->byUser,
        //     'action' => $this->action,
        //     'payload' => $this->payload
        // ]);
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastWith()
    {
        return [
            'byUser' => $this->byUser,
            'action' => $this->action,
            'payload' => $this->payload
        ];
    }
}
