<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskboardController;

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
});
