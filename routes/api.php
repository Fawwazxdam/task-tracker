<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected routes (require authentication via Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Project routes
    Route::apiResource('projects', \App\Http\Controllers\ProjectController::class);
    Route::get('projects/{projectId}/backlog', [\App\Http\Controllers\ProjectController::class, 'backlog']);
    Route::post('projects/{projectId}/backlog', [\App\Http\Controllers\ProjectController::class, 'addToBacklog']);
    Route::get('projects/{projectId}/stats', [\App\Http\Controllers\ProjectController::class, 'stats']);

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
});