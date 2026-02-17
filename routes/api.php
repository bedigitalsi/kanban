<?php

use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\ScheduledRoutineController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Middleware\ApiAuth;
use Illuminate\Support\Facades\Route;

Route::middleware(ApiAuth::class)->group(function () {
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::put('/tasks/{id}', [TaskController::class, 'update']);
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
    Route::post('/tasks/positions', [TaskController::class, 'positions']);

    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    Route::post('/activity-logs', [ActivityLogController::class, 'store']);

    Route::get('/scheduled-routines', [ScheduledRoutineController::class, 'index']);

    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
});
