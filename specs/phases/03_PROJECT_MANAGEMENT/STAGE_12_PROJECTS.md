# STAGE_12 — Projects

> **Phase:** 03_PROJECT_MANAGEMENT
> **Status:** NOT STARTED
> **Scope:** Project CRUD, phases, timelines, status tracking
> **Risk Level:** HIGH

## Stage Status

Status: DRAFT
Step: specify
Risk Level: HIGH
Last Updated: 2026-04-19T00:00:00Z

Scope Defined:

- Project CRUD with Admin/Owner roles
- Role-scoped project listing with RBAC
- Project status machine (6 states, validated transitions)
- Project phases management
- Timeline endpoint (read-only)
- Frontend pages: listing, detail, creation wizard

Deferred Scope:

- Team assignment (STAGE_15)
- Tasks within phases (STAGE_13)
- Workflow engine (STAGE_14)
- Document uploads, budget calculations, notifications

Architecture Governance Compliance:

- Specification drafted — governance audit pending

Notes:
Specification complete. Clarification step pending.

## Objective

Implement the core project management module. Projects represent construction jobs with phases, timelines, and role-based participation.

## Scope

### Backend

- Project Eloquent model with relationships (owner, team, phases, tasks, documents)
- Project phase model (sub-phases within a project)
- Project service (CRUD, status transitions, team assignment)
- Project repository with filtering, role-scoped queries
- Project timeline service
- Project API resource with nested includes
- Project Form Request validation
- Project status machine: DRAFT → PLANNING → IN_PROGRESS → ON_HOLD → COMPLETED → CLOSED

### Frontend

- Project listing page (role-scoped views)
- Project detail page with tabs (overview, phases, tasks, team, documents, timeline)
- Project creation wizard (multi-step form)
- Project timeline/Gantt view component
- Project status badge component
- Project card component

### API Endpoints

| Method | Route                          | Description                 |
| ------ | ------------------------------ | --------------------------- |
| GET    | /api/v1/projects               | List projects (role-scoped) |
| POST   | /api/v1/projects               | Create project              |
| GET    | /api/v1/projects/{id}          | Get project details         |
| PUT    | /api/v1/projects/{id}          | Update project              |
| PUT    | /api/v1/projects/{id}/status   | Transition status           |
| GET    | /api/v1/projects/{id}/phases   | List project phases         |
| POST   | /api/v1/projects/{id}/phases   | Add phase                   |
| GET    | /api/v1/projects/{id}/timeline | Get project timeline        |

### Database Schema

| Table          | Columns                                                                                                                                                                                                                                  |
| -------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| projects       | id, owner_id, name_ar, name_en, description, city, district, location_lat, location_lng, status, type (residential/commercial/infrastructure), budget_estimated, budget_actual, start_date, end_date, created_at, updated_at, deleted_at |
| project_phases | id, project_id, name_ar, name_en, sort_order, status, start_date, end_date, completion_percentage                                                                                                                                        |

## Dependencies

- **Upstream:** STAGE_04_RBAC_SYSTEM, STAGE_06_API_FOUNDATION
- **Downstream:** STAGE_13_TASKS, STAGE_14_WORKFLOW_ENGINE, STAGE_15_TEAM_MANAGEMENT
