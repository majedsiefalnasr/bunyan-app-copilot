# STAGE_14 — Workflow Engine

> **Phase:** 03_PROJECT_MANAGEMENT
> **Status:** NOT STARTED
> **Scope:** Approval workflows, state machine, configurable rules
> **Risk Level:** HIGH

## Stage Status

Status: NOT STARTED
Step: —
Risk Level: HIGH

## Objective

Implement the workflow engine powering approval chains, status transitions, and configurable business rules. Core state machine: Pending → In Progress → Complete → Paid.

## Scope

### Backend

- Workflow definition model (configurable per project type)
- Workflow step model (ordered steps with approval roles)
- Workflow instance model (active workflow execution)
- Workflow engine service (advance, approve, reject, escalate)
- Approval rule model (who can approve which transitions)
- Notification triggers on workflow transitions
- Workflow history/audit log

### Frontend

- Workflow configuration page (Admin)
- Workflow visualization component (step progress)
- Approval action interface (approve/reject with notes)
- Workflow history timeline
- Pending approvals dashboard widget

### API Endpoints

| Method | Route                                   | Description                |
| ------ | --------------------------------------- | -------------------------- |
| GET    | /api/v1/workflows                       | List workflow definitions  |
| POST   | /api/v1/workflows                       | Create workflow definition |
| GET    | /api/v1/workflows/{id}                  | Get workflow definition    |
| POST   | /api/v1/projects/{id}/workflow/start    | Start workflow             |
| PUT    | /api/v1/workflow-instances/{id}/approve | Approve step               |
| PUT    | /api/v1/workflow-instances/{id}/reject  | Reject step                |
| GET    | /api/v1/approvals/pending               | List pending approvals     |

### Database Schema

| Table                | Columns                                                                                                           |
| -------------------- | ----------------------------------------------------------------------------------------------------------------- |
| workflow_definitions | id, name_ar, name_en, description, type, is_active, created_at, updated_at                                        |
| workflow_steps       | id, workflow_definition_id, name_ar, name_en, sort_order, approval_role_id, auto_approve, timeout_hours           |
| workflow_instances   | id, workflow_definition_id, workflowable_type, workflowable_id, current_step_id, status, started_at, completed_at |
| workflow_approvals   | id, workflow_instance_id, step_id, approved_by, action (approved/rejected), notes, acted_at                       |

## Dependencies

- **Upstream:** STAGE_12_PROJECTS, STAGE_13_TASKS
- **Downstream:** STAGE_19_ORDERS, STAGE_20_PAYMENTS
