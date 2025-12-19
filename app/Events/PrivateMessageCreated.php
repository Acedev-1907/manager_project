<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class PrivateMessageCreated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public Message $message;
    public int $receiverId;

    public function __construct(Message $message, int $receiverId)
    {
        $this->message = $message;
        $this->receiverId = $receiverId;
    }

    public function broadcastOn()
    {
        // Gửi vào kênh riêng của receiver
        return [new PrivateChannel('user.' . $this->receiverId)];
    }

    public function broadcastAs()
    {
        // Tên event FE sẽ listen
        return 'PrivateMessageCreated';
    }

    public function broadcastWith()
    {
        // Đảm bảo đã load quan hệ sender
        if (!$this->message->relationLoaded('sender')) {
            $this->message->load('sender');
        }

        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'sender_id' => $this->message->sender_id,
                'receiver_id' => $this->message->receiver_id,
                'body' => $this->message->body,
                'read_at' => $this->message->read_at,
                'created_at' => $this->message->created_at,
                'updated_at' => $this->message->updated_at,
                'sender' => [
                    'id' => $this->message->sender->id,
                    'name' => $this->message->sender->name,
                    'avatar' => $this->message->sender->avatar,
                ],
            ],
        ];
    }
}


