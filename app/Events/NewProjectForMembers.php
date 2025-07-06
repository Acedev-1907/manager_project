<?php

namespace App\Events;

use App\Models\Project;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewProjectForMembers implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $project;
    public $memberId;

    public function __construct(Project $project, $memberId)
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
            'project' => $this->project
        ];
    }
}
