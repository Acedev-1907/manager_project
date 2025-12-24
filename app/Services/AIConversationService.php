<?php

namespace App\Services;

use App\Events\TaskStatusChanged;
use App\Helpers\LanguageDetector;
use App\Helpers\LocaleHelper;
use App\Models\AIConversation;
use App\Models\AIMessage;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskProgress;
use App\Models\User;
use App\Services\GroqAIService;
use App\Services\TaskService;
use App\Services\ProjectService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AIConversationService
{
    protected GroqAIService $aiService;
    protected TaskService $taskService;
    protected ProjectService $projectService;

    // Constants for regex patterns
    private const CREATE_TASK_PATTERN = '/\[CREATE_TASK\](.*?)\[\/CREATE_TASK\]/is';
    private const CREATE_PROJECT_PATTERN = '/\[CREATE_PROJECT\](.*?)\[\/CREATE_PROJECT\]/is';
    private const KEY_VALIDATION_PATTERN = '/[^a-zA-Z0-9_]/';

    // Common skip words for project name extraction
    private const SKIP_WORDS = ['mới', 'new', 'project', 'dự án', 'đã', 'tạo', 'cho', 'bạn', 'thành công'];

    public function __construct(GroqAIService $aiService, TaskService $taskService, ProjectService $projectService)
    {
        $this->aiService = $aiService;
        $this->taskService = $taskService;
        $this->projectService = $projectService;
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
        // Auto-detect language from user message
        $detectedLocale = LanguageDetector::detectAndUpdateUserLocale($conversation->user, $userMessage);
        
        // Set locale (prioritize detected language, fallback to user preference)
        $locale = $detectedLocale ?: LocaleHelper::getLocale($conversation->user);
        LocaleHelper::setLocale($locale);
        
        return DB::transaction(function () use ($conversation, $userMessage) {
            $locale = app()->getLocale(); // Get locale inside transaction
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
                ->with(['task_progress', 'tasks', 'users'])
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
                    
                    // Get detailed task information
                    $tasksDetail = $tasks->map(function ($task) {
                        return [
                            'id' => $task->id,
                            'name' => $task->name,
                            'content' => $task->content ?? '',
                            'status' => $task->status,
                            'status_text' => $this->getTaskStatusText($task->status),
                        ];
                    })->toArray();
                    
                    // Get project members for task assignment
                    $members = $project->users->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                        ];
                    })->toArray();
                    
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
                        'tasks' => $tasksDetail,
                        'members' => $members,
                    ];
                });
            
            $context['user_projects'] = $userProjects->toArray();
            $context['projects_count'] = $userProjects->count();

            // Get AI response
            $hasError = false;
            $modelUsed = 'unknown';
            $createdTasks = [];
            
            // Check if user is confirming or rejecting a pending action FIRST
            // This avoids unnecessary AI calls when user is just confirming
            $pendingAction = $this->getPendingAction($conversation);
            $isConfirmation = $this->isConfirmationMessage($userMessage);
            $isRejection = $this->isRejectionMessage($userMessage);
            
            try {
                // If user is confirming or rejecting, skip AI call and handle directly
                if ($pendingAction && ($isConfirmation || $isRejection)) {
                    $aiResponse = '';
                    $modelUsed = config('ai.groq_model', 'llama-3.1-8b-instant');
                } else {
                    // Normal flow - call AI service
                    $aiResponse = $this->aiService->chat($messages, $context);
                    
                    // Get model from config
                    $modelUsed = config('ai.groq_model', 'llama-3.1-8b-instant');
                    
                    // Check if response is empty
                    if (empty(trim($aiResponse))) {
                        throw new \Exception(trans('ai.errors.empty_response', [], $locale));
                    }
                }
                
                // Store original response for parsing (before removing format tags)
                $aiResponseOriginal = $aiResponse;
                
                if ($pendingAction) {
                    // Check if pending action is waiting for task name
                    if ($pendingAction['type'] === 'task' && !empty($pendingAction['needs_task_name'])) {
                        // User is providing task name
                        $taskName = trim($userMessage);
                        if (!empty($taskName) && strlen($taskName) > 0) {
                            // Update pending action with task name
                            $pendingAction['data']['name'] = $taskName;
                            unset($pendingAction['needs_task_name']);
                            
                            // If project_id is missing, try to use conversation's project
                            if (empty($pendingAction['data']['project_id']) && $conversation->project_id) {
                                $pendingAction['data']['project_id'] = $conversation->project_id;
                            }
                            
                            // Now ask for confirmation or project name if needed
                            $aiResponse = $this->buildConfirmationPrompt($pendingAction, $conversation);
                            
                            // Save pending action again in case needs_project_name was set
                            $this->savePendingAction($conversation, $pendingAction);
                        } else {
                            // Invalid task name, ask again
                            $aiResponse = "Vui lòng nhập tên task hợp lệ. Tên task không được để trống.";
                        }
                    } elseif ($pendingAction['type'] === 'task' && !empty($pendingAction['needs_project_name'])) {
                        // User is providing project name
                        $projectName = trim($userMessage);
                        if (!empty($projectName) && strlen($projectName) > 0) {
                            // Find project by name from user's projects
                            $project = $this->findProjectByName($conversation->user, $projectName);
                            
                            if ($project) {
                                // Update pending action with project_id
                                $pendingAction['data']['project_id'] = $project->id;
                                unset($pendingAction['needs_project_name']);
                                $this->savePendingAction($conversation, $pendingAction);
                                
                                // Now ask for confirmation with project name
                                $aiResponse = $this->buildConfirmationPrompt($pendingAction, $conversation);
                            } else {
                                // Project not found, ask again
                                $aiResponse = "Không tìm thấy project với tên '{$projectName}'. Vui lòng kiểm tra lại tên project hoặc thử tên khác.";
                            }
                        } else {
                            // Invalid project name, ask again
                            $aiResponse = "Vui lòng nhập tên project hợp lệ. Tên project không được để trống.";
                        }
                    } elseif ($isConfirmation) {
                        // User confirmed, proceed with creation
                        $createdTasks = [];
                        $createdProjects = [];
                        
                        if ($pendingAction['type'] === 'project') {
                            $createdProjects = $this->createProjectFromPendingAction($pendingAction, $conversation);
                        } elseif ($pendingAction['type'] === 'task') {
                            $createdTasks = $this->createTaskFromPendingAction($pendingAction, $conversation);
                        }
                        
                        // Clear pending action
                        $this->clearPendingAction($conversation);
                        
                        // Update AI response to confirm creation
                        if (!empty($createdProjects) || !empty($createdTasks)) {
                            $aiResponse = $this->buildConfirmationResponse($createdProjects, $createdTasks);
                        } else {
                            $aiResponse = trans('ai.response.cancelled', [], $locale);
                        }
                    } elseif ($isRejection) {
                        // User rejected, clear pending action and inform
                        $this->clearPendingAction($conversation);
                        $aiResponse = trans('ai.response.cancelled_with_retry', [], $locale);
                        $createdTasks = [];
                        $createdProjects = [];
                    } else {
                        // User sent other message while pending action exists
                        // Keep pending action and let AI respond normally
                        // But remind user about pending action
                        
                        // Remove CREATE tags from AI response (in case AI included them)
                        $aiResponse = preg_replace(self::CREATE_TASK_PATTERN, '', $aiResponseOriginal);
                        $aiResponse = preg_replace(self::CREATE_PROJECT_PATTERN, '', $aiResponse);
                        $aiResponse = trim($aiResponse);
                        
                        $reminder = $this->buildPendingActionReminder($pendingAction);
                        if (!empty($reminder)) {
                            $aiResponse = $reminder . "\n\n" . $aiResponse;
                        }
                    }
                } else {
                    // Check if AI wants to create something (use original response for detection)
                    $creationIntent = $this->detectCreationIntent($aiResponseOriginal, $userMessage);
                    
                    if ($creationIntent) {
                        // If task intent but missing project_id, try to use conversation's project
                        if ($creationIntent['type'] === 'task' && empty($creationIntent['data']['project_id']) && $conversation->project_id) {
                            $creationIntent['data']['project_id'] = $conversation->project_id;
                        }
                        
                        // Save pending action and ask for confirmation
                        $this->savePendingAction($conversation, $creationIntent);
                        $aiResponse = $this->buildConfirmationPrompt($creationIntent, $conversation);
                        $createdTasks = [];
                        $createdProjects = [];
                    } else {
                        // Normal flow - parse and create if format found (use original response)
                        $createdTasks = $this->parseAndCreateTasks($aiResponseOriginal, $conversation);
                        $createdProjects = $this->parseAndCreateProjects($aiResponseOriginal, $conversation);
                        
                        // Fallback: If AI says "đã tạo" but no format found, try to extract and create
                        if (empty($createdProjects) && $this->isProjectCreationIntent($aiResponseOriginal)) {
                            $fallbackProjects = $this->extractAndCreateProjectFromText($aiResponseOriginal, $conversation);
                            if (!empty($fallbackProjects)) {
                                $createdProjects = array_merge($createdProjects, $fallbackProjects);
                            }
                        }
                        
                        // Remove CREATE tags from response for display (but keep other content)
                        $aiResponse = preg_replace(self::CREATE_TASK_PATTERN, '', $aiResponseOriginal);
                        $aiResponse = preg_replace(self::CREATE_PROJECT_PATTERN, '', $aiResponse);
                        $aiResponse = trim($aiResponse);
                        
                        // If response becomes empty after removing tags, restore original or use default
                        if (empty($aiResponse)) {
                            // Try to extract meaningful text from original response
                            $aiResponse = $this->extractMeaningfulText($aiResponseOriginal);
                            if (empty($aiResponse)) {
                                $aiResponse = trans('ai.response.cancelled', [], $locale);
                            }
                        }
                    }
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
                    'created_tasks' => $createdTasks,
                    'created_projects' => $createdProjects ?? [],
                ],
            ]);

            return [
                'user_message' => $userMsg,
                'ai_message' => $aiMsg,
                'created_tasks' => $createdTasks,
                'created_projects' => $createdProjects ?? [],
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

    /**
     * Get task status text
     * 
     * @param mixed $status
     * @return string
     */
    protected function getTaskStatusText($status): string
    {
        return match($status) {
            0 => 'Chưa bắt đầu',
            1 => 'Đang làm',
            'OK', 'completed' => 'Hoàn thành',
            default => 'Không xác định',
        };
    }

    /**
     * Parse AI response and create tasks if requested
     * 
     * @param string $aiResponse
     * @param AIConversation $conversation
     * @return array Array of created task IDs
     */
    protected function parseAndCreateTasks(string $aiResponse, AIConversation $conversation): array
    {
        $createdTasks = [];
        
        preg_match_all(self::CREATE_TASK_PATTERN, $aiResponse, $matches);
        
        if (empty($matches[1])) {
            return $createdTasks;
        }
        
        Log::debug('AI Response parsing for tasks', [
            'matches_count' => count($matches[1]),
            'conversation_id' => $conversation->id,
        ]);
        
        foreach ($matches[1] as $taskData) {
            try {
                $params = $this->parseTaskParams($taskData);
                
                // Validate required fields
                if (empty($params['project_id']) || empty($params['name'])) {
                    Log::warning('AI task creation: Missing required fields', ['params' => $params]);
                    continue;
                }
                
                $projectId = (int) $params['project_id'];
                $taskName = $params['name'];
                $taskContent = $params['content'] ?? '';
                
                // Verify user has access to project
                $project = Project::find($projectId);
                if (!$project || !$project->users->contains($conversation->user_id)) {
                    Log::warning('AI task creation: User does not have access to project', [
                        'user_id' => $conversation->user_id,
                        'project_id' => $projectId
                    ]);
                    continue;
                }
                
                // Parse and validate member IDs
                $memberIds = $this->parseTaskMemberIds($params['memberIds'] ?? null, $project, $conversation->user_id);
                
                // Create task
                $result = $this->taskService->createTask([
                    'projectId' => $projectId,
                    'name' => $taskName,
                    'content' => $taskContent,
                    'memberIds' => array_values($memberIds),
                ]);
                
                if (isset($result['task'])) {
                    $task = $result['task'];
                    $this->broadcastTaskCreation($task, $projectId, $conversation->user_id);
                    
                    $createdTasks[] = [
                        'id' => $task->id,
                        'name' => $task->name,
                        'project_id' => $projectId,
                    ];
                    Log::info('AI created task successfully', [
                        'task_id' => $task->id,
                        'conversation_id' => $conversation->id,
                    ]);
                }
                
            } catch (\Exception $e) {
                Log::error('AI task creation failed: ' . $e->getMessage(), [
                    'task_data' => $taskData,
                    'conversation_id' => $conversation->id,
                ]);
            }
        }
        
        return $createdTasks;
    }

    /**
     * Parse AI response and create projects if requested
     * 
     * @param string $aiResponse
     * @param AIConversation $conversation
     * @return array Array of created project IDs
     */
    protected function parseAndCreateProjects(string $aiResponse, AIConversation $conversation): array
    {
        $createdProjects = [];
        
        preg_match_all(self::CREATE_PROJECT_PATTERN, $aiResponse, $matches);
        
        if (empty($matches[1])) {
            return $createdProjects;
        }
        
        Log::debug('AI Response parsing for projects', [
            'matches_count' => count($matches[1]),
            'conversation_id' => $conversation->id,
        ]);
        
        foreach ($matches[1] as $projectData) {
            try {
                $projectData = trim($projectData);
                $projectData = preg_replace('/\s+/', ' ', $projectData);
                $params = $this->parseProjectParams($projectData);
                
                // Validate required fields
                if (empty($params['name'])) {
                    Log::warning('AI project creation: Missing required fields', [
                        'params' => $params,
                        'raw_data' => $projectData
                    ]);
                    continue;
                }
                
                $projectName = $params['name'];
                $projectContent = $params['content'] ?? '';
                
                // Validate and set dates
                [$startDate, $endDate] = $this->validateAndSetDates(
                    $params['startDate'] ?? null,
                    $params['endDate'] ?? null
                );
                
                // Parse member IDs
                $memberIds = $this->parseProjectMemberIds($params['members'] ?? null, $conversation->user_id);
                
                $result = $this->projectService->createProject([
                    'name' => $projectName,
                    'content' => $projectContent,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'members' => array_values($memberIds),
                ], $conversation->user);
                
                if (!isset($result['errors'])) {
                    $project = $this->findCreatedProject($projectName, $conversation->user_id);
                    
                    if ($project) {
                        $createdProjects[] = [
                            'id' => $project->id,
                            'name' => $project->name,
                        ];
                        Log::info('AI created project successfully', [
                            'project_id' => $project->id,
                            'project_name' => $project->name,
                            'conversation_id' => $conversation->id,
                        ]);
                    } else {
                        Log::warning('AI project creation: Project not found after creation', [
                            'project_name' => $projectName,
                            'user_id' => $conversation->user_id,
                        ]);
                    }
                } else {
                    Log::warning('AI project creation failed', [
                        'errors' => $result['errors'],
                        'conversation_id' => $conversation->id,
                        'project_name' => $projectName,
                    ]);
                }
                
            } catch (\Exception $e) {
                Log::error('AI project creation exception: ' . $e->getMessage(), [
                    'project_data' => $projectData,
                    'conversation_id' => $conversation->id,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        return $createdProjects;
    }

    /**
     * Check if AI response indicates project creation intent
     * 
     * @param string $aiResponse
     * @return bool
     */
    protected function isProjectCreationIntent(string $aiResponse): bool
    {
        $indicators = [
            'đã tạo.*dự án',
            'đã tạo.*project',
            'dự án.*đã được tạo',
            'project.*đã được tạo',
            'tạo thành công.*dự án',
            'tạo thành công.*project',
        ];
        
        foreach ($indicators as $pattern) {
            if (preg_match('/' . $pattern . '/i', $aiResponse)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Extract project information from AI response text and create project
     * 
     * @param string $aiResponse
     * @param AIConversation $conversation
     * @return array Array of created project IDs
     */
    protected function extractAndCreateProjectFromText(string $aiResponse, AIConversation $conversation): array
    {
        $createdProjects = [];
        
        // Try to extract project name from patterns like:
        // "Dự án mới \"NAME\"" (highest priority - most specific)
        // "Dự án: NAME" 
        // "Tôi đã tạo dự án mới \"NAME\""
        // "project NAME"
        $patterns = [
            // Pattern 1: "Dự án mới \"NAME\"" - most specific with quotes
            '/dự án mới\s+"([^"]+)"/i',
            // Pattern 2: "đã tạo.*dự án.*\"NAME\"" - with quotes
            '/đã tạo.*dự án.*?"([^"]+)"/i',
            // Pattern 3: "Dự án: NAME" (exclude "mới" and other common words)
            '/dự án:\s+([^"\n\-]+?)(?:\s*-\s*ID:|$)/i',
            // Pattern 4: "project NAME"
            '/project[:\s]+"?([^"\n]+)"?/i',
        ];
        
        $projectName = null;
        foreach ($patterns as $index => $pattern) {
            if (preg_match($pattern, $aiResponse, $matches)) {
                $rawExtracted = trim($matches[1]);
                
                Log::debug('AI project creation fallback: Pattern matched', [
                    'pattern_index' => $index,
                    'pattern' => $pattern,
                    'raw_extracted' => $rawExtracted,
                    'full_match' => $matches[0] ?? null,
                ]);
                
                $projectName = $rawExtracted;
                
                // Skip if it's just common words
                $projectNameLower = strtolower(trim($projectName));
                if (in_array($projectNameLower, self::SKIP_WORDS) || strlen($projectNameLower) < 2) {
                    Log::debug('AI project creation fallback: Skipping invalid name', [
                        'extracted' => $projectName,
                        'pattern_index' => $index,
                    ]);
                    continue;
                }
                
                // Remove common suffixes
                $projectName = preg_replace('/\s*-\s*ID:.*$/i', '', $projectName);
                $projectName = trim($projectName);
                
                // Validate project name
                if (!empty($projectName) && strlen($projectName) > 0 && strlen($projectName) < 255) {
                    Log::info('AI project creation fallback: Found valid project name', [
                        'project_name' => $projectName,
                        'pattern_index' => $index,
                        'pattern' => $pattern,
                    ]);
                    break;
                }
            }
        }
        
        if (empty($projectName)) {
            Log::warning('AI project creation fallback: Could not extract project name', [
                'response_preview' => mb_substr($aiResponse, 0, 300),
            ]);
            return $createdProjects;
        }
        
        // Extract description if available
        $description = '';
        if (preg_match('/mô tả[:\s]+([^\n]+)/i', $aiResponse, $matches)) {
            $description = trim($matches[1]);
        }
        
        Log::info('AI project creation fallback: Extracted project info', [
            'project_name' => $projectName,
            'description' => $description,
        ]);
        
        try {
            // Create project with extracted information
            $result = $this->projectService->createProject([
                'name' => $projectName,
                'content' => $description,
                'startDate' => now()->format('Y-m-d'),
                'endDate' => now()->addMonths(3)->format('Y-m-d'),
                'members' => [],
            ], $conversation->user);
            
            if (!isset($result['errors'])) {
                $project = $this->findCreatedProject($projectName, $conversation->user_id);
                
                if ($project) {
                    $createdProjects[] = [
                        'id' => $project->id,
                        'name' => $project->name,
                    ];
                    Log::info('AI created project via fallback successfully', [
                        'project_id' => $project->id,
                        'project_name' => $project->name,
                        'conversation_id' => $conversation->id,
                    ]);
                }
            } else {
                Log::warning('AI project creation fallback failed', [
                    'errors' => $result['errors'],
                    'project_name' => $projectName,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('AI project creation fallback exception: ' . $e->getMessage(), [
                'project_name' => $projectName,
                'conversation_id' => $conversation->id,
            ]);
        }
        
        return $createdProjects;
    }

    /**
     * Detect creation intent from AI response or user message
     * 
     * @param string $aiResponse
     * @param string $userMessage
     * @return array|null
     */
    protected function detectCreationIntent(string $aiResponse, string $userMessage): ?array
    {
        // Check for CREATE_PROJECT format
        if (preg_match(self::CREATE_PROJECT_PATTERN, $aiResponse, $matches)) {
            $projectData = trim($matches[1]);
            $params = $this->parseProjectParams($projectData);
            
            if (!empty($params['name'])) {
                return [
                    'type' => 'project',
                    'data' => $params,
                ];
            }
        }
        
        // Check for CREATE_TASK format
        if (preg_match(self::CREATE_TASK_PATTERN, $aiResponse, $matches)) {
            $taskData = trim($matches[1]);
            $params = $this->parseTaskParams($taskData);
            
            // Always require task name - if missing, ask for it
            if (empty($params['name'])) {
                return [
                    'type' => 'task',
                    'data' => $params,
                    'needs_task_name' => true,
                ];
            }
            
            if (!empty($params['project_id'])) {
                return [
                    'type' => 'task',
                    'data' => $params,
                ];
            }
        }
        
        // Also check user message for task creation intent (without format)
        if (preg_match('/tạo.*task|create.*task|thêm.*task|add.*task/i', $userMessage)) {
            // User wants to create task but hasn't provided name yet
            return [
                'type' => 'task',
                'data' => [],
                'needs_task_name' => true,
            ];
        }
        
        return null;
    }

    /**
     * Check if user message is a confirmation
     * 
     * @param string $userMessage
     * @return bool
     */
    protected function isConfirmationMessage(string $userMessage): bool
    {
        $locale = app()->getLocale();
        $userMessage = trim($userMessage);
        
        // Vietnamese confirmation patterns
        $viPatterns = [
            '/^(đồng ý|ok|okay|yes|tạo|tạo đi|được|chấp nhận|confirm|agree)$/i',
            '/^(có|yes|yep|yup)$/i',
            '/tạo.*đi/i',
            '/đồng ý.*tạo/i',
        ];
        
        // English confirmation patterns
        $enPatterns = [
            '/^(yes|ok|okay|create|agree|confirm|sure|alright)$/i',
            '/^(yep|yup|yeah|y)$/i',
            '/create.*it/i',
            '/go.*ahead/i',
            '/let.*do.*it/i',
        ];
        
        $patterns = $locale === 'vi' ? $viPatterns : $enPatterns;
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $userMessage)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if user message is a rejection
     * 
     * @param string $userMessage
     * @return bool
     */
    protected function isRejectionMessage(string $userMessage): bool
    {
        $locale = app()->getLocale();
        $userMessage = trim($userMessage);
        
        // Vietnamese rejection patterns
        $viPatterns = [
            '/^(không|no|nope|cancel|hủy|hủy bỏ|thôi|không muốn|không cần)$/i',
            '/^(đừng|dừng|stop|không tạo)$/i',
            '/hủy.*tạo/i',
            '/không.*tạo/i',
            '/từ chối/i',
        ];
        
        // English rejection patterns
        $enPatterns = [
            '/^(no|nope|nah|cancel|abort|stop|don\'t|dont)$/i',
            '/^(skip|ignore|nevermind|never mind)$/i',
            '/cancel.*it/i',
            '/don.*t.*create/i',
            '/not.*create/i',
            '/reject/i',
        ];
        
        $patterns = $locale === 'vi' ? $viPatterns : $enPatterns;
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $userMessage)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Build reminder message for pending action
     * 
     * @param array $pendingAction
     * @return string
     */
    protected function buildPendingActionReminder(array $pendingAction): string
    {
        if ($pendingAction['type'] === 'project') {
            $name = $pendingAction['data']['name'] ?? 'dự án';
            return "⚠️ Bạn đang có yêu cầu tạo dự án '{$name}' đang chờ xác nhận. Trả lời 'đồng ý' để tạo hoặc 'không' để hủy.";
        } elseif ($pendingAction['type'] === 'task') {
            $name = $pendingAction['data']['name'] ?? 'task';
            return "⚠️ Bạn đang có yêu cầu tạo task '{$name}' đang chờ xác nhận. Trả lời 'đồng ý' để tạo hoặc 'không' để hủy.";
        }
        
        return '';
    }

    /**
     * Save pending action to conversation metadata
     * 
     * @param AIConversation $conversation
     * @param array $action
     * @return void
     */
    protected function savePendingAction(AIConversation $conversation, array $action): void
    {
        $metadata = $conversation->metadata ?? [];
        $metadata['pending_action'] = $action;
        $metadata['pending_action_at'] = now()->toIso8601String();
        $conversation->update(['metadata' => $metadata]);
    }

    /**
     * Get pending action from conversation
     * 
     * @param AIConversation $conversation
     * @return array|null
     */
    protected function getPendingAction(AIConversation $conversation): ?array
    {
        $metadata = $conversation->metadata ?? [];
        return $metadata['pending_action'] ?? null;
    }

    /**
     * Clear pending action
     * 
     * @param AIConversation $conversation
     * @return void
     */
    protected function clearPendingAction(AIConversation $conversation): void
    {
        $metadata = $conversation->metadata ?? [];
        unset($metadata['pending_action']);
        unset($metadata['pending_action_at']);
        $conversation->update(['metadata' => $metadata]);
    }

    /**
     * Build confirmation prompt
     * 
     * @param array $intent
     * @param AIConversation|null $conversation
     * @return string
     */
    protected function buildConfirmationPrompt(array $intent, ?AIConversation $conversation = null): string
    {
        if ($intent['type'] === 'project') {
            $data = $intent['data'];
            $prompt = "Bạn có muốn tạo dự án mới với thông tin sau không?\n\n";
            $prompt .= "📋 Tên dự án: {$data['name']}\n";
            if (!empty($data['content'])) {
                $prompt .= "📝 Mô tả: {$data['content']}\n";
            }
            if (!empty($data['startDate'])) {
                $prompt .= "📅 Ngày bắt đầu: {$data['startDate']}\n";
            }
            if (!empty($data['endDate'])) {
                $prompt .= "📅 Ngày kết thúc: {$data['endDate']}\n";
            }
            $prompt .= "\nVui lòng trả lời 'đồng ý', 'ok', 'tạo' hoặc 'có' để xác nhận tạo dự án.";
        } elseif ($intent['type'] === 'task') {
            // Check if we need to ask for task name first
            if (!empty($intent['needs_task_name']) || empty($intent['data']['name'])) {
                $prompt = "Bạn muốn tạo task mới. Vui lòng cho tôi biết tên task bạn muốn tạo là gì?";
            } else {
                $data = $intent['data'];
                
                // Check if project_id is missing
                if (empty($data['project_id'])) {
                    // Try to use conversation's project if available
                    if ($conversation && $conversation->project_id) {
                        $data['project_id'] = $conversation->project_id;
                    } else {
                        // Need to ask for project name
                        $intent['needs_project_name'] = true;
                        $prompt = "Bạn muốn tạo task '{$data['name']}'. Vui lòng cho tôi biết tên project bạn muốn tạo task này?";
                        return $prompt;
                    }
                }
                
                $prompt = "Bạn có muốn tạo task mới với thông tin sau không?\n\n";
                $prompt .= "📋 Tên task: {$data['name']}\n";
                if (!empty($data['content'])) {
                    $prompt .= "📝 Mô tả: {$data['content']}\n";
                }
                if (!empty($data['project_id'])) {
                    // Get project name for display
                    $project = Project::find($data['project_id']);
                    if ($project) {
                        $prompt .= "📁 Project: {$project->name}\n";
                    } else {
                        $prompt .= "📁 Project ID: {$data['project_id']}\n";
                    }
                }
                $prompt .= "\nVui lòng trả lời 'đồng ý', 'ok', 'tạo' hoặc 'có' để xác nhận tạo task.";
            }
        } else {
            $prompt = "Bạn có muốn tiếp tục không?";
        }
        
        return $prompt;
    }

    /**
     * Build confirmation response after creation
     * 
     * @param array $createdProjects
     * @param array $createdTasks
     * @return string
     */
    protected function buildConfirmationResponse(array $createdProjects, array $createdTasks): string
    {
        $locale = app()->getLocale();
        $response = "";
        
        if (!empty($createdProjects)) {
            foreach ($createdProjects as $project) {
                $response .= trans('ai.response.project_created', [], $locale) . "\n";
                $response .= trans('ai.response.project_name', ['name' => $project['name']], $locale) . "\n";
                $response .= trans('ai.response.project_id', ['id' => $project['id']], $locale) . "\n\n";
            }
        }
        
        if (!empty($createdTasks)) {
            foreach ($createdTasks as $task) {
                $response .= trans('ai.response.task_created', [], $locale) . "\n";
                $response .= trans('ai.response.task_name', ['name' => $task['name']], $locale) . "\n";
                $response .= trans('ai.response.task_id', ['id' => $task['id']], $locale) . "\n\n";
            }
        }
        
        return trim($response);
    }

    /**
     * Create project from pending action
     * 
     * @param array $pendingAction
     * @param AIConversation $conversation
     * @return array
     */
    protected function createProjectFromPendingAction(array $pendingAction, AIConversation $conversation): array
    {
        $data = $pendingAction['data'];
        $createdProjects = [];
        
        try {
            $projectName = $data['name'];
            $projectContent = $data['content'] ?? '';
            
            // Validate and set dates
            [$startDate, $endDate] = $this->validateAndSetDates(
                $data['startDate'] ?? null,
                $data['endDate'] ?? null
            );
            
            $memberIds = $this->parseProjectMemberIds($data['members'] ?? null, $conversation->user_id);
            
            $result = $this->projectService->createProject([
                'name' => $projectName,
                'content' => $projectContent,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'members' => array_values($memberIds),
            ], $conversation->user);
            
                if (!isset($result['errors'])) {
                    $project = $this->findCreatedProject($projectName, $conversation->user_id);
                    
                    if ($project) {
                        $createdProjects[] = [
                            'id' => $project->id,
                            'name' => $project->name,
                        ];
                    }
                }
        } catch (\Exception $e) {
            Log::error('AI create project from pending action failed: ' . $e->getMessage());
        }
        
        return $createdProjects;
    }

    /**
     * Create task from pending action
     * 
     * @param array $pendingAction
     * @param AIConversation $conversation
     * @return array
     */
    protected function createTaskFromPendingAction(array $pendingAction, AIConversation $conversation): array
    {
        $data = $pendingAction['data'];
        $createdTasks = [];
        
        try {
            $projectId = (int) $data['project_id'];
            $taskName = $data['name'];
            $taskContent = $data['content'] ?? '';
            
            // Verify user has access to project
            $project = Project::find($projectId);
            if (!$project || !$project->users->contains($conversation->user_id)) {
                return $createdTasks;
            }
            
            $memberIds = $this->parseTaskMemberIds($data['memberIds'] ?? null, $project, $conversation->user_id);
            
            $result = $this->taskService->createTask([
                'projectId' => $projectId,
                'name' => $taskName,
                'content' => $taskContent,
                'memberIds' => array_values($memberIds),
            ]);
            
            if (isset($result['task'])) {
                $task = $result['task'];
                $this->broadcastTaskCreation($task, $projectId, $conversation->user_id);
                
                $createdTasks[] = [
                    'id' => $task->id,
                    'name' => $task->name,
                    'project_id' => $projectId,
                ];
            }
        } catch (\Exception $e) {
            Log::error('AI create task from pending action failed: ' . $e->getMessage());
        }
        
        return $createdTasks;
    }

    /**
     * Parse parameters from format string (shared for both task and project)
     * 
     * @param string $data Format: key1=value1|key2=value2
     * @param bool $validateKey Whether to validate key format
     * @return array
     */
    protected function parseParams(string $data, bool $validateKey = true): array
    {
        $params = [];
        $parts = explode('|', trim($data));
        
        foreach ($parts as $part) {
            $part = trim($part);
            if (strpos($part, '=') === false) continue;
            
            [$key, $value] = explode('=', $part, 2);
            $key = trim($key);
            $value = trim($value);
            
            if ($validateKey && (empty($key) || preg_match(self::KEY_VALIDATION_PATTERN, $key))) {
                continue;
            }
            
            $params[$key] = $value;
        }
        
        return $params;
    }

    /**
     * Parse project parameters from format string
     * 
     * @param string $projectData
     * @return array
     */
    protected function parseProjectParams(string $projectData): array
    {
        return $this->parseParams($projectData, true);
    }

    /**
     * Parse task parameters from format string
     * 
     * @param string $taskData
     * @return array
     */
    protected function parseTaskParams(string $taskData): array
    {
        return $this->parseParams($taskData, false);
    }

    /**
     * Extract meaningful text from response after removing tags
     * 
     * @param string $response
     * @return string
     */
    protected function extractMeaningfulText(string $response): string
    {
        // Remove CREATE tags
        $text = preg_replace(self::CREATE_TASK_PATTERN, '', $response);
        $text = preg_replace(self::CREATE_PROJECT_PATTERN, '', $text);
        
        // Remove empty lines and trim
        $text = preg_replace('/\n\s*\n/', '\n', $text);
        $text = trim($text);
        
        // If still has content, return it
        if (!empty($text) && strlen($text) > 10) {
            return $text;
        }
        
        return '';
    }

    /**
     * Find project by name from user's projects
     * 
     * @param User $user
     * @param string $projectName
     * @return Project|null
     */
    protected function findProjectByName(User $user, string $projectName): ?Project
    {
        $projectName = trim($projectName);
        $projects = $user->projects()
            ->where('name', 'like', '%' . $projectName . '%')
            ->get();
        
        if ($projects->isEmpty()) {
            return null;
        }
        
        // Try exact match first
        $exactMatch = $projects->firstWhere('name', $projectName);
        if ($exactMatch) {
            return $exactMatch;
        }
        
        // Try case-insensitive match
        $caseInsensitiveMatch = $projects->first(function ($project) use ($projectName) {
            return strcasecmp($project->name, $projectName) === 0;
        });
        
        return $caseInsensitiveMatch ?: $projects->first();
    }

    /**
     * Broadcast task creation event with progress data
     * 
     * @param Task $task
     * @param int $projectId
     * @param int $userId
     * @return void
     */
    protected function broadcastTaskCreation(Task $task, int $projectId, int $userId): void
    {
        try {
            $task->load(['task_members.user']);
            $progressData = Task::handleProjectProgress($projectId, $userId, $task->id, false);
            
            broadcast(new TaskStatusChanged(
                $task,
                $projectId,
                $task->status,
                $userId,
                $progressData['progress'] ?? 0,
                $progressData['counts'] ?? [0, 0]
            ))->toOthers();
        } catch (\Exception $e) {
            Log::warning('Failed to broadcast task creation: ' . $e->getMessage());
        }
    }

    /**
     * Find created project by name and creator
     * 
     * @param string $projectName
     * @param int $userId
     * @return Project|null
     */
    protected function findCreatedProject(string $projectName, int $userId): ?Project
    {
        return Project::where('creator_id', $userId)
            ->where('name', $projectName)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Parse and validate member IDs for project
     * 
     * @param string|null $membersString
     * @param int $currentUserId
     * @return array
     */
    protected function parseProjectMemberIds(?string $membersString, int $currentUserId): array
    {
        if (empty($membersString)) {
            return [];
        }
        
        $memberIds = array_map('intval', explode(',', $membersString));
        return array_filter($memberIds, function($id) use ($currentUserId) {
            return $id != $currentUserId;
        });
    }

    /**
     * Parse and validate member IDs for task
     * 
     * @param string|null $memberIdsString
     * @param Project $project
     * @param int $currentUserId
     * @return array
     */
    protected function parseTaskMemberIds(?string $memberIdsString, Project $project, int $currentUserId): array
    {
        if (empty($memberIdsString)) {
            return [$currentUserId];
        }
        
        $memberIds = array_map('intval', explode(',', $memberIdsString));
        $memberIds = array_filter($memberIds, function($id) use ($project) {
            return $project->users->contains($id);
        });
        
        return empty($memberIds) ? [$currentUserId] : array_values($memberIds);
    }

    /**
     * Validate and set project dates with defaults
     * 
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array [startDate, endDate]
     */
    protected function validateAndSetDates(?string $startDate, ?string $endDate): array
    {
        // Set defaults if not provided
        $startDate = !empty($startDate) ? $startDate : now()->format('Y-m-d');
        $endDate = !empty($endDate) ? $endDate : now()->addMonths(3)->format('Y-m-d');
        
        // Validate date format
        try {
            $startDateObj = \Carbon\Carbon::parse($startDate);
            $endDateObj = \Carbon\Carbon::parse($endDate);
            
            // Ensure end date is after start date
            if ($endDateObj->lt($startDateObj)) {
                $endDate = $startDateObj->copy()->addMonths(3)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            // If date parsing fails, use defaults
            Log::warning('AI project creation: Invalid date format, using defaults', [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'error' => $e->getMessage(),
            ]);
            $startDate = now()->format('Y-m-d');
            $endDate = now()->addMonths(3)->format('Y-m-d');
        }
        
        return [$startDate, $endDate];
    }
}

