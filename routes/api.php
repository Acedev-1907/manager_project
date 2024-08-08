<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/projects', [ProjectController::class, 'store'])->name('createProject');
Route::put('/projects', [ProjectController::class, 'update'])->name('update');
Route::get('/projects', [ProjectController::class,'index'])->name('indexProject');
Route::post('/projects/pinned', [ProjectController::class,'pinnendProject'])->name('pinnendProject');