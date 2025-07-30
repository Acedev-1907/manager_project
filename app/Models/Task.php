<?php

namespace App\Models;

use App\Events\TrackProjectProgress;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Events\TrackCompletedAndPending;
use Illuminate\Support\Facades\Auth;

/**
 * Task Model
 * 
 * Represents a task within a project. Handles task lifecycle,
 * status transitions, and project progress tracking.
 */
class Task extends Model
{
    use HasFactory;

    // Task status constants
    const NOT_STARTED = 0;
    const PENDING = 1;
    const COMPLETED = 'OK'; // Special status for completed tasks

    protected $fillable = [
        'projectId',
        'name',
        'content',
        'status'
    ];

    /**
     * Model boot method - handle model events
     */
    protected static function booted()
    {
        // When task is updated (especially status changes)
        static::updated(function ($task) {
            // Only dispatch event when status changes
            if ($task->wasChanged('status')) {
                try {
                    // Dispatch events for project progress tracking
                    self::handleProjectProgress($task->projectId, Auth::id());
                } catch (\Exception $e) {
                    // Silent error handling for event dispatch
                }
            }
        });

        // When task is created
        static::created(function ($task) {
            try {
                // Dispatch events for project progress tracking
                self::handleProjectProgress($task->projectId, Auth::id());
            } catch (\Exception $e) {
                // Silent error handling for event dispatch
            }
        });

        // When task is deleted
        static::deleted(function ($task) {
            try {
                // Dispatch events for project progress tracking
                self::handleProjectProgress($task->projectId, Auth::id());
            } catch (\Exception $e) {
                // Silent error handling for event dispatch
            }
        });
    }

    /**
     * Get task members relationship
     */
    public function task_members()
    {
        return $this->hasMany(TaskMember::class, 'taskId');
    }

    /**
     * Get task comments relationship
     */
    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'taskId');
    }

    /**
     * Count completed tasks for a project
     * 
     * @param int $projectId
     * @return int
     */
    public static function countCompletedTask(int $projectId): int
    {
        return self::where('projectId', $projectId)
            ->where('status', self::COMPLETED)
            ->count();
    }

    /**
     * Count completed and pending tasks for a project
     * 
     * @param int $projectId
     * @return array
     */
    public static function countCompletedAndPendingTask(int $projectId): array
    {
        $completed = self::where('projectId', $projectId)
            ->where('status', self::COMPLETED)
            ->count();

        $pending = self::where('projectId', $projectId)
            ->where('status', self::PENDING)
            ->count();

        return [
            'completed' => $completed,
            'pending' => $pending
        ];
    }

    /**
     * Count total tasks for a project
     * 
     * @param int $projectId
     * @return int
     */
    public static function countProjectTask(int $projectId): int
    {
        return self::where('projectId', $projectId)->count();
    }

    /**
     * Handle project progress updates when tasks change
     * 
     * @param int $projectId
     * @param int|null $userId
     * @return void
     */
    public static function handleProjectProgress(int $projectId, ?int $userId = null): void
    {
        try {
            // Get project and its tasks
            $project = Project::find($projectId);
            if (!$project) return;

            $totalTasks = self::countProjectTask($projectId);
            if ($totalTasks === 0) {
                // No tasks, set progress to 0
                self::updateProjectProgress($projectId, 0, $userId);
                return;
            }

            // Count completed tasks
            $completedTasks = self::countCompletedTask($projectId);

            // Calculate progress percentage
            $progress = self::calculateProgress($completedTasks, $totalTasks);

            // Update project progress
            self::updateProjectProgress($projectId, $progress, $userId);

            // Dispatch events for real-time updates
            self::dispatchProgressEvents($projectId, $completedTasks, $totalTasks, $progress);
        } catch (\Exception $e) {
            // Silent error handling for progress updates
        }
    }

    /**
     * Calculate progress percentage
     * 
     * @param int $completed
     * @param int $total
     * @return int
     */
    private static function calculateProgress(int $completed, int $total): int
    {
        if ($total === 0) return 0;
        return self::aroundNumber(($completed / $total) * 100);
    }

    /**
     * Update project progress in database
     * 
     * @param int $projectId
     * @param int $progress
     * @param int|null $userId
     * @return void
     */
    private static function updateProjectProgress(int $projectId, int $progress, ?int $userId): void
    {
        // Update task progress for all users in the project
        $project = Project::find($projectId);
        if (!$project) return;

        $userIds = $project->users->pluck('id');

        foreach ($userIds as $uid) {
            TaskProgress::updateOrCreate(
                [
                    'projectId' => $projectId,
                    'user_id' => $uid
                ],
                [
                    'progress' => $progress
                ]
            );
        }
    }

    /**
     * Dispatch real-time events for progress updates
     * 
     * @param int $projectId
     * @param int $completed
     * @param int $total
     * @param int $progress
     * @return void
     */
    private static function dispatchProgressEvents(int $projectId, int $completed, int $total, int $progress): void
    {
        // Broadcast project progress update
        broadcast(new TrackProjectProgress($projectId, $progress));

        // Broadcast completed and pending task counts
        broadcast(new TrackCompletedAndPending($projectId, $completed, $total - $completed));
    }

    /**
     * Round number to nearest integer
     * 
     * @param float $number
     * @return int
     */
    public static function aroundNumber(float $number): int
    {
        return (int) round($number);
    }

    /**
     * Get completed tasks for a project with member information
     * 
     * @param int $projectId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getCompletedTasks(int $projectId)
    {
        return self::with(['task_members.user'])
            ->where('projectId', $projectId)
            ->where('status', self::COMPLETED)
            ->get();
    }

    /**
     * Check if task is completed
     * 
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === self::COMPLETED;
    }
}
