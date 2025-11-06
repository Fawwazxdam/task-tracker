# Task Tracker API Guide

This guide provides comprehensive documentation for integrating with the Task Tracker API built with Laravel Sanctum. The API follows a project-based architecture where tasks are organized under projects.

## Base URL
```
https://task-tracker.test/api
```

**Note:** Laravel Herd automatically redirects HTTP to HTTPS

## Authentication

All project and task endpoints require authentication using Bearer tokens.

### Login
**Endpoint:** `POST /login`

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password"
}
```

**Success Response (200):**
```json
{
  "user": {
    "id": 1,
    "name": "User Name",
    "email": "user@example.com",
    "email_verified_at": null,
    "created_at": "2025-09-21T06:44:13.000000Z",
    "updated_at": "2025-09-21T06:44:13.000000Z"
  },
  "token": "1|abc123def456..."
}
```

**Error Response (422):**
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": [
      "The provided credentials are incorrect."
    ]
  }
}
```

### Register (if implemented)
**Endpoint:** `POST /register`

**Request Body:**
```json
{
  "name": "User Name",
  "email": "user@example.com",
  "password": "password",
  "password_confirmation": "password"
}
```

### Logout
**Endpoint:** `POST /logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "message": "Logged out successfully"
}
```

### Get Current User
**Endpoint:** `GET /user`

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "id": 1,
  "name": "User Name",
  "email": "user@example.com",
  "email_verified_at": null,
  "created_at": "2025-09-21T06:44:13.000000Z",
  "updated_at": "2025-09-21T06:44:13.000000Z"
}
```

## Projects API

### Headers for Authenticated Requests
```
Authorization: Bearer {token}
Content-Type: application/json
```

### List Projects
**Endpoint:** `GET /projects`

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Project Alpha",
      "description": "First project",
      "owner_id": 1,
      "status": "active",
      "created_at": "2025-09-21T06:44:13.000000Z",
      "updated_at": "2025-09-21T06:44:13.000000Z",
      "tasks_count": 5,
      "backlog_tasks_count": 2,
      "owner": {
        "id": 1,
        "name": "User Name",
        "email": "user@example.com"
      }
    }
  ]
}
```

### Create Project
**Endpoint:** `POST /projects`

**Request Body:**
```json
{
  "name": "New Project",
  "description": "Project description"
}
```

**Validation Rules:**
- `name`: required, string, max 255 characters
- `description`: optional, string

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "uuid": "550e8400-e29b-41d4-a716-446655440001",
    "name": "New Project",
    "description": "Project description",
    "owner_id": 1,
    "status": "active",
    "created_at": "2025-09-21T06:44:13.000000Z",
    "updated_at": "2025-09-21T06:44:13.000000Z",
    "owner": {
      "id": 1,
      "name": "User Name",
      "email": "user@example.com"
    }
  },
  "message": "Project created successfully"
}
```

### Get Project Details
**Endpoint:** `GET /projects/{id}`

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "project": {
      "id": 1,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Project Alpha",
      "description": "First project",
      "owner_id": 1,
      "status": "active",
      "created_at": "2025-09-21T06:44:13.000000Z",
      "updated_at": "2025-09-21T06:44:13.000000Z",
      "owner": {
        "id": 1,
        "name": "User Name",
        "email": "user@example.com"
      }
    },
    "tasks_by_status": {
      "backlog": [
        {
          "id": 1,
          "title": "Backlog Task",
          "status": "backlog",
          "priority": "high",
          "type": "feature",
          "assignee": {
            "id": 1,
            "name": "User Name",
            "email": "user@example.com"
          }
        }
      ],
      "todo": [],
      "in_progress": [],
      "done": []
    },
    "stats": {
      "total_tasks": 1,
      "backlog_tasks": 1,
      "todo_tasks": 0,
      "in_progress_tasks": 0,
      "done_tasks": 0,
      "completion_rate": 0
    }
  }
}
```

