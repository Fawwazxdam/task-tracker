<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics for the authenticated user
     */
    public function stats()
    {
        try {
            // Get all projects where user is owner or has assigned tasks
            $projectIds = Project::where('owner_id', Auth::id())
                ->orWhereHas('tasks', function ($query) {
                    $query->where('user_id', Auth::id());
                })
                ->pluck('id');

            if ($projectIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'total_tasks' => 0,
                        'completed' => 0,
                        'in_progress' => 0,
                        'overdue' => 0
                    ]
                ]);
            }

            // Get task statistics
            $tasks = Task::whereIn('project_id', $projectIds)->get();

            $stats = [
                'total_tasks' => $tasks->count(),
                'completed' => $tasks->where('status', 'done')->count(),
                'in_progress' => $tasks->where('status', 'in_progress')->count(),
                'overdue' => $tasks->where('due_date', '<', now())
                    ->where('status', '!=', 'done')
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent tasks for the authenticated user
     */
    public function recentTasks(Request $request)
    {
        try {
            $limit = $request->get('limit', 5);

            // Get all projects where user is owner or has assigned tasks
            $projectIds = Project::where('owner_id', Auth::id())
                ->orWhereHas('tasks', function ($query) {
                    $query->where('user_id', Auth::id());
                })
                ->pluck('id');

            if ($projectIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            $recentTasks = Task::whereIn('project_id', $projectIds)
                ->with(['project:id,name', 'assignee:id,name'])
                ->orderBy('updated_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'title' => $task->title,
                        'status' => $task->status,
                        'priority' => $task->priority,
                        'project_name' => $task->project->name,
                        'assignee' => $task->assignee,
                        'updated_at' => $task->updated_at
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $recentTasks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recent tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
