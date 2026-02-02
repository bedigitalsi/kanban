<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\ScheduledRoutineController;

// API routes with Bearer token authentication
Route::middleware('api.auth')->group(function () {
    Route::apiResource('tasks', TaskController::class);
    Route::post('tasks/positions', [TaskController::class, 'updatePositions']);

    Route::get('activity-logs', [ActivityLogController::class, 'index']);
    Route::post('activity-logs', [ActivityLogController::class, 'store']);

    Route::get('scheduled-routines', [ScheduledRoutineController::class, 'index']);
    Route::post('scheduled-routines', [ScheduledRoutineController::class, 'store']);
    Route::put('scheduled-routines/{id}', [ScheduledRoutineController::class, 'update']);
    Route::delete('scheduled-routines/{id}', [ScheduledRoutineController::class, 'destroy']);
});