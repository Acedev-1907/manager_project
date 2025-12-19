<?php

namespace App\Services;

use App\Events\PrivateMessageCreated;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatService
{
    /**
     * Lấy hoặc tạo conversation giữa 2 user
     */
    protected function getOrCreateConversation(int $userId, int $otherUserId): Conversation
    {
        // Sắp xếp id để đảm bảo unique(user_one_id, user_two_id)
        $ids = [$userId, $otherUserId];
        sort($ids);

        return Conversation::firstOrCreate([
            'user_one_id' => $ids[0],
            'user_two_id' => $ids[1],
        ]);
    }

    /**
     * Gửi tin nhắn 1-1
     */
    public function sendMessage(User $sender, int $receiverId, string $body): array
    {
        if ($sender->id === $receiverId) {
            return ['error' => 'Không thể tự chat với chính mình'];
        }

        $receiver = User::find($receiverId);
        if (!$receiver) {
            return ['error' => 'Người nhận không tồn tại'];
        }

        // Optional: chỉ cho phép chat với friend (bảng members)
        $isFriend = Member::where('user_id', $sender->id)
            ->where('member_id', $receiverId)
            ->exists();

        if (!$isFriend) {
            return ['error' => 'Chỉ có thể chat với bạn bè trong danh sách members'];
        }

        return DB::transaction(function () use ($sender, $receiver, $body) {
            $conversation = $this->getOrCreateConversation($sender->id, $receiver->id);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'body' => $body,
            ]);

            // Load sender để broadcast
            $message->load('sender');

            // Broadcast realtime cho receiver (người nhận tin nhắn)
            broadcast(new PrivateMessageCreated($message, $receiver->id));
            
            // Broadcast cho cả sender để cập nhật conversation list realtime
            broadcast(new PrivateMessageCreated($message, $sender->id));

            return [
                'message' => $message,
                'conversation_id' => $conversation->id,
            ];
        });
    }

    /**
     * Lấy danh sách cuộc hội thoại của user (kèm last message)
     */
    public function getConversations(User $user, int $perPage = 20)
    {
        $userId = $user->id;

        $query = Conversation::query()
            ->where(function ($q) use ($userId) {
                $q->where('user_one_id', $userId)
                    ->orWhere('user_two_id', $userId);
            })
            ->with([
                'userOne:id,name,email,avatar',
                'userTwo:id,name,email,avatar',
                'messages' => function ($q) {
                    $q->latest('created_at')->limit(1);
                },
            ])
            ->withCount([
                'messages as unread_count' => function ($q) use ($userId) {
                    $q->whereNull('read_at')
                        ->where('receiver_id', $userId);
                },
            ])
            ->orderByDesc(
                Message::select('created_at')
                    ->whereColumn('conversation_id', 'conversations.id')
                    ->latest('created_at')
                    ->take(1)
            );

        $paginator = $query->paginate($perPage);

        // Chuẩn hóa dữ liệu trả về cho FE
        $paginator->getCollection()->transform(function (Conversation $conv) use ($userId) {
            $other = $conv->otherUser($userId);
            $lastMessage = $conv->messages->first();

            return [
                'id' => $conv->id,
                'other_user' => $other ? [
                    'id' => $other->id,
                    'name' => $other->name,
                    'email' => $other->email,
                    'avatar' => $other->avatar,
                ] : null,
                'last_message' => $lastMessage ? [
                    'id' => $lastMessage->id,
                    'sender_id' => $lastMessage->sender_id,
                    'receiver_id' => $lastMessage->receiver_id,
                    'body' => $lastMessage->body,
                    'created_at' => $lastMessage->created_at,
                    'read_at' => $lastMessage->read_at,
                ] : null,
                'unread_count' => (int) $conv->unread_count,
            ];
        });

        return $paginator;
    }

    /**
     * Lấy danh sách message giữa current user và 1 user khác
     */
    public function getMessages(User $user, int $otherUserId, int $perPage = 50)
    {
        if ($user->id === $otherUserId) {
            return ['error' => 'Không hợp lệ'];
        }

        $other = User::find($otherUserId);
        if (!$other) {
            return ['error' => 'Người dùng không tồn tại'];
        }

        $conversation = $this->getOrCreateConversation($user->id, $otherUserId);

        $query = Message::where('conversation_id', $conversation->id)
            ->with('sender:id,name,avatar')
            ->orderBy('created_at', 'asc');

        $paginator = $query->paginate($perPage);

        // Đánh dấu các tin nhắn gửi tới current user là đã đọc
        Message::where('conversation_id', $conversation->id)
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return [
            'conversation_id' => $conversation->id,
            'other_user' => [
                'id' => $other->id,
                'name' => $other->name,
                'email' => $other->email,
                'avatar' => $other->avatar,
            ],
            'messages' => $paginator,
        ];
    }
}


