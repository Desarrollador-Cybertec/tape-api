<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Public ──
Route::post('/login', [AuthController::class, 'login']);

// ── Protected ──
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Users
    Route::apiResource('users', UserController::class)->except(['destroy']);
    Route::patch('/users/{user}/role', [UserController::class, 'updateRole']);
    Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);

    // Areas
    Route::apiResource('areas', AreaController::class)->except(['destroy']);
    Route::patch('/areas/{area}/manager', [AreaController::class, 'assignManager']);
    Route::post('/areas/claim-worker', [AreaController::class, 'claimWorker']);

    // Meetings
    Route::apiResource('meetings', MeetingController::class);

    // Tasks
    Route::apiResource('tasks', TaskController::class)->except(['destroy']);
    Route::post('/tasks/{task}/delegate', [TaskController::class, 'delegate']);
    Route::post('/tasks/{task}/start', [TaskController::class, 'start']);
    Route::post('/tasks/{task}/submit-review', [TaskController::class, 'submitForReview']);
    Route::post('/tasks/{task}/approve', [TaskController::class, 'approve']);
    Route::post('/tasks/{task}/reject', [TaskController::class, 'reject']);
    Route::post('/tasks/{task}/cancel', [TaskController::class, 'cancel']);
    Route::post('/tasks/{task}/comment', [TaskController::class, 'comment']);
    Route::post('/tasks/{task}/attachments', [TaskController::class, 'addAttachment']);
    Route::post('/tasks/{task}/updates', [TaskController::class, 'addUpdate']);

    // Dashboard
    Route::get('/dashboard/general', [DashboardController::class, 'general']);
    Route::get('/dashboard/area/{area}', [DashboardController::class, 'byArea']);
    Route::get('/dashboard/me', [DashboardController::class, 'myDashboard']);
});
