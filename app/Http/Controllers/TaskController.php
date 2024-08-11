<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
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

            return response(['message' => 'user created'], 200);
        });
    }

    public function TaskToNotStartedToPending(Request $req)
    {
        Task::changeTaskStatus($req->taskId, Task::PENDING);
        return response(['message' => 'task move to peding'], 200);
    }

    public function TaskToNotStartedToCompleted(Request $req)
    {
        Task::changeTaskStatus($req->taskId, Task::COMPLETED);
        return response(['message' => 'task move to completed'], 200);
    }

    public function TaskToPendingToCompleted(Request $req)
    {
        Task::changeTaskStatus($req->taskId, Task::COMPLETED);
        return response(['message' => 'task move to completed'], 200);
    }

    public function TaskToPendingToNotStarted(Request $req)
    {
        Task::changeTaskStatus($req->taskId, Task::NOT_STARTED);
        return response(['message' => 'task move to started'], 200);
    }

    public function TaskToCompletedToPending(Request $req)
    {
        Task::changeTaskStatus($req->taskId, Task::PENDING);
        return response(['message' => 'task move to peding'], 200);
    }

    public function TaskToCompletedToNotStarted(Request $req)
    {
        Task::changeTaskStatus($req->taskId, Task::NOT_STARTED);
        return response(['message' => 'task move to not started'], 200);
    }
}
