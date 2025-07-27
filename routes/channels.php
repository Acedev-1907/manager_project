<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

// Broadcast routes configuration
Broadcast::routes([
    'middleware' => ['auth:sanctum'],
]);

// ========================================
// PUBLIC CHANNELS (No authorization required)
// ========================================

// Global project count updates
Broadcast::channel('countProject', function () {
    return true;
});

// Global task updates
Broadcast::channel('channel-task', function () {
    return true;
});

// ========================================
// PRIVATE CHANNELS (Authorization required)
// ========================================

// User-specific channels
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// User notification channel
Broadcast::channel('user-notification.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Task-specific channel
Broadcast::channel('task.{taskId}', function ($user, $taskId) {
    return true;
});

// ========================================
// PROJECT-SPECIFIC CHANNELS
// ========================================

// Project private channel - only project members and creator can access
Broadcast::channel('project.{projectId}', function ($user, $projectId) {
    // Check if user is a project member
    $isMember = DB::table('project_user')
        ->where('user_id', $user->id)
        ->where('project_id', $projectId)
        ->exists();

    // Check if user is the project creator
    $isCreator = Project::where('id', $projectId)
        ->where('creator_id', $user->id)
        ->exists();

    return $isMember || $isCreator;
});
