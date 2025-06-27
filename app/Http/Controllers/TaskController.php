<?php

namespace App\Http\Controllers;

use App\Events\TaskStatusUpdated;
use App\Models\Task;
use App\Models\TaskMember;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function __construct(private TaskService $taskService) {}

    public function createTask(Request $req)
    {
        return DB::transaction(function () use ($req) {
            $fields = $req->all();

            $errs = Validator::make($fields, [
                'name' => 'required',
                'projectId' => 'required|numeric',
                'memberIds' => 'required|array',
                'memberIds.*' => 'numeric',
            ]);

            if ($errs->fails()) return response($errs->errors()->all(), 422);

            $task = Task::create([
                'projectId' => $fields['projectId'],
                'name' => $fields['name'],
                'status' => Task::NOT_STARTED,
            ]);

            $members = $fields['memberIds'];

            for ($i = 0; $i < count($members); $i++) {
                TaskMember::create([
                    'projectId' => $fields['projectId'],
                    'taskId' => $task->id,
                    'memberId' => $members[$i]
                ]);
            }
            return response(['message' => 'task created'], 200);
        });
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
