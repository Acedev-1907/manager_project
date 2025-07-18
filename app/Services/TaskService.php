<?php

namespace App\Services;

use App\Models\Task;
use App\Repositories\Task\TaskRepository;
use App\Repositories\Task\TaskMemberRepository;
use Illuminate\Support\Facades\Validator;
use App\Repositories\Task\TaskCommentRepository;

class TaskService
{
    public function __construct(
        private TaskRepository $taskRepository,
        private TaskMemberRepository $taskMemberRepository
    ) {}

    /**
     * Create a new task and assign members
     */
    public function createTask(array $fields)
    {
        $errs = Validator::make($fields, [
            'name' => 'required',
            'projectId' => 'required|numeric',
            'memberIds' => 'required|array',
            'memberIds.*' => 'numeric',
        ]);
        if ($errs->fails()) {
            return ['errors' => $errs->errors()->all(), 'status' => 422];
        }

        $task = $this->taskRepository->create([
            'projectId' => $fields['projectId'],
            'name' => $fields['name'],
            'content' => $fields['content'] ?? null,
            'status' => Task::NOT_STARTED,
        ]);

        foreach ($fields['memberIds'] as $memberId) {
            $this->taskMemberRepository->create([
                'projectId' => $fields['projectId'],
                'taskId' => $task->id,
                'memberId' => $memberId
            ]);
        }

        return [
            'message' => 'Task created',
            'status' => 200,
            'task' => $task
        ];
    }

    /**
     * Update the status of a task
     */
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

    /**
     * Delete a task and its members
     */
    public function deleteTask($taskId)
    {
        $task = $this->taskRepository->find($taskId);
        if (!$task) {
            return ['errors' => ['Task does not exist'], 'status' => 404];
        }
        $this->taskMemberRepository->deleteByTaskId($taskId);
        $this->taskRepository->delete($taskId);
        return ['message' => 'Task deleted successfully', 'status' => 200];
    }
}

class TaskCommentService
{
    protected $taskCommentRepo;

    /**
     * Initialize the service for task comments
     */
    public function __construct(TaskCommentRepository $taskCommentRepo)
    {
        $this->taskCommentRepo = $taskCommentRepo;
    }

    /**
     * Get all comments for a task, including user info
     */
    public function getCommentsByTask($taskId)
    {
        return $this->taskCommentRepo->getModelInstance()
            ->where('task_id', $taskId)
            ->with('user')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Create a new comment for a task
     */
    public function createComment($taskId, $userId, $content)
    {
        return $this->taskCommentRepo->create([
            'task_id' => $taskId,
            'user_id' => $userId,
            'content' => $content,
        ]);
    }
}
