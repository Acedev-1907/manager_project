<?php

namespace App\Services;

use App\Models\Task;
use App\Repositories\Task\TaskRepository;

class TaskService
{
    public function __construct(
        private TaskRepository $taskRepository
    ){}
    public function updateTaskStatus(array $data, int $status)
    {
        $taskId = $data['taskId'];
        $projectId = $data['projectId'];

        $updated = $this->taskRepository->updateByPK($taskId, ['status' => $status]);


        if ($updated) {
            Task::handleProjectProgress($projectId);
        }

        return $updated;
    }
}