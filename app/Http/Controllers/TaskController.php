<?php

namespace App\Http\Controllers;

use App\Events\TaskCommentCreated;
use App\Events\TaskDragStarted;
use App\Events\TaskDragEnded;
use App\Events\TaskDragOverColumn;
use App\Models\Task;
use App\Services\TaskService;
use App\Services\TaskCommentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\ApiController;

/**
 * Task Controller
 * 
 * Handles all task-related operations including CRUD operations,
 * status transitions, and task comments.
 */
class TaskController extends ApiController
{
    public function __construct(
        private TaskService $taskService,
        private TaskCommentService $taskCommentService
    ) {}

    /**
     * Create task (Legacy - array based)
     * 
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTask(Request $req)
    {
        $result = $this->taskService->createTask($req->all());
        
        if (isset($result['errors'])) {
            return $this->respondValidationError($result['errors']);
        }
        
        return $this->respondCreated($result['message'], $result['task']->id);
    }

    /**
     * Create task with DTO (New - recommended)
     * 
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTaskWithDTO(Request $req)
    {
        try {
            $taskDTO = \App\DTOs\TaskDTO::fromArray($req->all());
            
            // Validate DTO
            if (empty($taskDTO->title) || empty($taskDTO->projectId)) {
                return $this->respondValidationError('Title and projectId are required');
            }
            
            // Use DTO in service
            $result = $this->taskService->createTaskWithDTO($taskDTO);
            
            if (isset($result['errors'])) {
                return $this->respondValidationError($result['errors']);
            }
            
            return $this->respondCreated($result['message'], $result['task']->id);
            
        } catch (\Exception $e) {
            return $this->respondValidationError($e->getMessage());
        }
    }

    /**
     * Transition task status
     * 
     * @param Request $req
     * @param string $transition
     * @return \Illuminate\Http\JsonResponse
     */
    public function transition(Request $req, string $transition)
    {
        $transitions = [
            'not_started_to_pending' => [Task::PENDING, 'Pending'],
            'not_started_to_completed' => [Task::COMPLETED, 'Completed'],
            'pending_to_completed' => [Task::COMPLETED, 'Completed'],
            'pending_to_not_started' => [Task::NOT_STARTED, 'Not Started'],
            'completed_to_pending' => [Task::PENDING, 'Pending'],
            'completed_to_not_started' => [Task::NOT_STARTED, 'Not Started'],
        ];

        if (!isset($transitions[$transition])) {
            return $this->respondNotFound('Unknown transition: ' . $transition);
        }

        [$newStatus, $nameStatus] = $transitions[$transition];

        // Add userId to the data
        $data = $req->all();
        $data['userId'] = Auth::id();

        $checkUpdate = $this->taskService->updateTaskStatus($data, $newStatus);

        if ($checkUpdate) {
            return $this->respondUpdated("Task status updated to {$nameStatus}");
        }

        return $this->respondServerError('Failed to update task status');
    }

