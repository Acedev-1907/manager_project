<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TaskCommentCreated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $comment;
    public $taskId;

    public function __construct($comment, $taskId)
    {
        $this->comment = $comment;
        $this->taskId = $taskId;
    }

    public function broadcastOn()
    {
        // Log::info('Broadcasting TaskCommentCreated event for task', ['taskId' => $this->taskId, 'comment_id' => $this->comment->id ?? null]);
        return [new PrivateChannel('task.' . $this->taskId)];
    }

    /**
     * The event's broadcast name.
     * Frontend listens for 'TaskCommentCreated' (not 'App\Events\TaskCommentCreated')
     */
    public function broadcastAs()
    {
        return 'TaskCommentCreated';
    }

    public function broadcastWith()
    {
        return [
            'comment' => $this->comment->load('user')
        ];
    }
}
