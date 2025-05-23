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

    // public function TaskToNotStartedToPending(Request $req)
    // {
    //     $checkUpdate = $this->taskService->updateTaskStatus($req->all(), Task::PENDING);
    //     if($checkUpdate){
    //         return response(['message' => 'task move to peding'], 200);
    //     }
    // }

    // public function TaskToNotStartedToCompleted(Request $req)
    // {
    //     $checkUpdate = $this->taskService->updateTaskStatus($req->all(), Task::COMPLETED);

    //     if($checkUpdate){
    //         return response(['message' => 'task move to completed'], 200);
    //     }
    // }

    // public function TaskToPendingToCompleted(Request $req)
    // {
    //     $checkUpdate = $this->taskService->updateTaskStatus($req->all(), Task::COMPLETED);

    //     if($checkUpdate){
    //         return response(['message' => 'task move to completed'], 200);
    //     }
    // }

    // public function TaskToPendingToNotStarted(Request $req)
    // {
    //     $checkUpdate = $this->taskService->updateTaskStatus($req->all(), Task::NOT_STARTED);

    //     if($checkUpdate){
    //         return response(['message' => 'task move to started'], 200);
    //     }
    // }

    // public function TaskToCompletedToPending(Request $req)
    // {
    //     $checkUpdate = $this->taskService->updateTaskStatus($req->all(), Task::PENDING);

    //     if($checkUpdate){
    //         return response(['message' => 'task move to pending'], 200);
    //     }
    // }

    // public function TaskToCompletedToNotStarted(Request $req)
    // {
    //     $checkUpdate = $this->taskService->updateTaskStatus($req->all(), Task::NOT_STARTED);

    //     if($checkUpdate){
    //         return response(['message' => 'task move to not started'], 200);
    //     }
    // }

    //Optimizing code about task status
    public function transition(Request $req, string $transition)
    {
        switch ($transition) {
            case 'not_started_to_pending':
                $newStatus = Task::PENDING;
                $nameStatus = 'Pending';
                break;

            case 'not_started_to_completed':
                $newStatus = Task::COMPLETED;
                $nameStatus = 'Completed';
                break;

            case 'pending_to_completed':
                $newStatus = Task::COMPLETED;
                $nameStatus = 'Completed';
                break;

            case 'pending_to_not_started':
                $newStatus = Task::NOT_STARTED;
                $nameStatus = 'Not Started';
                break;

            case 'completed_to_pending':
                $newStatus = Task::PENDING;
                $nameStatus = 'Pending';
                break;

            case 'completed_to_not_started':
                $newStatus = Task::NOT_STARTED;
                break;

            default:
                return response(['error' => 'Unknown transition: ' . $transition], 404);
        }

        $checkUpdate = $this->taskService->updateTaskStatus($req->all(), $newStatus);

        if ($checkUpdate) {
            return response(['message' => "Task status updated to {$nameStatus}"], 200);
        }

        return response(['error' => 'Failed to update task status'], 500);
    }
}
