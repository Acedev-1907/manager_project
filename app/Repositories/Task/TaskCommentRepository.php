<?php

namespace App\Repositories\Task;

use App\Models\TaskComment;
use App\Repositories\BaseRepository;

class TaskCommentRepository extends BaseRepository
{
    public function __construct(TaskComment $model)
    {
        $this->model = $model;
    }

    protected function getModel(): string
    {
        return TaskComment::class;
    }

    public function getModelInstance()
    {
        return $this->model;
    }
}
