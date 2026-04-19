# Plan Report — Projects

> **Phase:** 03_PROJECT_MANAGEMENT > **Generated:** 2026-04-19T00:03:00Z

## Plan Summary

| Metric         | Value                                       |
| -------------- | ------------------------------------------- |
| New Tables     | 2 (projects, project_phases)                |
| New Endpoints  | 10                                          |
| New Services   | 2 (ProjectService, ProjectPhaseService)     |
| New Pages      | 4 (listing, detail, create wizard, edit)    |
| New Components | 14                                          |
| New Enums      | 3 (ProjectStatus, ProjectType, PhaseStatus) |
| Form Requests  | 5                                           |
| API Resources  | 2 + collection                              |

## Architecture Decisions

- Status machine lives on `ProjectStatus` enum with `allowedTransitions()` method — no external library
- `ProjectPhase` does NOT extend `BaseModel` (no SoftDeletes — hard-delete by design)
- Role-scoped visibility stubs empty results for Contractor/Architect/Engineer until STAGE_15 team assignment
- Optimistic locking via `updated_at` comparison for concurrent status transitions
- `owner_id` validated as Customer-role user in StoreProjectRequest
- Controllers placed under `app/Http/Controllers/Api/` per codebase convention
- DELETE route added for projects (admin-only soft-delete)

## Guardian Verdicts

| Guardian              | Verdict | Notes                                                               |
| --------------------- | ------- | ------------------------------------------------------------------- |
| Architecture Guardian | PASS    | 2 advisory: ProjectPhase no BaseModel (justified), DELETE route gap |
| API Designer          | PASS    | 7 advisory: controller namespace, filter format, pagination meta    |

## Risk Assessment

| Risk Level | Count | Details                                                       |
| ---------- | ----- | ------------------------------------------------------------- |
| HIGH       | 1     | Status machine validation — complex bidirectional transitions |
| MEDIUM     | 2     | Role-scoped visibility stub, optimistic locking concurrency   |
| LOW        | 1     | Bilingual support (established pattern)                       |

## File Inventory

### Backend (23+ files)

- 2 migrations, 2 models, 3 enums, 2 repositories, 2 services
- 2 controllers, 5 form requests, 2 resources + collection
- 1 policy, 1 route file

### Frontend (24+ files)

- 4 pages, 14 components, 1 Pinia store, 2 composables, route config

### Tests (8+ files)

- Unit tests for services, enums
- Feature tests for API endpoints, RBAC, status transitions
