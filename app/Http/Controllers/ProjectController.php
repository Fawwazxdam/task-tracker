<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class ProjectController extends Controller
{
    /**
     * Get all projects for the authenticated user
     */
    public function index()
    {
        try {
            // Get projects where user is owner or has tasks assigned
            $projects = Project::where('owner_id', Auth::id())
                ->orWhereHas('tasks', function ($query) {
                    $query->where('user_id', Auth::id());
                })
                ->withCount(['tasks', 'tasks as backlog_tasks_count' => function ($query) {
                    $query->where('status', 'backlog');
                }])
                ->with('owner')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $projects
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new project
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $project = Project::create([
                'uuid' => Uuid::uuid4()->toString(),
                'name' => $request->name,
                'description' => $request->description,
                'owner_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $project->load('owner'),
                'message' => 'Project created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific project with its tasks grouped by status
     */
    public function show($id)
    {
        try {
            Log::info("Project show called with id: {$id}, user_id: " . Auth::id());
            $project = Project::with(['owner', 'tasks.assignee'])
                ->where('uuid', $id)
                ->where(function ($query) {
                    $query->where('owner_id', Auth::id())
                        ->orWhereHas('tasks', function ($q) {
                            $q->where('user_id', Auth::id());
                        });
                })
                ->firstOrFail();

            // Group tasks by status for easier frontend processing
            $tasksByStatus = [
                'backlog' => $project->tasks->where('status', 'backlog')->sortByDesc('priority')->values(),
                'todo' => $project->tasks->where('status', 'todo')->sortByDesc('priority')->values(),
                'in_progress' => $project->tasks->where('status', 'in_progress')->sortByDesc('priority')->values(),
                'done' => $project->tasks->where('status', 'done')->sortByDesc('priority')->values(),
            ];

            // Get statistics
            $stats = [
                'total_tasks' => $project->tasks->count(),
                'backlog_tasks' => $tasksByStatus['backlog']->count(),
                'todo_tasks' => $tasksByStatus['todo']->count(),
                'in_progress_tasks' => $tasksByStatus['in_progress']->count(),
                'done_tasks' => $tasksByStatus['done']->count(),
                'completion_rate' => $project->tasks->count() > 0
                    ? round(($tasksByStatus['done']->count() / $project->tasks->count()) * 100, 2)
                    : 0,
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'project' => $project,
                    'tasks_by_status' => $tasksByStatus,
                    'stats' => $stats
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update a project
     */
    public function update(Request $request, $id)
    {
        try {
            $project = Project::where('uuid', $id)
                ->where('owner_id', Auth::id())
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $project->update($request->only(['name', 'description']));

            return response()->json([
                'success' => true,
                'data' => $project->fresh(),
                'message' => 'Project updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a project
     */
    public function destroy($id)
    {
        try {
            $project = Project::where('uuid', $id)
                ->where('owner_id', Auth::id())
                ->firstOrFail();

            $project->delete();

            return response()->json([
                'success' => true,
                'message' => 'Project deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete project',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get backlog items for a project (tasks with status 'backlog')
     */
    public function backlog($projectId)
    {
        try {
            $project = Project::where('uuid', $projectId)
                ->where(function ($query) {
                    $query->where('owner_id', Auth::id())
                        ->orWhereHas('tasks', function ($q) {
                            $q->where('user_id', Auth::id());
                        });
                })
                ->firstOrFail();

            $backlogTasks = $project->tasks()
                ->where('status', 'backlog')
                ->with('assignee')
                ->orderByRaw("FIELD(priority, 'critical', 'high', 'medium', 'low')")
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $backlogTasks,
                'project' => $project->only(['id', 'name'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch backlog',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a task to backlog
     */
    public function addToBacklog(Request $request, $projectId)
    {
        try {
            $project = Project::where('uuid', $projectId)
                ->where(function ($query) {
                    $query->where('owner_id', Auth::id())
                        ->orWhereHas('tasks', function ($q) {
                            $q->where('user_id', Auth::id());
                        });
                })
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:feature,bug,chore,enhancement',
                'priority' => 'required|in:low,medium,high,critical',
                'story_points' => 'nullable|integer|min:1|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $task = Task::create([
                'project_id' => $projectId,
                'user_id' => Auth::id(),
                'title' => $request->title,
                'description' => $request->description,
                'type' => $request->type,
                'status' => 'backlog',
                'priority' => $request->priority,
                'story_points' => $request->story_points,
            ]);

            return response()->json([
                'success' => true,
                'data' => $task->load('assignee'),
                'message' => 'Task added to backlog successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add task to backlog',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get project statistics
     */
    public function stats($projectId)
    {
        try {
            $project = Project::where('uuid', $projectId)
                ->where(function ($query) {
                    $query->where('owner_id', Auth::id())
                        ->orWhereHas('tasks', function ($q) {
                            $q->where('user_id', Auth::id());
                        });
                })
                ->firstOrFail();

            $tasks = $project->tasks()
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            $typeStats = $project->tasks()
                ->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type');

            $priorityStats = $project->tasks()
                ->selectRaw('priority, count(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority');

            return response()->json([
                'success' => true,
                'data' => [
                    'status_stats' => $tasks,
                    'type_stats' => $typeStats,
                    'priority_stats' => $priorityStats,
                    'total_tasks' => $project->tasks()->count(),
                    'completed_tasks' => $project->tasks()->where('status', 'done')->count(),
                    'completion_rate' => $project->tasks()->count() > 0
                        ? round(($project->tasks()->where('status', 'done')->count() / $project->tasks()->count()) * 100, 2)
                        : 0
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch project statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add members to a project
     */
    public function addMembers(Request $request, $projectId)
    {
        try {
            $project = Project::where('uuid', $projectId)
                ->where(function ($query) {
                    $query->where('owner_id', Auth::id())
                        ->orWhereHas('members', function ($q) {
                            $q->where('user_id', Auth::id())
                              ->whereIn('role', ['owner', 'admin']);
                        });
                })
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
                'role' => 'sometimes|string|in:member,admin'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $role = $request->role ?? 'member';
            $userIds = $request->user_ids;

            foreach ($userIds as $userId) {
                $project->members()->syncWithoutDetaching([
                    $userId => ['role' => $role]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Members added successfully',
                'data' => $project->members
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add members',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get project members
     */
    public function members($projectId)
    {
        try {
            $project = Project::where('uuid', $projectId)
                ->where(function ($query) {
                    $query->where('owner_id', Auth::id())
                        ->orWhereHas('members', function ($q) {
                            $q->where('user_id', Auth::id());
                        })
                        ->orWhereHas('tasks', function ($q) {
                            $q->where('user_id', Auth::id());
                        });
                })
                ->firstOrFail();

            $members = $project->members()->get();

            return response()->json([
                'success' => true,
                'data' => $members
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch members',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
