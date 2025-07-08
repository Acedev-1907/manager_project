<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function createTask(Request $req)
    {
        $result = $this->taskService->createTask($req->all());
        if (isset($result['errors'])) {
            return response($result['errors'], $result['status']);
        }
        return response(['message' => $result['message']], $result['status']);
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

        $checkUpdate = $this->taskService->updateTaskStatus($req->all(), $newStatus);

        if ($checkUpdate) {
            return response(['message' => "Task status updated to {$nameStatus}"], 200);
        }

        return response(['error' => 'Failed to update task status'], 500);
    }
}
