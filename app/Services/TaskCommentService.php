<?php

namespace App\Services;

use App\Repositories\Task\TaskCommentRepository;

/**
 * Task Comment Service
 * 
 * Handles all business logic related to task comments including
 * creating and retrieving comments.
 */
class TaskCommentService
{
    protected TaskCommentRepository $taskCommentRepo;

    /**
     * Initialize the service for task comments
     * 
     * @param TaskCommentRepository $taskCommentRepo
     */
    public function __construct(TaskCommentRepository $taskCommentRepo)
    {
        $this->taskCommentRepo = $taskCommentRepo;
    }

    /**
     * Get all comments for a task, including user info
     * 
     * @param int $taskId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCommentsByTask(int $taskId)
    {
        return $this->taskCommentRepo->getModelInstance()
            ->where('task_id', $taskId)
            ->with('user')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Create a new comment for a task
     * 
     * @param int $taskId
     * @param int $userId
     * @param string $content
     * @return \App\Models\TaskComment
     */
    public function createComment(int $taskId, int $userId, string $content)
    {
        return $this->taskCommentRepo->create([
            'task_id' => $taskId,
            'user_id' => $userId,
            'content' => $content,
        ]);
    }
}