### Update Project
**Endpoint:** `PUT /projects/{id}`

**Request Body:**
```json
{
  "name": "Updated Project Name",
  "description": "Updated description"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Updated Project Name",
    "description": "Updated description",
    "owner_id": 1,
    "status": "active"
  },
  "message": "Project updated successfully"
}
```

### Delete Project
**Endpoint:** `DELETE /projects/{id}`

**Success Response (200):**
```json
{
  "success": true,
  "message": "Project deleted successfully"
}
```

### Get Project Backlog
**Endpoint:** `GET /projects/{projectId}/backlog`

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "project_id": 1,
      "user_id": 1,
      "title": "Backlog Task",
      "description": "Task in backlog",
      "status": "backlog",
      "priority": "high",
      "type": "feature",
      "story_points": 5,
      "due_date": null,
      "created_at": "2025-09-21T06:44:13.000000Z",
      "updated_at": "2025-09-21T06:44:13.000000Z",
      "assignee": {
        "id": 1,
        "name": "User Name",
        "email": "user@example.com"
      }
    }
  ],
  "project": {
    "id": 1,
    "name": "Project Alpha"
  }
}
```

### Add Task to Backlog
**Endpoint:** `POST /projects/{projectId}/backlog`

**Request Body:**
```json
{
  "title": "New Backlog Task",
  "description": "Task description",
  "type": "feature",
  "priority": "medium",
  "story_points": 3
}
```

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "title": "New Backlog Task",
    "status": "backlog",
    "priority": "medium",
    "type": "feature",
    "story_points": 3,
    "assignee": {
      "id": 1,
      "name": "User Name",
      "email": "user@example.com"
    }
  },
  "message": "Task added to backlog successfully"
}
```

### Get Project Statistics
**Endpoint:** `GET /projects/{projectId}/stats`

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "status_stats": {
      "backlog": 2,
      "todo": 3,
      "in_progress": 1,
      "done": 5
    },
    "type_stats": {
      "feature": 6,
      "bug": 3,
      "chore": 2
    },
    "priority_stats": {
      "low": 2,
      "medium": 5,
      "high": 3,
      "critical": 1
    },
    "total_tasks": 11,
    "completed_tasks": 5,
    "completion_rate": 45.45
  }
}
```

## Tasks API

Tasks are organized under projects. All task endpoints are nested under `/projects/{projectId}/`.

### List Tasks
**Endpoint:** `GET /projects/{projectId}/tasks`

**Query Parameters:**
- `status`: Filter by status (backlog, todo, in_progress, done, all)
- `type`: Filter by type (feature, bug, chore, enhancement, all)
- `priority`: Filter by priority (low, medium, high, critical, all)
- `assignee`: Filter by assignee user ID (all for no filter)
- `sort_by`: Sort field (created_at, title, priority, due_date)
- `sort_order`: Sort order (asc, desc)
- `per_page`: Items per page (default: 15)

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "project_id": 1,
        "user_id": 1,
        "title": "Complete feature",
        "description": "Implement user authentication",
        "status": "in_progress",
        "priority": "high",
        "type": "feature",
        "story_points": 8,
        "due_date": "2025-12-31",
        "created_at": "2025-09-21T06:44:13.000000Z",
        "updated_at": "2025-09-21T06:44:13.000000Z",
        "assignee": {
          "id": 1,
          "name": "User Name",
          "email": "user@example.com"
        },
        "project": {
          "id": 1,
          "name": "Project Alpha",
          "uuid": "550e8400-e29b-41d4-a716-446655440000"
        }
      }
    ],
    "per_page": 15,
    "total": 1
  }
}
```

### Create Task
**Endpoint:** `POST /projects/{projectId}/tasks`

**Request Body:**
```json
{
  "title": "New Task",
  "description": "Task description",
  "type": "feature",
  "status": "todo",
  "priority": "medium",
  "story_points": 5,
  "due_date": "2025-12-31",
  "user_id": 2
}
```

