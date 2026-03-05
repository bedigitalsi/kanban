<?php

use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\ScheduledRoutineController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\BrainController;
use App\Http\Controllers\Api\SmsController;
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
    Route::post('/scheduled-routines/{id}/toggle', [ScheduledRoutineController::class, 'toggle']);
    Route::put('/scheduled-routines/{id}', [ScheduledRoutineController::class, 'update']);

    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);

    Route::get('/brain', [BrainController::class, 'index']);
    Route::post('/brain', [BrainController::class, 'store']);
    Route::put('/brain/{brain}', [BrainController::class, 'update']);
    Route::delete('/brain/{brain}', [BrainController::class, 'destroy']);

    Route::get('/sms/stats', [SmsController::class, 'stats']);
    Route::get('/sms', [SmsController::class, 'index']);
    Route::post('/sms', [SmsController::class, 'store']);

});

use App\Http\Controllers\Api\AgentMessageController;

Route::middleware(ApiAuth::class)->group(function () {
    Route::get("/agent-messages", [AgentMessageController::class, "index"]);
    Route::post("/agent-messages", [AgentMessageController::class, "store"]);
    Route::post("/agent-messages/{id}/read", [AgentMessageController::class, "markRead"]);
    Route::post("/agent-messages/{id}/reply", [AgentMessageController::class, "reply"]);
});
