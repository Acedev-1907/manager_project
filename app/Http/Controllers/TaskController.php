<?php

namespace App\Http\Controllers;

use App\Events\TaskCommentCreated;
use App\Models\Task;
use App\Services\TaskService;
use App\Services\TaskCommentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
}
