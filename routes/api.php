<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
});

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logoutUser');
    });

    Route::controller(ProjectController::class)->group(function () {
        Route::post('/projects', 'store')->name('createProject');
        Route::put('/projects', 'update')->name('update');
        Route::get('/projects', 'index')->name('indexProject');
        Route::post('/projects/pinned', 'pinnendProject')->name('pinnendProject');
        Route::get('projects/{slug}', 'getProject')->name('getProject');
        Route::get('/count/projects', 'countProject')->name('countProject');
        Route::get('/pinned/projects', 'getPinnnedProject')->name('getPinnnedProject');
        Route::get('/chart-data/projects', 'getProjectChartData')->name('getProjectChartData');
    });

    Route::controller(MemberController::class)->group(function () {
        Route::post('/members', 'store')->name('createMember');
        Route::put('/members', 'update')->name('update');
        Route::get('/members', 'index')->name('indexMember');
        Route::delete('/members/{id}', 'destroy')->name('deleteMember');
        Route::post('/members/add-by-email', 'addByEmail')->name('addByEmail');
        Route::post('/members/add-by-name-or-email', 'addByNameOrEmail')->name('addByNameOrEmail');
    });

    Route::controller(TaskController::class)->group(function () {
        Route::post('/tasks', 'createTask')->name('createTask');
        Route::post('/task/{transition}', 'transition')->name('transition');
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/users/all', [UserController::class, 'allUsers']);
