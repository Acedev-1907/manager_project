<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    UserController,
    MemberController,
    ProjectController,
    TaskController,
    PostController
};

Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register')->name('auth.register');
    Route::post('/login', 'login')->name('auth.login');
    Route::post('/reset-password', 'resetPassword')->name('auth.resetPassword');
});

Route::post('/check-reset-token', [AuthController::class, 'checkResetToken']);

// Public proxy-image endpoint
Route::get('/proxy-image', [App\Http\Controllers\UserController::class, 'proxyImage']);

Route::middleware('auth:sanctum')->group(function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logoutUser')->name('auth.logout');
        Route::post('/user/send-reset-password-link', 'sendResetPasswordLink');
    });

    Route::controller(UserController::class)->group(function () {
        Route::get('/user', 'show')->name('users.show');
        Route::put('/user', 'update')->name('users.update');
        Route::post('/user/upload-avatar', 'uploadAvatar');
        Route::get('/users/all', 'all');
    });

    Route::controller(MemberController::class)->group(function () {
        Route::post('/members', 'store')->name('members.store');
        Route::put('/members', 'update')->name('members.update');
        Route::get('/members', 'index')->name('members.index');
        Route::delete('/members/{id}', 'destroy')->name('members.destroy');
        // Route::post('/members/add-by-email', 'addByEmail')->name('members.addByEmail');
        // Route::post('/members/add-by-name-or-email', 'addByIdOrEmail')->name('members.addByIdOrEmail');
    });

    Route::controller(ProjectController::class)->group(function () {
        Route::post('/projects', 'store')->name('projects.store');
        Route::put('/projects', 'update')->name('projects.update');
        Route::get('/projects', 'index')->name('projects.index');
        Route::post('/projects/pinned', 'pinnedProject')->name('projects.pinned');
        Route::get('projects/{slug}', 'getProject')->name('projects.show');
        Route::get('/count/projects', 'countProject')->name('projects.count');
        Route::get('/pinned/projects', 'getPinnedProject')->name('projects.getPinned');
        Route::get('/chart-data/projects', 'getProjectChartData')->name('projects.chartData');
        Route::get('/projects/{id}/members', 'getProjectMembers')->name('projects.members');
        Route::delete('/projects/{id}', 'destroy')->name('projects.destroy');
        Route::post('/projects/{id}/add-column', 'addColumn')->name('projects.addColumn');
        Route::put('/projects/{id}/update-column', 'updateColumn')->name('projects.updateColumn');
        Route::delete('/projects/{id}/delete-column', 'deleteColumn')->name('projects.deleteColumn');
        Route::get('/projects/{projectId}/completed-tasks', 'getCompletedTasks')->name('projects.completedTasks');
    });

    // Friend Request API
    Route::controller(\App\Http\Controllers\MemberInvitationController::class)->group(function () {
        Route::post('/member-invitations/send', 'send');
        Route::get('/member-invitations/received', 'received');
        Route::get('/member-invitations/sent', 'sent');
        Route::post('/member-invitations/{id}/accept', 'accept');
        Route::post('/member-invitations/{id}/decline', 'decline');
        // Allow GET aliases for clients that trigger actions via links
        Route::get('/member-invitations/{id}/accept', 'accept');
        Route::get('/member-invitations/{id}/decline', 'decline');
        Route::delete('/member-invitations/{id}/cancel', 'cancel');
    });

    Route::controller(TaskController::class)->group(function () {
        Route::post('/tasks', 'createTask')->name('tasks.create');
        Route::post('/task/transition_to_{status}', 'transitionToStatus')->name('tasks.transitionToStatus');
        Route::post('/task/{transition}', 'transition')->name('tasks.transition');
        Route::delete('/tasks/{id}', 'destroy')->name('tasks.destroy');
        // API comment cho task
        Route::get('/tasks/{id}/comments', 'getTaskComments');
        Route::post('/tasks/{id}/comments', 'addTaskComment');
        // API broadcast drag events
        Route::post('/tasks/drag-started', 'broadcastDragStarted');
        Route::post('/tasks/drag-ended', 'broadcastDragEnded');
        Route::post('/tasks/drag-over-column', 'broadcastDragOverColumn');
    });

    // API notification cho user
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::get('/notifications/all', [\App\Http\Controllers\NotificationController::class, 'all']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']); //not implemented
    Route::delete('/notifications/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);

    // API Post (News Feed)
    Route::controller(PostController::class)->group(function () {
        Route::get('/posts', 'index');
        Route::post('/posts', 'store');
        Route::get('/posts/user-images', 'getUserImages');
        Route::post('/posts/upload-image', 'uploadImage');
        Route::get('/posts/{id}', 'show')->whereNumber('id');
        Route::post('/posts/{id}/like', 'toggleLike')->whereNumber('id');
        Route::post('/posts/{id}/comment', 'addComment')->whereNumber('id');
    });
});

Route::get('/check_email/{token}', [AuthController::class, 'verifyEmailApi'])->name('validEmail');
