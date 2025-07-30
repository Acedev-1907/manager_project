<?php

namespace App\Http\Controllers;

use App\Events\TaskCommentCreated;
use App\Models\Task;
use App\Services\TaskService;
use App\Services\TaskCommentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{

    public function __construct(private TaskService $taskService, private TaskCommentService $taskCommentService)
    {
        $this->taskCommentService = $taskCommentService;
    }

    public function createTask(Request $req)
    {
        $result = $this->taskService->createTask($req->all());
        if (isset($result['errors'])) {
            return response($result['errors'], $result['status']);
        }
        return response([
            'message' => $result['message'],
            'task' => $result['task']
        ], $result['status']);
    }

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
            return response(['error' => 'Unknown transition: ' . $transition], 404);
        }

        [$newStatus, $nameStatus] = $transitions[$transition];

        // Add userId to the data
        $data = $req->all();
        $data['userId'] = Auth::id();

        $checkUpdate = $this->taskService->updateTaskStatus($data, $newStatus);

        if ($checkUpdate) {
            return response(['message' => "Task status updated to {$nameStatus}"], 200);
        }

        return response(['error' => 'Failed to update task status'], 500);
    }

    public function transitionToStatus(Request $req, string $status)
    {
        // Handle both numeric and string status
        $newStatus = $status;

        // If status is numeric, convert to int for validation
        if (is_numeric($status)) {
            $newStatus = (int)$status;
            // Validate status range (allow any non-negative integer)
            if ($newStatus < 0) {
                return response(['error' => 'Invalid status: ' . $status], 400);
            }
        } else {
            // For string status, only allow 'OK' for completed
            if ($status !== 'OK') {
                return response(['error' => 'Invalid status: ' . $status], 400);
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
            return response(['message' => "Task moved to {$statusName}"], 200);
        }

        return response(['error' => 'Failed to update task status'], 500);
    }

    public function destroy($id)
    {
        $result = $this->taskService->deleteTask($id);
        if (isset($result['errors'])) {
            return response($result['errors'], $result['status']);
        }
        return response(['message' => $result['message']], $result['status']);
    }

    /**
     * Get the list of comments for a task
     */
    public function getTaskComments($id)
    {
        $comments = $this->taskCommentService->getCommentsByTask($id);
        return response(['comments' => $comments], 200);
    }

    /**
     * Add a new comment to a task
     */
    public function addTaskComment(Request $request, $id)
    {
        $userId = Auth::id();
        $content = $request->input('content');
        if (!$content) {
            return response(['error' => 'Content is required'], 422);
        }
        $comment = $this->taskCommentService->createComment($id, $userId, $content);
        // Broadcast realtime event
        event(new TaskCommentCreated($comment, $id));
        return response(['message' => 'Comment added successfully', 'comment' => $comment], 201);
    }
}
