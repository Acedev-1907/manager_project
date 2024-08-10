<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MemberContrller;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login')->name('login');
});

Route::controller(ProjectController::class)->group(function () {
    Route::post('/projects', 'store')->name('createProject');
    Route::put('/projects', 'update')->name('update');
    Route::get('/projects', 'index')->name('indexProject');
    Route::post('/projects/pinned', 'pinnendProject')->name('pinnendProject');
    Route::get('projects/{slug}','getProject')->name('getProject');
});

Route::controller(MemberContrller::class)->group(function () {
    Route::post('/members', 'store')->name('createMember');
    Route::put('/members', 'update')->name('update');
    Route::get('/members', 'index')->name('indexMember');
});

Route::controller(TaskController::class)->group(function () {
    Route::post('/tasks', 'createTask')->name('createTask');
});