<?php

namespace App\Services;

use App\Models\Task;
use App\Repositories\Task\TaskRepository;
use App\Repositories\Task\TaskMemberRepository;
use Illuminate\Support\Facades\Validator;

class TaskService
{
    public function __construct(
        private TaskRepository $taskRepository,
        private TaskMemberRepository $taskMemberRepository
    ) {}

    public function createTask(array $fields)
    {
        $errs = Validator::make($fields, [
            'name' => 'required',
            'projectId' => 'required|numeric',
            'memberIds' => 'required|array',
            'memberIds.*' => 'numeric',
        ]);
        if ($errs->fails()) return ['errors' => $errs->errors()->all(), 'status' => 422];

        $task = $this->taskRepository->create([
            'projectId' => $fields['projectId'],
            'name' => $fields['name'],
            'status' => \App\Models\Task::NOT_STARTED,
        ]);

        $members = $fields['memberIds'];
        foreach ($members as $memberId) {
            $this->taskMemberRepository->create([
                'projectId' => $fields['projectId'],
                'taskId' => $task->id,
                'memberId' => $memberId
            ]);
        }
        return ['message' => 'task created', 'status' => 200];
    }

    public function updateTaskStatus(array $data, int $status)
    {
        $taskId = $data['taskId'];
        $projectId = $data['projectId'];

        $updated = $this->taskRepository->updateById($taskId, ['status' => $status]);


        if ($updated) {
            Task::handleProjectProgress($projectId);
        }

        return $updated;
    }

    public function deleteTask($taskId)
    {
        $task = $this->taskRepository->find($taskId);
        if (!$task) {
            return ['errors' => ['Task does not exist'], 'status' => 404];
        }
        // Delete all task members related to this task
        $this->taskMemberRepository->deleteByTaskId($taskId);
        $this->taskRepository->delete($taskId);
        return ['message' => 'Task deleted successfully', 'status' => 200];
    }
}
