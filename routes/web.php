<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskboardController;
use App\Http\Controllers\ProjectController;

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware('simple.auth')->group(function () {
    Route::get('/', [TaskboardController::class, 'index'])->name('taskboard');
    Route::post('/tasks', [TaskboardController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{task}', [TaskboardController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskboardController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/tasks/positions', [TaskboardController::class, 'updatePositions'])->name('tasks.positions');
    
    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('/projects/positions', [ProjectController::class, 'updatePositions'])->name('projects.positions');
});
