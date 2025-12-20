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
     * Get list of conversations for the user (sidebar)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function conversations(Request $request)
    {
        $user = $request->user();
        $perPage = (int) $request->get('per_page', 20);

        $conversations = $this->chatService->getConversations($user, $perPage);

        return $this->respondWithData($conversations, 'Conversations retrieved successfully');
    }

    /**
     * Get all messages between current user and another user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function messages(Request $request)
    {
        $user = $request->user();
        $otherUserId = (int) $request->get('user_id');
        $perPage = (int) $request->get('per_page', 50);

        if (!$otherUserId) {
            return $this->respondValidationError('user_id is required');
        }

        $result = $this->chatService->getMessages($user, $otherUserId, $perPage);

        if (isset($result['error'])) {
            return $this->respondValidationError($result['error']);
        }

        return $this->respondWithData($result, 'Messages retrieved successfully');
    }

    /**
     * Send a 1-on-1 message
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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

        return $this->respondWithData($result['message'], 'Message sent successfully');
    }
}


