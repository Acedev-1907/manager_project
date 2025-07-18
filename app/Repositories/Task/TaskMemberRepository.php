<?php

namespace App\Repositories\Task;

use App\Repositories\BaseRepository;

class TaskMemberRepository extends BaseRepository
{
    protected function getModel(): string
    {
        return \App\Models\TaskMember::class;
    }

    public function deleteByTaskId($taskId)
    {
        return $this->model->where('taskId', $taskId)->delete();
    }
}
