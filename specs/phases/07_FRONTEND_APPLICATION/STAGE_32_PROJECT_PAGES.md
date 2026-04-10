# STAGE_32 — Project Pages

> **Phase:** 07_FRONTEND_APPLICATION
> **Status:** NOT STARTED
> **Scope:** Project management UI, tasks, workflows, team
> **Risk Level:** HIGH

## Stage Status

Status: NOT STARTED
Step: —
Risk Level: HIGH

## Objective

Implement all project management frontend pages including project CRUD, task management, workflow visualization, and team management using **Nuxt UI** components.

## Scope

### Frontend Pages

| Page             | Route                       | Description                                                 |
| ---------------- | --------------------------- | ----------------------------------------------------------- |
| Project Listing  | /projects                   | Role-scoped project list                                    |
| Project Create   | /projects/create            | Multi-step creation wizard                                  |
| Project Detail   | /projects/:id               | Tabbed view (overview, phases, tasks, team, docs, timeline) |
| Task Board       | /projects/:id/tasks         | Kanban board + list view                                    |
| Task Detail      | /projects/:id/tasks/:taskId | Task detail modal/page                                      |
| Workflow View    | /projects/:id/workflow      | Workflow step visualization                                 |
| Team Management  | /projects/:id/team          | Team member management                                      |
| Document Browser | /projects/:id/documents     | Project documents                                           |
| Cost Estimator   | /projects/:id/estimates     | BOQ builder and estimate management                         |

### Nuxt UI Component Map

| Element                 | Nuxt UI Component                                   |
| ----------------------- | --------------------------------------------------- |
| Project detail tabs     | `UTabs`                                             |
| Status badge            | `UBadge` (color per status)                         |
| Project creation wizard | `USteppers`                                         |
| Kanban board            | `USortable` (drag-and-drop columns)                 |
| Task card               | `UCard` with `UBadge` priority + assignee `UAvatar` |
| Task detail modal       | `UModal` (full-screen on mobile)                    |
| Workflow stepper        | `USteppers` (horizontal, status-colored)            |
| Approval dialog         | `UModal` + `UAlert` (confirm/reject)                |
| Team member list        | `UTable` + `UAvatar` + role `UBadge`                |
| Add team member         | `UModal` + `USelectMenu` (user search)              |
| Document browser        | `UTable` + file type `UIcon`                        |
| BOQ table               | `UTable` (editable rows)                            |
| Progress bar            | `UProgress`                                         |
| Empty state             | `ULandingCard` with CTA                             |

### Components

- `ProjectWizard` — `USteppers` multi-step form (info → team → phases → confirm)
- `TaskBoard` — Kanban with drag-and-drop using `USortable`
- `TaskCard` — `UCard` for board/list view
- `WorkflowStepper` — `USteppers` for workflow progress visualization
- `ApprovalAction` — `UModal` confirm dialog with approve/reject actions
- `GanttChart` — Timeline component (optional, custom SVG or third-party)
- `TeamMemberList` — `UTable` with role badges and remove action
- `DocumentBrowser` — File manager with `UTable` + upload `UButton`
- `BOQBuilder` — Editable `UTable` with line-item management

## Testing

### Unit Tests (Vitest)

- `useProjectStatus` — valid status transitions (DRAFT→PLANNING, etc.)
- `useTaskBoard` — column sorting, drag-and-drop state
- `useWorkflow` — approval action dispatches correct API call

### E2E Tests (Playwright)

| Test Case                           | Scenario                                                        |
| ----------------------------------- | --------------------------------------------------------------- |
| Project creation wizard             | Complete all steps → project created → redirect to detail       |
| Task kanban drag-and-drop           | Drag task card to "In Review" → status updated via API          |
| Workflow approval flow              | Admin approves task → status changes → toast notification       |
| Team member add                     | Click "Add Member" → select user → confirm → appears in list    |
| Document upload                     | Upload PDF → appears in document browser with correct icon      |
| Project status badge colors correct | PLANNING → blue badge, IN_PROGRESS → green, ON_HOLD → orange    |
| BOQ builder adds/removes rows       | Add item → total updates; remove row → total updates            |
| Project detail tabs navigate        | Click "Tasks" tab → task board visible; "Team" tab → team table |

```typescript
// tests/e2e/projects.spec.ts
import {test, expect} from '@playwright/test'

test('project creation wizard completes all steps', async ({page}) => {
  await page.goto('/projects/create')
  // Step 1: Project Info
  await page.fill('[data-testid="project-name"]', 'مشروع اختباري')
  await page.click('[data-testid="wizard-next"]')
  // Step 2: Team
  await page.click('[data-testid="wizard-next"]')
  // Step 3: Phases
  await page.click('[data-testid="wizard-next"]')
  // Confirm
  await page.click('[data-testid="wizard-submit"]')
  await expect(page).toHaveURL(/\/projects\/\d+/)
})
```

## Dependencies

- **Upstream:** STAGE_12-16 (all project management stages), STAGE_29_NUXT_SHELL
- **Downstream:** None
