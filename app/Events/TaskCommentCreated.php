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
        // \Illuminate\Support\Facades\Log::info('TaskCommentCreated broadcastOn called', [
        //     'taskId' => $this->taskId,
        //     'channel' => 'task.' . $this->taskId
        // ]);
        return [new PrivateChannel('task.' . $this->taskId)];
    }

    /**
     * Event name để frontend listen
     * Frontend sẽ listen với tên 'TaskCommentCreated'
     */
    public function broadcastAs()
    {
        return 'TaskCommentCreated';
    }

    public function broadcastWith()
    {
        // Đảm bảo user relationship được load trước khi broadcast
        if (!$this->comment->relationLoaded('user')) {
            $this->comment->load('user');
        }
        
        $data = [
            'comment' => $this->comment->toArray()
        ];
        
        // Log::info('TaskCommentCreated broadcastWith data', [
        //     'taskId' => $this->taskId,
        //     'commentId' => $this->comment->id ?? null,
        //     'hasComment' => isset($data['comment']),
        //     'hasUser' => isset($data['comment']['user'])
        // ]);
        
        return $data;
    }
}
