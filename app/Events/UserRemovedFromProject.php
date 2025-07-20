<?php

namespace App\Events;


use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class UserRemovedFromProject implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $project;
    public $memberId;

    public function __construct($project, $memberId)
    {
        $this->project = $project;
        $this->memberId = $memberId;
    }

    public function broadcastOn()
    {
        return [new PrivateChannel('user.' . $this->memberId)];
    }

    public function broadcastWith()
    {
        return [
            'projectId' => $this->project->id
        ];
    }
}
