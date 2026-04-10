---
name: workflow-engine-patterns
description: Construction workflow engine patterns and state machines
---

# Workflow Engine Patterns — Bunyan

## Overview

The workflow engine manages construction project lifecycle:
Project → Phases → Tasks → Reports

## Workflow States

### Project Status
```
Draft → Active → OnHold → Active → Completed
  ↓                                      ↓
Cancelled                           Archived
```

### Phase Status
```
Pending → InProgress → Review → Approved
  ↓          ↓          ↓
Rejected  Paused    Rejected → InProgress
```

### Task Status
```
Todo → InProgress → WaitingApproval → Approved → Done
  ↓       ↓              ↓
Blocked  Paused       Rejected → InProgress
```

## State Machine Implementation

```php
class ProjectStateMachine
{
    private const TRANSITIONS = [
        ProjectStatus::Draft->value => [ProjectStatus::Active, ProjectStatus::Cancelled],
        ProjectStatus::Active->value => [ProjectStatus::OnHold, ProjectStatus::Completed, ProjectStatus::Cancelled],
        ProjectStatus::OnHold->value => [ProjectStatus::Active, ProjectStatus::Cancelled],
        ProjectStatus::Completed->value => [ProjectStatus::Archived],
    ];

    public function canTransition(ProjectStatus $from, ProjectStatus $to): bool
    {
        $allowed = self::TRANSITIONS[$from->value] ?? [];
        return in_array($to, $allowed, true);
    }

    public function transition(Project $project, ProjectStatus $to): Project
    {
        if (!$this->canTransition($project->status, $to)) {
            throw new InvalidStateTransitionException(
                "Cannot transition from {$project->status->value} to {$to->value}"
            );
        }

        $project->status = $to;
        $project->save();

        event(new ProjectStatusChanged($project, $to));

        return $project;
    }
}
```

## Role-Based Workflow Rules

| Action | Customer | Contractor | Architect | Field Eng. | Admin |
|--------|----------|------------|-----------|------------|-------|
| Create Project | ✅ | ❌ | ❌ | ❌ | ✅ |
| Assign Contractor | ❌ | ❌ | ❌ | ❌ | ✅ |
| Create Phase | ❌ | ✅ | ✅ | ❌ | ✅ |
| Create Task | ❌ | ✅ | ✅ | ✅ | ✅ |
| Update Task Progress | ❌ | ✅ | ❌ | ✅ | ✅ |
| Approve Task | ❌ | ❌ | ✅ | ❌ | ✅ |
| Approve Phase | ✅ | ❌ | ✅ | ❌ | ✅ |
| Upload Report | ❌ | ✅ | ✅ | ✅ | ✅ |

## Workflow Integrity Rules

1. **Phase can't complete** until all child tasks are Approved/Done
2. **Project can't complete** until all phases are Approved
3. **Task can't start** if predecessor tasks are not Done
4. **Budget tracking** must update on task completion
5. **All state transitions** must be logged in an audit trail
6. **Reports required** before phase review (configurable per workflow config)

## Audit Trail Pattern

```php
class WorkflowAuditLog extends Model
{
    protected $fillable = [
        'auditable_type',  // Project, Phase, Task
        'auditable_id',
        'action',          // status_changed, assigned, created
        'from_status',
        'to_status',
        'user_id',
        'metadata',        // JSON - extra context
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
```
