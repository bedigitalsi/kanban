<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaskController;

// API routes with Bearer token authentication
Route::middleware('api.auth')->group(function () {
    Route::apiResource('tasks', TaskController::class);
    Route::post('tasks/positions', [TaskController::class, 'updatePositions']);
});