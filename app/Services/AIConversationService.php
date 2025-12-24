<?php

namespace App\Services;

use App\Models\AIConversation;
use App\Models\AIMessage;
use App\Models\Project;
use App\Models\TaskProgress;
use App\Models\User;
use App\Services\GroqAIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AIConversationService
{
    protected GroqAIService $aiService;

    public function __construct(GroqAIService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Create a new AI conversation
     * 
     * @param User $user
     * @param Project|null $project
     * @param string $type
     * @param string|null $title
     * @return AIConversation
     */
    public function createConversation(User $user, ?Project $project = null, string $type = 'general', ?string $title = null): AIConversation
    {
        return AIConversation::create([
            'user_id' => $user->id,
            'project_id' => $project?->id,
            'type' => $type,
            'title' => $title ?? $this->generateDefaultTitle($type, $project),
        ]);
    }

    /**
     * Send a message to AI and get response
     * 
     * @param AIConversation $conversation
     * @param string $userMessage
     * @return array
     */
    public function sendMessage(AIConversation $conversation, string $userMessage): array
    {
        return DB::transaction(function () use ($conversation, $userMessage) {
            // Save user message
            $userMsg = AIMessage::create([
                'ai_conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $userMessage,
            ]);

            // Get conversation history (limit to last 10 messages to reduce token usage)
            $history = $conversation->messages()
                ->where('id', '!=', $userMsg->id) // Exclude the newly created user message
                ->orderBy('created_at', 'asc')
                ->limit(10)
                ->get();
            
            // Build messages for AI - include all history messages
            $messages = $history->map(function ($msg) {
                return [
                    'role' => $msg->role === 'assistant' ? 'assistant' : 'user',
                    'content' => $msg->content
                ];
            })->toArray();
            
            // Add the new user message to the end
            $messages[] = [
                'role' => 'user',
                'content' => $userMessage
            ];

            // Build context with user's projects information
            $context = [];
            if ($conversation->project) {
                $context['project'] = $conversation->project;
            }
            $context['user'] = $conversation->user;
            
            // Load user's projects for AI to analyze
            $userProjects = $conversation->user->projects()
                ->with(['task_progress', 'tasks'])
                ->get()
                ->map(function ($project) use ($conversation) {
                    $tasks = $project->tasks;
                    $completedTasks = $tasks->where('status', 'completed')->count();
                    $totalTasks = $tasks->count();
                    
                    // Check if project is pinned for this user
                    $taskProgress = TaskProgress::where('projectId', $project->id)
                        ->where('user_id', $conversation->user->id)
                        ->first();
                    $isPinned = $taskProgress && $taskProgress->pinned_on_dashboard == TaskProgress::PINNED_ON_DASHBOARD;
                    
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'status' => $project->status,
                        'description' => $project->content ?? $project->description ?? '',
                        'start_date' => $project->start_date?->format('Y-m-d'),
                        'end_date' => $project->end_date?->format('Y-m-d'),
                        'total_tasks' => $totalTasks,
                        'completed_tasks' => $completedTasks,
                        'progress_percentage' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0,
                        'is_pinned' => $isPinned,
                    ];
                });
            
            $context['user_projects'] = $userProjects->toArray();
            $context['projects_count'] = $userProjects->count();

            // Get AI response
            $hasError = false;
            $modelUsed = 'unknown';
            try {
                $aiResponse = $this->aiService->chat($messages, $context);
                
                // Get model from config
                $modelUsed = config('ai.groq_model', 'llama-3.1-8b-instant');
                
                // Check if response is empty
                if (empty(trim($aiResponse))) {
                    throw new \Exception('AI trả về phản hồi rỗng. Vui lòng thử lại.');
                }
            } catch (\Exception $e) {
                // Save error message as AI response so user knows what happened
                $aiResponse = $e->getMessage();
                $hasError = true;
                Log::error('Groq AI Service Error in sendMessage: ' . $e->getMessage(), [
                    'conversation_id' => $conversation->id,
                    'exception' => get_class($e),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            // Save AI response
            $aiMsg = AIMessage::create([
                'ai_conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $aiResponse,
                'metadata' => [
                    'model' => $modelUsed,
                    'provider' => 'groq',
                    'timestamp' => now()->toIso8601String(),
                    'error' => $hasError,
                ],
            ]);

            return [
                'user_message' => $userMsg,
                'ai_message' => $aiMsg,
            ];
        });
    }

    /**
     * Get conversation history
     * 
     * @param AIConversation $conversation
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getConversationHistory(AIConversation $conversation, int $limit = 50)
    {
        return $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get conversations for a user
     * 
     * @param User $user
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getConversations(User $user, int $perPage = 20)
    {
        return AIConversation::where('user_id', $user->id)
            ->with(['project:id,name', 'lastMessage'])
            ->orderBy('updated_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get messages for a conversation
     * 
     * @param AIConversation $conversation
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getMessages(AIConversation $conversation, int $perPage = 50)
    {
        return $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }

    /**
     * Delete a conversation
     * 
     * @param AIConversation $conversation
     * @return bool
     */
    public function deleteConversation(AIConversation $conversation): bool
    {
        try {
            return $conversation->delete();
        } catch (\Exception $e) {
            Log::error('Delete AI Conversation Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate default title for conversation
     * 
     * @param string $type
     * @param Project|null $project
     * @return string
     */
    protected function generateDefaultTitle(string $type, ?Project $project): string
    {
        $prefix = match($type) {
            'project_help' => 'Hỗ trợ dự án',
            'task_help' => 'Hỗ trợ task',
            default => 'Chat với AI',
        };

        if ($project) {
            return "{$prefix}: {$project->name}";
        }

        return $prefix;
    }
}

