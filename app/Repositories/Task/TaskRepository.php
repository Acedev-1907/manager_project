<?php

namespace App\Repositories\Task;

use App\Repositories\BaseRepository;

class TaskRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Abstract Function's serve for initialize model transmission
     */
    protected function getModel(): string
    {
        return \App\Models\Task::class;
    }
}