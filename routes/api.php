<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    UserController,
    MemberController,
    ProjectController,
    TaskController
};

Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register')->name('auth.register');
    Route::post('/login', 'login')->name('auth.login');
    Route::post('/reset-password', 'resetPassword')->name('auth.resetPassword');
});

Route::post('/check-reset-token', [AuthController::class, 'checkResetToken']);

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
        Route::post('/proxy-image', 'proxyImage');
    });

    Route::controller(MemberController::class)->group(function () {
        Route::post('/members', 'store')->name('members.store');
        Route::put('/members', 'update')->name('members.update');
        Route::get('/members', 'index')->name('members.index');
        Route::delete('/members/{id}', 'destroy')->name('members.destroy');
        Route::post('/members/add-by-email', 'addByEmail')->name('members.addByEmail');
        Route::post('/members/add-by-name-or-email', 'addByNameOrEmail')->name('members.addByNameOrEmail');
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
    });

    Route::controller(TaskController::class)->group(function () {
        Route::post('/tasks', 'createTask')->name('tasks.create');
        Route::post('/task/{transition}', 'transition')->name('tasks.transition');
        Route::delete('/tasks/{id}', 'destroy')->name('tasks.destroy');
        // API comment cho task
        Route::get('/tasks/{id}/comments', 'getTaskComments');
        Route::post('/tasks/{id}/comments', 'addTaskComment');
    });
});
