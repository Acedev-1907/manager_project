<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ApiController;
use App\Models\AIConversation;
use App\Models\Project;
use App\Services\AIConversationService;
use Illuminate\Http\Request;

class AIChatController extends ApiController
{
    protected AIConversationService $aiConversationService;

    public function __construct(AIConversationService $aiConversationService)
    {
        $this->aiConversationService = $aiConversationService;
    }

    /**
     * Get list of AI conversations for the user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function conversations(Request $request)
    {
        $user = $request->user();
        $perPage = (int) $request->get('per_page', 20);

        $conversations = $this->aiConversationService->getConversations($user, $perPage);

        return $this->respondWithData($conversations, 'AI conversations retrieved successfully');
    }

    /**
     * Create a new AI conversation
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createConversation(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'type' => 'nullable|in:general,project_help,task_help',
            'title' => 'nullable|string|max:255',
        ]);

        $project = null;
        if ($request->has('project_id')) {
            $project = Project::find($request->project_id);
            // Verify user has access to project
            if ($project && !$project->users->contains($user->id)) {
                return $this->respondValidationError('You do not have access to this project');
            }
        }

        $conversation = $this->aiConversationService->createConversation(
            $user,
            $project,
            $request->get('type', 'general'),
            $request->get('title')
        );

        $conversation->load(['project:id,name', 'user:id,name,email']);

        return $this->respondWithData($conversation, 'AI conversation created successfully');
    }

    /**
     * Get messages for an AI conversation
     * 
     * @param Request $request
     * @param AIConversation $conversation
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessages(Request $request, AIConversation $conversation)
    {
        $user = $request->user();

        // Verify ownership
        if ($conversation->user_id !== $user->id) {
            return $this->respondValidationError('You do not have access to this conversation');
        }

        $perPage = (int) $request->get('per_page', 50);
        $messages = $this->aiConversationService->getMessages($conversation, $perPage);

        return $this->respondWithData($messages, 'Messages retrieved successfully');
    }

    /**
     * Send a message to AI
     * 
     * @param Request $request
     * @param AIConversation $conversation
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request, AIConversation $conversation)
    {
        $user = $request->user();

        // Verify ownership
        if ($conversation->user_id !== $user->id) {
            return $this->respondValidationError('You do not have access to this conversation');
        }

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        try {
            $result = $this->aiConversationService->sendMessage(
                $conversation,
                $request->message
            );

            $response = [
                'user_message' => $result['user_message'],
                'ai_message' => $result['ai_message'],
            ];
            
            // Include created tasks if any
            if (isset($result['created_tasks']) && !empty($result['created_tasks'])) {
                $response['created_tasks'] = $result['created_tasks'];
            }
            
            // Include created projects if any
            if (isset($result['created_projects']) && !empty($result['created_projects'])) {
                $response['created_projects'] = $result['created_projects'];
            }
            
            return $this->respondWithData($response, 'Message sent successfully');
        } catch (\Exception $e) {
            return $this->respondValidationError('Failed to send message: ' . $e->getMessage());
        }
    }

    /**
     * Delete an AI conversation
     * 
     * @param Request $request
     * @param AIConversation $conversation
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteConversation(Request $request, AIConversation $conversation)
    {
        $user = $request->user();

        // Verify ownership
        if ($conversation->user_id !== $user->id) {
            return $this->respondValidationError('You do not have access to this conversation');
        }

        $deleted = $this->aiConversationService->deleteConversation($conversation);

        if ($deleted) {
            return $this->respondWithData(null, 'Conversation deleted successfully');
        }

        return $this->respondValidationError('Failed to delete conversation');
    }
}
