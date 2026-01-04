<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class TaskController extends Controller
{
    /**
     * Get all tasks for a project with filtering and pagination
     */
    public function index(Request $request, $projectId)
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

            $query = $project->tasks()->with(['assignee', 'project']);

            // Apply filters
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('type') && $request->type !== 'all') {
                $query->where('type', $request->type);
            }

            if ($request->has('priority') && $request->priority !== 'all') {
                $query->where('priority', $request->priority);
            }

            if ($request->has('assignee') && $request->assignee !== 'all') {
                $query->where('user_id', $request->assignee);
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $tasks = $query->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $tasks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new task in a project
     */
    public function store(Request $request, $projectId)
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
                'status' => 'required|in:backlog,todo,in_progress,done',
                'priority' => 'required|in:low,medium,high,critical',
                'story_points' => 'nullable|integer|min:1|max:20',
                'due_date' => 'nullable|date|after:today',
                'user_id' => 'nullable|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Authorization: Only project owner can assign tasks to others
            if ($request->has('user_id') && $request->user_id != Auth::id()) {
                if ($project->owner_id !== Auth::id()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only project owner can assign tasks to other users'
                    ], 403);
                }
            }

            $taskData = array_merge($request->all(), [
                'uuid' => Uuid::uuid4()->toString(),
                'project_id' => $project->id,
                'user_id' => $request->user_id ?? Auth::id(),
            ]);

            $task = Task::create($taskData);

            return response()->json([
                'success' => true,
                'data' => $task->load(['assignee', 'project']),
                'message' => 'Task created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific task
     */
    public function show($projectId, $taskId)
    {
        try {
            $task = Task::with(['project.owner', 'assignee'])
                ->where('id', $taskId)
                ->whereHas('project', function ($query) use ($projectId) {
                    $query->where('uuid', $projectId)
                        ->where(function ($q) {
                            $q->where('owner_id', Auth::id())
                                ->orWhereHas('tasks', function ($qq) {
                                    $qq->where('user_id', Auth::id());
                                });
                        });
                })
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $task
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update a task
     */
    public function update(Request $request, $projectId, $taskId)
    {
        try {
            $task = Task::with('project')
                ->where('id', $taskId)
                ->whereHas('project', function ($query) use ($projectId) {
                    $query->where('uuid', $projectId)
                        ->where(function ($q) {
                            $q->where('owner_id', Auth::id())
                                ->orWhereHas('tasks', function ($qq) {
                                    $qq->where('user_id', Auth::id());
                                });
                        });
                })
                ->firstOrFail();

            // Authorization: Only project owner can assign tasks to others
            if ($request->has('user_id') && $request->user_id != $task->user_id) {
                if ($task->project->owner_id !== Auth::id()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only project owner can reassign tasks'
                    ], 403);
                }
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'sometimes|in:feature,bug,chore,enhancement',
                'status' => 'sometimes|in:backlog,todo,in_progress,done',
                'priority' => 'sometimes|in:low,medium,high,critical',
                'story_points' => 'nullable|integer|min:1|max:20',
                'due_date' => 'nullable|date|after:today',
                'user_id' => 'nullable|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $task->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $task->fresh()->load(['assignee', 'project']),
                'message' => 'Task updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update task status only (for drag and drop)
     */
    public function updateStatus(Request $request, $projectId, $taskId)
    {
        try {
            $task = Task::where('id', $taskId)
                ->whereHas('project', function ($query) use ($projectId) {
                    $query->where('uuid', $projectId)
                        ->where(function ($q) {
                            $q->where('owner_id', Auth::id())
                                ->orWhereHas('tasks', function ($qq) {
                                    $qq->where('user_id', Auth::id());
                                });
                        });
                })
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:backlog,todo,in_progress,done',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $task->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'data' => $task->fresh()->load('assignee'),
                'message' => 'Task status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Move a task from backlog to active status
     */
    public function moveFromBacklog(Request $request, $projectId, $taskId)
    {
        try {
            $task = Task::with('project')
                ->where('id', $taskId)
                ->where('status', 'backlog')
                ->whereHas('project', function ($query) use ($projectId) {
                    $query->where('uuid', $projectId)
                        ->where(function ($q) {
                            $q->where('owner_id', Auth::id())
                                ->orWhereHas('tasks', function ($qq) {
                                    $qq->where('user_id', Auth::id());
                                });
                        });
                })
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:todo,in_progress',
                'user_id' => 'nullable|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = ['status' => $request->status];

            // Only project owner can assign to other users
            if ($request->has('user_id') && $request->user_id != Auth::id()) {
                if ($task->project->owner_id === Auth::id()) {
                    $updateData['user_id'] = $request->user_id;
                }
            }

            $task->update($updateData);

            return response()->json([
                'success' => true,
                'data' => $task->fresh()->load('assignee'),
                'message' => 'Task moved from backlog successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to move task from backlog',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a task
     */
    public function destroy($projectId, $taskId)
    {
        try {
            $task = Task::where('id', $taskId)
                ->whereHas('project', function ($query) use ($projectId) {
                    $query->where('uuid', $projectId)
                        ->where('owner_id', Auth::id());
                })
                ->firstOrFail();

            $task->delete();

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get backlog tasks for a project
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
                'uuid' => Uuid::uuid4()->toString(),
                'project_id' => $project->id,
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
     * Search tasks
     */
    public function search(Request $request, $projectId)
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

            $query = $project->tasks()->with(['assignee', 'project']);

            if ($request->has('q') && !empty($request->q)) {
                $searchTerm = $request->q;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'like', "%{$searchTerm}%")
                        ->orWhere('description', 'like', "%{$searchTerm}%");
                });
            }

            $tasks = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $tasks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search tasks',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
