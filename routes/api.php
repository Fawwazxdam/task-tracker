<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\DashboardController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes (require authentication via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Project routes
    Route::apiResource('projects', \App\Http\Controllers\ProjectController::class);
    Route::get('projects/{projectId}/backlog', [\App\Http\Controllers\TaskController::class, 'backlog']);
    Route::post('projects/{projectId}/backlog', [\App\Http\Controllers\TaskController::class, 'addToBacklog']);
    Route::get('projects/{projectId}/stats', [\App\Http\Controllers\ProjectController::class, 'stats']);
    Route::get('projects/{projectId}/members', [\App\Http\Controllers\ProjectController::class, 'members']);
    Route::post('projects/{projectId}/members', [\App\Http\Controllers\ProjectController::class, 'addMembers']);

    // Task routes nested under projects
    Route::prefix('projects/{projectId}')->group(function () {
        Route::get('tasks', [TaskController::class, 'index']);
        Route::post('tasks', [TaskController::class, 'store']);
        Route::get('tasks/{taskId}', [TaskController::class, 'show']);
        Route::put('tasks/{taskId}', [TaskController::class, 'update']);
        Route::patch('tasks/{taskId}/status', [TaskController::class, 'updateStatus']);
        Route::patch('tasks/{taskId}/move', [TaskController::class, 'moveFromBacklog']);
        Route::delete('tasks/{taskId}', [TaskController::class, 'destroy']);
        Route::get('tasks-search', [TaskController::class, 'search']);
    });

    Route::post('logout', [AuthController::class, 'logout']);
    // You can add a route to get the current user
    Route::get('user', function (Request $request) {
        return $request->user();
    });

    // User Preferences routes
    Route::prefix('user/preferences')->group(function () {
        Route::get('/', [UserPreferenceController::class, 'index']);
        Route::post('/', [UserPreferenceController::class, 'store']);
        Route::get('/{key}', [UserPreferenceController::class, 'show']);
        Route::put('/{key}', [UserPreferenceController::class, 'update']);
        Route::delete('/{key}', [UserPreferenceController::class, 'destroy']);
    });

    // Dashboard routes
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('dashboard/recent-tasks', [DashboardController::class, 'recentTasks']);

    // User routes
    Route::get('users', [UserController::class, 'index']);
});