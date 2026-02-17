<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/login', fn() => Inertia::render('Auth/Login'))->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/', fn() => Inertia::render('Dashboard'))->name('dashboard');
    Route::get('/tasks', fn() => Inertia::render('Tasks'))->name('tasks');
    Route::get('/journal', fn() => Inertia::render('Journal'))->name('journal');
    Route::get('/activity', fn() => Inertia::render('Activity'))->name('activity');
    Route::get('/brain', fn() => Inertia::render('Brain'))->name('brain');
    Route::get('/routines', fn() => Inertia::render('Routines'))->name('routines');
    Route::get('/projects', fn() => Inertia::render('Projects'))->name('projects');
});
