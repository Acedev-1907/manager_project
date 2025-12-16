<?php

namespace App\Services;

use App\Models\Task;
use App\Repositories\Task\TaskRepository;
use App\Repositories\Task\TaskMemberRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Events\TaskStatusChanged;

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
        try {
            $taskId = $data['taskId'];
            $projectId = $data['projectId'];
            $userId = $data['userId'] ?? null;

            if (!$taskId || !$projectId) {
                Log::error('updateTaskStatus: Missing taskId or projectId', $data);
                return false;
            }

            // Cập nhật status (không cập nhật column_id vì có thể không tồn tại trong DB)
            $updateData = ['status' => $status];
            
            // Chỉ thêm column_id nếu cột này tồn tại trong bảng
            // (kiểm tra schema hoặc bỏ qua nếu không có)
            
            $updated = $this->taskRepository->updateById($taskId, $updateData);

            if ($updated) {
                try {
                    Task::handleProjectProgress($projectId, $userId);
                } catch (\Exception $e) {
                    Log::warning('Failed to handle project progress: ' . $e->getMessage());
                }

                // Broadcast realtime task status changed
                try {
                    $task = $this->taskRepository->find($taskId);
                    if ($task) {
                        broadcast(new TaskStatusChanged($task, $projectId, $status, $userId));
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to broadcast task status changed: ' . $e->getMessage());
                    // Không fail toàn bộ request nếu broadcast fail
                }
            }

            return $updated;
        } catch (\Exception $e) {
            Log::error('Error in updateTaskStatus: ' . $e->getMessage(), [
                'data' => $data,
                'status' => $status,
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
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
