# STAGE_13 — Tasks

> **Phase:** 03_PROJECT_MANAGEMENT
> **Status:** NOT STARTED
> **Scope:** Task management within projects, assignment, tracking
> **Risk Level:** MEDIUM

## Stage Status

Status: NOT STARTED
Step: —
Risk Level: MEDIUM

## Objective

Implement task management within projects. Tasks are assigned to team members, tracked by status, and linked to project phases.

## Scope

### Backend

- Task Eloquent model (belongs to project, phase, assignee)
- Task service (CRUD, assignment, status transitions, dependencies)
- Task status machine: TODO → IN_PROGRESS → IN_REVIEW → DONE → BLOCKED
- Task priority levels: LOW, MEDIUM, HIGH, URGENT
- Task comment model
- Task API endpoints

### Frontend

- Task board view (Kanban-style)
- Task list view with filters
- Task detail modal/page
- Task creation form
- Task assignment interface
- Task comment thread

### API Endpoints

| Method | Route                       | Description        |
| ------ | --------------------------- | ------------------ |
| GET    | /api/v1/projects/{id}/tasks | List project tasks |
| POST   | /api/v1/projects/{id}/tasks | Create task        |
| GET    | /api/v1/tasks/{id}          | Get task details   |
| PUT    | /api/v1/tasks/{id}          | Update task        |
| PUT    | /api/v1/tasks/{id}/assign   | Assign task        |
| PUT    | /api/v1/tasks/{id}/status   | Transition status  |
| POST   | /api/v1/tasks/{id}/comments | Add comment        |

### Database Schema

| Table             | Columns                                                                                                                                                                                       |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| tasks             | id, project_id, phase_id, assigned_to, title_ar, title_en, description, status, priority, due_date, estimated_hours, actual_hours, sort_order, created_by, created_at, updated_at, deleted_at |
| task_comments     | id, task_id, user_id, body, created_at, updated_at                                                                                                                                            |
| task_dependencies | id, task_id, depends_on_task_id                                                                                                                                                               |

## Dependencies

- **Upstream:** STAGE_12_PROJECTS
- **Downstream:** STAGE_14_WORKFLOW_ENGINE