**Validation Rules:**
- `title`: required, string, max 255 characters
- `description`: optional, string
- `type`: required, enum: `feature`, `bug`, `chore`, `enhancement`
- `status`: required, enum: `backlog`, `todo`, `in_progress`, `done`
- `priority`: required, enum: `low`, `medium`, `high`, `critical`
- `story_points`: optional, integer, 1-20
- `due_date`: optional, date, after today
- `user_id`: optional, exists in users table (only project owner can assign to others)

**Success Response (201):**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "project_id": 1,
    "user_id": 1,
    "title": "New Task",
    "description": "Task description",
    "status": "todo",
    "priority": "medium",
    "type": "feature",
    "story_points": 5,
    "due_date": "2025-12-31",
    "created_at": "2025-09-21T06:44:13.000000Z",
    "updated_at": "2025-09-21T06:44:13.000000Z",
    "assignee": {
      "id": 1,
      "name": "User Name",
      "email": "user@example.com"
    },
    "project": {
      "id": 1,
      "name": "Project Alpha"
    }
  },
  "message": "Task created successfully"
}
```

### Get Task
**Endpoint:** `GET /projects/{projectId}/tasks/{taskId}`

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "project_id": 1,
    "user_id": 1,
    "title": "Complete feature",
    "description": "Implement user authentication",
    "status": "in_progress",
    "priority": "high",
    "type": "feature",
    "story_points": 8,
    "due_date": "2025-12-31",
    "created_at": "2025-09-21T06:44:13.000000Z",
    "updated_at": "2025-09-21T06:44:13.000000Z",
    "project": {
      "id": 1,
      "name": "Project Alpha",
      "owner": {
        "id": 1,
        "name": "User Name",
        "email": "user@example.com"
      }
    },
    "assignee": {
      "id": 1,
      "name": "User Name",
      "email": "user@example.com"
    }
  }
}
```

### Update Task
**Endpoint:** `PUT /projects/{projectId}/tasks/{taskId}`

**Request Body:** (only include fields to update)
```json
{
  "title": "Updated Task Title",
  "status": "done",
  "priority": "critical",
  "story_points": 10,
  "user_id": 3
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Updated Task Title",
    "status": "done",
    "priority": "critical",
    "story_points": 10,
    "user_id": 3,
    "assignee": {
      "id": 3,
      "name": "Other User",
      "email": "other@example.com"
    },
    "project": {
      "id": 1,
      "name": "Project Alpha"
    }
  },
  "message": "Task updated successfully"
}
```

### Update Task Status
**Endpoint:** `PATCH /projects/{projectId}/tasks/{taskId}/status`

**Request Body:**
```json
{
  "status": "in_progress"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "in_progress",
    "assignee": {
      "id": 1,
      "name": "User Name",
      "email": "user@example.com"
    }
  },
  "message": "Task status updated successfully"
}
```

### Move Task from Backlog
**Endpoint:** `PATCH /projects/{projectId}/tasks/{taskId}/move`

**Request Body:**
```json
{
  "status": "todo",
  "user_id": 2
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "todo",
    "user_id": 2,
    "assignee": {
      "id": 2,
      "name": "Assigned User",
      "email": "assigned@example.com"
    }
  },
  "message": "Task moved from backlog successfully"
}
```

### Delete Task
**Endpoint:** `DELETE /projects/{projectId}/tasks/{taskId}`

**Success Response (200):**
```json
{
  "success": true,
  "message": "Task deleted successfully"
}
```

### Search Tasks
**Endpoint:** `GET /projects/{projectId}/tasks-search`

