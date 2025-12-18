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

            // Gửi event gộp sau khi tạo mới để Dashboard cập nhật
            try {
                $progressData = Task::handleProjectProgress($taskDTO->projectId, Auth::id(), $result['task']->id, false);
                broadcast(new TaskStatusChanged(
                    $result['task'], 
                    $taskDTO->projectId, 
                    $result['task']->status, 
                    Auth::id(), 
                    $progressData['progress'], 
                    $progressData['counts']
                ))->toOthers();
            } catch (\Exception $e) { }
            
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

        $data = $req->all();
        $data['userId'] = Auth::id();

        if ($this->taskService->updateTaskStatus($data, $newStatus)) {
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
            $newStatus = is_numeric($status) ? (int)$status : $status;

            if (is_numeric($newStatus) && $newStatus < 0) {
                return $this->respondValidationError('Invalid status: ' . $status);
            } elseif (!is_numeric($newStatus) && $status !== 'OK') {
                return $this->respondValidationError('Invalid status: ' . $status);
            }

            $data = $req->all();
            $data['userId'] = Auth::id();

            if (!isset($data['taskId']) || !isset($data['projectId'])) {
                return $this->respondValidationError('Task ID and Project ID are required');
            }

            if ($this->taskService->updateTaskStatus($data, $newStatus)) {
                $statusNames = [0 => 'Not Started', 1 => 'Pending', 'OK' => 'Completed'];
                $statusName = $statusNames[$newStatus] ?? "Column {$newStatus}";
                return $this->respondUpdated("Task moved to {$statusName}");
            }

            return $this->respondServerError('Failed to update task status');
        } catch (\Exception $e) {
            Log::error('Error in transitionToStatus: ' . $e->getMessage());
            return $this->setStatusCode(500)->respondWithError('Failed to transition task status');
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
        $task = Task::find($id);
        $projectId = $task?->projectId;
        $result = $this->taskService->deleteTask($id);
        
        if (isset($result['errors'])) {
            return $this->respondNotFound($result['errors'][0] ?? 'Task not found');
        }

        // Gửi event gộp sau khi xóa để Dashboard cập nhật
        if ($projectId) {
            try {
                $progressData = Task::handleProjectProgress($projectId, Auth::id(), null, false);
                broadcast(new TaskStatusChanged(
                    null, 
                    $projectId, 
                    null, 
                    Auth::id(), 
                    $progressData['progress'], 
                    $progressData['counts']
                ))->toOthers();
            } catch (\Exception $e) { }
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
        
        // Load user relationship trước khi broadcast để đảm bảo data đầy đủ
        $comment->load('user');
        
        // Broadcast realtime event TaskStatusChanged để cập nhật bình luận (giống như kéo thả)
        // Hoặc giữ TaskCommentCreated nhưng đảm bảo data gọn nhẹ. 
        // Ở đây ta giữ TaskCommentCreated nhưng tối ưu broadcast data.
        try {
            $event = new TaskCommentCreated($comment, $id);
            broadcast($event)->toOthers();
        } catch (\Exception $e) {
            Log::error('Failed to broadcast TaskCommentCreated', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'taskId' => $id,
                'commentId' => $comment->id
            ]);
        }
        
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

            if (!$taskId || !$projectId) {
                return $this->respondValidationError('Task ID and Project ID are required');
            }

            broadcast(new TaskDragStarted(
                $taskId,
                $projectId,
                $user?->id,
                $user?->name,
                $user?->avatar
            ))->toOthers();

            return $this->respondWithMessage('Drag started event broadcasted');
        } catch (\Exception $e) {
            return $this->setStatusCode(500)->respondWithError('Failed to broadcast drag started event');
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

            if (!$taskId || !$projectId) {
                return $this->respondValidationError('Task ID and Project ID are required');
            }

            broadcast(new TaskDragEnded($taskId, $projectId, $user?->id))->toOthers();

            return $this->respondWithMessage('Drag ended event broadcasted');
        } catch (\Exception $e) {
            return $this->setStatusCode(500)->respondWithError('Failed to broadcast drag ended event');
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

            if (!$taskId || !$projectId || !$columnId || $columnStatus === null) {
                return $this->respondValidationError('Task ID, Project ID, Column ID and Column Status are required');
            }

            broadcast(new TaskDragOverColumn(
                $taskId,
                $projectId,
                $columnId,
                $columnStatus,
                $user?->id,
                $user?->name,
                $user?->avatar
            ))->toOthers();

            return $this->respondWithMessage('Drag over column event broadcasted');
        } catch (\Exception $e) {
            return $this->setStatusCode(500)->respondWithError('Failed to broadcast drag over column event');
        }
    }
}