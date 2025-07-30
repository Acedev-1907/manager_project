<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChannelAuthorizationService
{
    /**
     * Check if user can access project channel
     */
    public static function canAccessProjectChannel($user, $projectId): bool
    {
        try {
            // Check if user is a project member
            $isMember = DB::table('project_user')
                ->where('user_id', $user->id)
                ->where('project_id', $projectId)
                ->exists();

            // Check if user is the project creator
            $isCreator = Project::where('id', $projectId)
                ->where('creator_id', $user->id)
                ->exists();

            $isAuthorized = $isMember || $isCreator;

            // Log access attempt for monitoring
            // Log::info('Project channel access attempt', [
            //     'user_id' => $user->id,
            //     'project_id' => $projectId,
            //     'is_member' => $isMember,
            //     'is_creator' => $isCreator,
            //     'authorized' => $isAuthorized
            // ]);

            return $isAuthorized;
        } catch (\Exception $e) {
            // Log::error('Error checking project channel authorization', [
            //     'user_id' => $user->id,
            //     'project_id' => $projectId,
            //     'error' => $e->getMessage()
            // ]);
            return false;
        }
    }

    /**
     * Check if user can access user-specific channel
     */
    public static function canAccessUserChannel($user, $targetUserId): bool
    {
        return (int) $user->id === (int) $targetUserId;
    }

    /**
     * Check if user can access notification channel
     */
    public static function canAccessNotificationChannel($user, $targetUserId): bool
    {
        return (int) $user->id === (int) $targetUserId;
    }

    /**
     * Check if user can access task channel
     */
    public static function canAccessTaskChannel($user, $taskId): bool
    {
        try {
            // Check if user has access to the task through project membership
            $hasAccess = DB::table('tasks')
                ->join('project_user', 'tasks.project_id', '=', 'project_user.project_id')
                ->where('tasks.id', $taskId)
                ->where('project_user.user_id', $user->id)
                ->exists();

            // Also check if user is the project creator
            $isCreator = DB::table('tasks')
                ->join('projects', 'tasks.project_id', '=', 'projects.id')
                ->where('tasks.id', $taskId)
                ->where('projects.creator_id', $user->id)
                ->exists();

            $isAuthorized = $hasAccess || $isCreator;

            // Log access attempt for monitoring
            // Log::info('Task channel access attempt', [
            //     'user_id' => $user->id,
            //     'task_id' => $taskId,
            //     'has_project_access' => $hasAccess,
            //     'is_project_creator' => $isCreator,
            //     'authorized' => $isAuthorized
            // ];

            return $isAuthorized;
        } catch (\Exception $e) {
            // Log::error('Error checking task channel authorization', [
            //     'user_id' => $user->id,
            //     'task_id' => $taskId,
            //     'error' => $e->getMessage()
            // ]);
            return false;
        }
    }

    /**
     * Check if user can access public channels
     */
    public static function canAccessPublicChannel(): bool
    {
        return true;
    }
}
