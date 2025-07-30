<?php

namespace App\Models;

use App\Events\TrackProjectProgress;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Events\TrackCompletedAndPending;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class Task extends Model
{
    use HasFactory;

    const NOT_STARTED = 0;
    const PENDING = 1;
    const COMPLETED = 'OK'; // Chỉ dùng cho task hoàn thành

    protected $fillable = [
        'projectId',
        'name',
        'content',
        'status'
    ];

    /**
     * Model events - tự động dispatch events khi có thay đổi
     */
    protected static function booted()
    {
        // Khi task được updated (đặc biệt là status thay đổi)
        static::updated(function ($task) {
            // Chỉ dispatch event khi status thay đổi
            if ($task->wasChanged('status')) {
                try {
                    // Log::info('Task status changed', [
                    //     'taskId' => $task->id,
                    //     'projectId' => $task->projectId,
                    //     'oldStatus' => $task->getOriginal('status'),
                    //     'newStatus' => $task->status,
                    //     'userId' => Auth::id()
                    // ]);

                    // Dispatch events cho project progress
                    Task::handleProjectProgress($task->projectId, Auth::id());
                } catch (\Exception $e) {
                    // Log::error('Error in task updated event', [
                    //     'taskId' => $task->id,
                    //     'projectId' => $task->projectId,
                    //     'error' => $e->getMessage()
                    // ]);
                }
            }
        });

        // Khi task được created
        static::created(function ($task) {
            try {
                // Log::info('New task created', [
                //     'taskId' => $task->id,
                //     'projectId' => $task->projectId,
                //     'status' => $task->status,
                //     'userId' => Auth::id()
                // ]);

                // Dispatch events cho project progress
                Task::handleProjectProgress($task->projectId, Auth::id());
            } catch (\Exception $e) {
                // Log::error('Error in task created event', [
                //     'taskId' => $task->id,
                //     'projectId' => $task->projectId,
                //     'error' => $e->getMessage()
                // ]);
            }
        });

        // Khi task được deleted
        static::deleted(function ($task) {
            try {
                // Log::info('Task deleted', [
                //     'taskId' => $task->id,
                //     'projectId' => $task->projectId,
                //     'userId' => Auth::id()
                // ]);

                // Dispatch events cho project progress
                Task::handleProjectProgress($task->projectId, Auth::id());
            } catch (\Exception $e) {
                // Log::error('Error in task deleted event', [
                //     'taskId' => $task->id,
                //     'projectId' => $task->projectId,
                //     'error' => $e->getMessage()
                // ]);
            }
        });
    }

    public function task_members()
    {
        return $this->hasMany(TaskMember::class, 'taskId');
    }

    /**
     * Get the task comments list
     */
    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'task_id');
    }

    public static function countCompletedTask($projectId)
    {
        $count = Task::where('projectId', $projectId)
            ->where('status', Task::COMPLETED)
            ->count();
        return $count;
    }

    public static function countCompletedAndPendingTask($projectId)
    {
        $task = Task::where('projectId', $projectId)->get();

        $pending = 0;
        $completed = 0;
        foreach ($task as $row) {
            if ($row->status == Task::PENDING || $row->status == 1) {
                $pending++;
            }

            if ($row->status === Task::COMPLETED || $row->status == 'OK') {
                $completed++;
            }
        }

        return [$pending, $completed];
    }

    public static  function countProjectTask($projectId)
    {
        $count = Task::where('projectId', $projectId)->count();
        return $count;
    }
    public static function handleProjectProgress($projectId, $userId = null)
    {
        try {
            $totalTask = Task::countProjectTask($projectId);
            if ($totalTask === 0) {
                return 0;
            }

            $totalCompletedTask = Task::countCompletedTask($projectId);
            $progress = Task::aroundNumber(($totalCompletedTask * 100) / $totalTask);

            $taskProgress = TaskProgress::where('projectId', $projectId)->first();
            if (!is_null($taskProgress)) {
                $taskProgress->where('projectId', $projectId)
                    ->update(['progress' => $progress]);

                $tasks = Task::countCompletedAndPendingTask($projectId);

                // Dispatch events with user information
                TrackCompletedAndPending::dispatch($tasks, $projectId, null, $userId);
                TrackProjectProgress::dispatch($progress, $projectId, $userId);

                // Log::info('Project progress updated', [
                //     'projectId' => $projectId,
                //     'progress' => $progress,
                //     'totalTasks' => $totalTask,
                //     'completedTasks' => $totalCompletedTask,
                //     'userId' => $userId
                // ]);

                return $progress;
            }
        } catch (\Exception $e) {
            // Log::error('Error in handleProjectProgress', [
            //     'projectId' => $projectId,
            //     'userId' => $userId,
            //     'error' => $e->getMessage(),
            //     'trace' => $e->getTraceAsString()
            // ]);
        }

        return 0;
    }

    public static function aroundNumber($number)
    {
        if (strpos($number, '.')) {
            $position = strpos($number, '.') + 1;
            return substr($number, 0, $position + 1);
        } else {
            return $number;
        }
    }

    /**
     * Get completed tasks for a project
     */
    public static function getCompletedTasks($projectId)
    {
        return Task::where('projectId', $projectId)
            ->where('status', Task::COMPLETED)
            ->with(['task_members.user', 'comments'])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Check if task is completed
     */
    public function isCompleted()
    {
        return $this->status === Task::COMPLETED;
    }
}
