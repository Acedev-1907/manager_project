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
     * Create a new task and assign members (Legacy - array based)
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
     * Create a new task with DTO (New - recommended)
     * 
     * @param \App\DTOs\TaskDTO $dto Task data transfer object
     * @return array
     */
    public function createTaskWithDTO(\App\DTOs\TaskDTO $dto): array
    {
        // Create task using DTO properties (type-safe)
        $task = $this->taskRepository->create([
            'projectId' => $dto->projectId,
            'name' => $dto->title, // DTO uses 'title', DB uses 'name'
            'content' => $dto->description,
            'status' => $dto->status ?? Task::NOT_STARTED,
            'priority' => $dto->priority,
            'dueDate' => $dto->dueDate,
        ]);

        // Assign members from DTO
        if (!empty($dto->members) && is_array($dto->members)) {
            foreach ($dto->members as $memberId) {
                $this->taskMemberRepository->create([
                    'projectId' => $dto->projectId,
                    'taskId' => $task->id,
                    'memberId' => $memberId
                ]);
            }
        }

        return [
            'message' => 'Task created successfully',
            'status' => 200,
            'task' => $task
        ];
    }

    /**
     * Update the status of a task
     */
    public function updateTaskStatus(array $data, $status)
    {
        $taskId = $data['taskId'];
        $projectId = $data['projectId'];
        $userId = $data['userId'] ?? null;

        // Cập nhật cả status và column_id để đảm bảo task chỉ ở 1 cột
        $updated = $this->taskRepository->updateById($taskId, [
            'status' => $status,
            'column_id' => $status // Với JSON columns, column_id = status
        ]);

        if ($updated) {
            Task::handleProjectProgress($projectId, $userId);
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