    /**
     * Transition task to specific status
     * 
     * @param Request $req
     * @param string $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function transitionToStatus(Request $req, string $status)
    {
        try {
            // Handle both numeric and string status
            $newStatus = $status;

            // If status is numeric, convert to int for validation
            if (is_numeric($status)) {
                $newStatus = (int)$status;
                // Validate status range (allow any non-negative integer)
                if ($newStatus < 0) {
                    return $this->respondValidationError('Invalid status: ' . $status);
                }
            } else {
                // For string status, only allow 'OK' for completed
                if ($status !== 'OK') {
                    return $this->respondValidationError('Invalid status: ' . $status);
                }
            }

            // Add userId to the data
            $data = $req->all();
            $data['userId'] = Auth::id();

            if (!isset($data['taskId']) || !isset($data['projectId'])) {
                return $this->respondValidationError('Task ID and Project ID are required');
            }

            $checkUpdate = $this->taskService->updateTaskStatus($data, $newStatus);

            if ($checkUpdate) {
                // Map status to column name for response
                $statusNames = [
                    0 => 'Not Started',
                    1 => 'Pending',
                    2 => 'Column 2',
                    3 => 'Column 3',
                    4 => 'Column 4',
                    'OK' => 'Completed'
                ];
                $statusName = $statusNames[$newStatus] ?? "Column {$newStatus}";
                return $this->respondUpdated("Task moved to {$statusName}");
            }

            return $this->respondServerError('Failed to update task status');
        } catch (\Exception $e) {
            Log::error('Error in transitionToStatus: ' . $e->getMessage(), [
                'status' => $status,
                'data' => $req->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->setStatusCode(500)
                ->setReturnCode(self::ERROR_INTERNAL)
                ->respondWithError('Failed to transition task status');
        }
    }

    /**
     * Delete a task
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        $result = $this->taskService->deleteTask($id);
        
        if (isset($result['errors'])) {
            return $this->respondNotFound($result['errors'][0] ?? 'Task not found');
        }
        
        return $this->respondDeleted($result['message']);
    }

    /**
     * Get the list of comments for a task
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTaskComments(int $id)
    {
        $comments = $this->taskCommentService->getCommentsByTask($id);
        return $this->respondWithData(['comments' => $comments], 'Comments retrieved successfully');
    }

    /**
     * Add a new comment to a task
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addTaskComment(Request $request, int $id)
    {
        $userId = Auth::id();
        $content = $request->input('content');
        
        if (!$content) {
            return $this->respondValidationError('Content is required');
        }
        
        $comment = $this->taskCommentService->createComment($id, $userId, $content);
        
        // Broadcast realtime event
        event(new TaskCommentCreated($comment, $id));
        
        return $this->respondCreated('Comment added successfully', $comment->id);
    }

    /**
     * Broadcast task drag started event
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function broadcastDragStarted(Request $request)
    {
        try {
            $user = $request->user();
            $taskId = $request->input('task_id');
            $projectId = $request->input('project_id');

            // Debug log để kiểm tra payload và user
            Log::info('DragStarted request', [
                'task_id' => $taskId,
                'project_id' => $projectId,
                'user_id' => $user?->id,
                'ip' => $request->ip(),
            ]);

            if (!$taskId || !$projectId) {
                return $this->respondValidationError('Task ID and Project ID are required');
            }

            // Verify user has access to project
            $task = Task::find($taskId);
            if (!$task || $task->projectId != $projectId) {
                Log::warning('DragStarted task not found or not in project', [
                    'task_id' => $taskId,
                    'project_id' => $projectId,
                    'task_project' => $task?->projectId,
                ]);
                // Vẫn broadcast tối thiểu để FE hiển thị overlay
                try {
                    broadcast(new TaskDragStarted(
                        $taskId,
                        $projectId,
                        $user?->id,
                        $user?->name,
                        $user?->avatar
                    ));
                } catch (\Exception $e) {
                    Log::warning('Failed to broadcast drag started (task not found): ' . $e->getMessage());
                }
                return $this->respondWithMessage('Task not found or not in project (drag-started broadcasted minimal)');
            }

            // Broadcast event
            try {
                broadcast(new TaskDragStarted(
                    $taskId,
                    $projectId,
                    $user->id,
                    $user->name,
                    $user->avatar
                ));
            } catch (\Exception $e) {
                Log::error('Failed to broadcast drag started: ' . $e->getMessage(), [
                    'task_id' => $taskId,
                    'project_id' => $projectId,
                    'user_id' => $user->id,
                ]);
                // Vẫn trả về success để FE không bị block
                return $this->respondWithMessage('Drag started event (broadcast may have failed)');
            }

            return $this->respondWithMessage('Drag started event broadcasted');
        } catch (\Exception $e) {
            Log::error('Error in broadcastDragStarted: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->setStatusCode(500)
                ->setReturnCode(self::ERROR_INTERNAL)
                ->respondWithError('Failed to broadcast drag started event');
        }
    }

    /**
     * Broadcast task drag ended event
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function broadcastDragEnded(Request $request)
    {
        try {
            $user = $request->user();
            $taskId = $request->input('task_id');
            $projectId = $request->input('project_id');

            // Debug log để kiểm tra payload và user
            Log::info('DragEnded request', [
                'task_id' => $taskId,
                'project_id' => $projectId,
                'user_id' => $user?->id,
                'ip' => $request->ip(),
            ]);

            if (!$taskId || !$projectId) {
                return $this->respondValidationError('Task ID and Project ID are required');
            }

            // Verify user has access to project
            $task = Task::find($taskId);
            if (!$task || $task->projectId != $projectId) {
                Log::warning('DragEnded task not found or not in project', [
                    'task_id' => $taskId,
                    'project_id' => $projectId,
                    'task_project' => $task?->projectId,
                ]);
                // Vẫn broadcast tối thiểu để FE gỡ overlay
                try {
                    broadcast(new TaskDragEnded($taskId, $projectId, $user?->id));
                } catch (\Exception $e) {
                    Log::warning('Failed to broadcast drag ended (task not found): ' . $e->getMessage());
                }
                return $this->respondWithMessage('Task not found or not in project (drag-ended broadcasted minimal)');
            }

            // Broadcast event
            try {
                broadcast(new TaskDragEnded($taskId, $projectId, $user->id));
            } catch (\Exception $e) {
                Log::error('Failed to broadcast drag ended: ' . $e->getMessage(), [
                    'task_id' => $taskId,
                    'project_id' => $projectId,
                    'user_id' => $user->id,
                ]);
                // Vẫn trả về success để FE không bị block
                return $this->respondWithMessage('Drag ended event (broadcast may have failed)');
            }

            return $this->respondWithMessage('Drag ended event broadcasted');
        } catch (\Exception $e) {
            Log::error('Error in broadcastDragEnded: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->setStatusCode(500)
                ->setReturnCode(self::ERROR_INTERNAL)
                ->respondWithError('Failed to broadcast drag ended event');
        }
    }

    /**
     * Broadcast task drag over column event
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function broadcastDragOverColumn(Request $request)
    {
        try {
            $user = $request->user();
            $taskId = $request->input('task_id');
            $projectId = $request->input('project_id');
            $columnId = $request->input('column_id');
            $columnStatus = $request->input('column_status');

            // Debug log để kiểm tra payload và user
            Log::info('DragOverColumn request', [
                'task_id' => $taskId,
                'project_id' => $projectId,
                'column_id' => $columnId,
                'column_status' => $columnStatus,
                'user_id' => $user?->id,
                'ip' => $request->ip(),
            ]);

            if (!$taskId || !$projectId || !$columnId || $columnStatus === null) {
                return $this->respondValidationError('Task ID, Project ID, Column ID and Column Status are required');
            }

            // Verify user has access to project
            $task = Task::find($taskId);
            if (!$task || $task->projectId != $projectId) {
                Log::warning('DragOverColumn task not found or not in project', [
                    'task_id' => $taskId,
                    'project_id' => $projectId,
                    'task_project' => $task?->projectId,
                ]);
                // Vẫn broadcast tối thiểu để FE highlight cột
                try {
                    broadcast(new TaskDragOverColumn(
                        $taskId,
                        $projectId,
                        $columnId,
                        $columnStatus,
                        $user?->id,
                        $user?->name,
                        $user?->avatar
                    ));
                } catch (\Exception $e) {
                    Log::warning('Failed to broadcast drag over column (task not found): ' . $e->getMessage());
                }
                return $this->respondWithMessage('Task not found or not in project (drag-over broadcasted minimal)');
            }

            // Broadcast event
            try {
                broadcast(new TaskDragOverColumn(
                    $taskId,
                    $projectId,
                    $columnId,
                    $columnStatus,
                    $user->id,
                    $user->name,
                    $user->avatar
                ));
            } catch (\Exception $e) {
                Log::error('Failed to broadcast drag over column: ' . $e->getMessage(), [
                    'task_id' => $taskId,
                    'project_id' => $projectId,
                    'column_id' => $columnId,
                    'user_id' => $user->id,
                ]);
                // Vẫn trả về success để FE không bị block
                return $this->respondWithMessage('Drag over column event (broadcast may have failed)');
            }

            return $this->respondWithMessage('Drag over column event broadcasted');
        } catch (\Exception $e) {
            Log::error('Error in broadcastDragOverColumn: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->setStatusCode(500)
                ->setReturnCode(self::ERROR_INTERNAL)
                ->respondWithError('Failed to broadcast drag over column event');
        }
    }
}