**Query Parameters:**
- `q`: Search query (searches in title and description)
- `per_page`: Items per page (default: 15)

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "Complete authentication",
        "description": "Implement user login",
        "status": "todo",
        "assignee": {
          "id": 1,
          "name": "User Name",
          "email": "user@example.com"
        },
        "project": {
          "id": 1,
          "name": "Project Alpha"
        }
      }
    ],
    "per_page": 15,
    "total": 1
  }
}
```

## Data Types and Enums

### Task Status
- `backlog`: Task is in backlog, not yet active
- `todo`: Task is ready to be worked on
- `in_progress`: Task is currently being worked on
- `done`: Task is completed

### Task Priority
- `low`: Low priority
- `medium`: Medium priority
- `high`: High priority
- `critical`: Critical priority

### Task Type
- `feature`: New feature implementation
- `bug`: Bug fix
- `chore`: Maintenance or cleanup task
- `enhancement`: Feature enhancement

### Project Status
- `active`: Project is active
- Other statuses may be added in the future

## Error Handling

### Common HTTP Status Codes
- `200`: Success
- `201`: Created
- `403`: Forbidden (unauthorized access)
- `404`: Not Found
- `422`: Validation Error
- `500`: Server Error

### Authentication Errors
If token is invalid or missing:
```json
{
  "message": "Unauthenticated."
}
```

### Validation Errors
```json
{
  "success": false,
  "errors": {
    "title": [
      "The title field is required."
    ],
    "status": [
      "The selected status is invalid."
    ]
  }
}
```

### Authorization Errors
```json
{
  "success": false,
  "message": "Only project owner can assign tasks to other users"
}
```

## Frontend Integration Examples

### JavaScript (Fetch API)
```javascript
// Get projects
const getProjects = async () => {
  const token = localStorage.getItem('token');
  const response = await fetch('/api/projects', {
    headers: {
      'Authorization': `Bearer ${token}`,
    },
  });
  return response.json();
};

// Get project tasks
const getProjectTasks = async (projectId, filters = {}) => {
  const token = localStorage.getItem('token');
  const queryParams = new URLSearchParams(filters);
  const response = await fetch(`/api/projects/${projectId}/tasks?${queryParams}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
    },
  });
  return response.json();
};

// Create task
const createTask = async (projectId, taskData) => {
  const token = localStorage.getItem('token');
  const response = await fetch(`/api/projects/${projectId}/tasks`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(taskData),
  });
  return response.json();
};

// Update task status (for drag and drop)
const updateTaskStatus = async (projectId, taskId, status) => {
  const token = localStorage.getItem('token');
  const response = await fetch(`/api/projects/${projectId}/tasks/${taskId}/status`, {
    method: 'PATCH',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ status }),
  });
  return response.json();
};
```

### React Hook Example
```javascript
import { useState, useEffect } from 'react';

const useProjectTasks = (projectId) => {
  const [tasks, setTasks] = useState([]);
  const [loading, setLoading] = useState(false);
  const [filters, setFilters] = useState({
    status: 'all',
    type: 'all',
    priority: 'all',
    assignee: 'all'
  });

  const fetchTasks = async () => {
    setLoading(true);
    try {
      const token = localStorage.getItem('token');
      const queryParams = new URLSearchParams(filters);
      const response = await fetch(`/api/projects/${projectId}/tasks?${queryParams}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
        },
      });
      const result = await response.json();
      if (result.success) {
        setTasks(result.data.data);
      }
    } catch (error) {
      console.error('Error fetching tasks:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (projectId) {
      fetchTasks();
    }
  }, [projectId, filters]);

  return {
    tasks,
    loading,
    filters,
    setFilters,
    refetch: fetchTasks
  };
};
```

## Notes

- All dates are in ISO 8601 format (YYYY-MM-DD)
- Tasks and projects use soft deletes
- Story points are optional and used for agile estimation
- Project owners have additional permissions (assigning tasks, deleting projects)
- Users can access projects they own or have tasks assigned to them
- Backlog tasks are sorted by priority (critical > high > medium > low)
- The API supports pagination for large datasets
- All responses include a `success` field to indicate operation status