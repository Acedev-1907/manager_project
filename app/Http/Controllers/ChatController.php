<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ApiController;
use App\Services\ChatService;
use Illuminate\Http\Request;

class ChatController extends ApiController
{
    protected ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Danh sách cuộc hội thoại của user (sidebar)
     */
    public function conversations(Request $request)
    {
        $user = $request->user();
        $perPage = (int) $request->get('per_page', 20);

        $conversations = $this->chatService->getConversations($user, $perPage);

        return $this->respondWithData($conversations);
    }

    /**
     * Lấy toàn bộ message giữa current user và 1 user khác
     */
    public function messages(Request $request)
    {
        $user = $request->user();
        $otherUserId = (int) $request->get('user_id');
        $perPage = (int) $request->get('per_page', 50);

        if (!$otherUserId) {
            return $this->respondValidationError('user_id là bắt buộc');
        }

        $result = $this->chatService->getMessages($user, $otherUserId, $perPage);

        if (isset($result['error'])) {
            return $this->respondValidationError($result['error']);
        }

        return $this->respondWithData($result);
    }

    /**
     * Gửi tin nhắn 1-1
     */
    public function send(Request $request)
    {
        $user = $request->user();
        $fields = $request->all();

        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'body' => 'required|string|max:5000',
        ]);

        $result = $this->chatService->sendMessage($user, (int) $fields['receiver_id'], (string) $fields['body']);

        if (isset($result['error'])) {
            return $this->respondValidationError($result['error']);
        }

        return $this->respondWithData($result['message'], 'Gửi tin nhắn thành công');
    }
}


