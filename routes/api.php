<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberContrller;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
});
Route::controller(ProjectController::class)->group(function () {
    Route::post('/projects', 'store')->name('createProject');
    Route::put('/projects', 'update')->name('update');
    Route::get('/projects', 'index')->name('indexProject');
    Route::post('/projects/pinned', 'pinnendProject')->name('pinnendProject');
    Route::get('projects/{slug}', 'getProject')->name('getProject');
    Route::get('/count/projects', 'countProject')->name('countProject');
    Route::get('/pinned/projects', 'getPinnnedProject')->name('getPinnnedProject');
});

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logoutUser');
    });

    // Route::controller(ProjectController::class)->group(function () {
    //     Route::post('/projects', 'store')->name('createProject');
    //     Route::put('/projects', 'update')->name('update');
    //     Route::get('/projects', 'index')->name('indexProject');
    //     Route::post('/projects/pinned', 'pinnendProject')->name('pinnendProject');
    //     Route::get('projects/{slug}', 'getProject')->name('getProject');
    //     Route::get('/count/projects', 'countProject')->name('countProject');
    //     Route::get('/pinned/projects', 'getPinnnedProject')->name('getPinnnedProject');
    // });

    Route::controller(MemberContrller::class)->group(function () {
        Route::post('/members', 'store')->name('createMember');
        Route::put('/members', 'update')->name('update');
        Route::get('/members', 'index')->name('indexMember');
    });

    Route::controller(TaskController::class)->group(function () {
        Route::post('/tasks', 'createTask')->name('createTask');
        Route::post('task/not_started_to_peeding', 'TaskToNotStartedToPending')->name('TaskToNotStartedToPending');
        Route::post('task/not_started_to_completed', 'TaskToNotStartedToCompleted')->name('TaskToNotStartedToCompleted');
        Route::post('task/pending_to_completed', 'TaskToPendingToCompleted')->name('TaskToPendingToCompleted');

        Route::post('task/pending_to_not_started', 'TaskToPendingToNotStarted')->name('TaskToPendingToNotStarted');
        Route::post('task/completed_to_pending', 'TaskToCompletedToPending')->name('TaskToCompletedToPending');
        Route::post('task/complete_to_not_started', 'TaskToCompletedToNotStarted')->name('TaskToCompletedToNotStarted');
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
