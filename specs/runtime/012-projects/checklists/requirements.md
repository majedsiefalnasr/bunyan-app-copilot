# Requirements Checklist — Projects (المشاريع)

**Feature Branch**: `spec/012-projects`  
**Generated**: 2026-04-19  
**Source**: specs/runtime/012-projects/spec.md

---

## Backend Requirements

### Models & Database

- [ ] **DB-001**: Create `projects` migration with all columns (id, owner_id, name_ar, name_en, description, city, district, location_lat, location_lng, status, type, budget_estimated, budget_actual, start_date, end_date, timestamps, soft_deletes)
- [ ] **DB-002**: Create `project_phases` migration with all columns (id, project_id, name_ar, name_en, sort_order, status, start_date, end_date, completion_percentage, timestamps)
- [ ] **DB-003**: Add indexes on projects(owner_id, status, type, city) and project_phases(project_id, sort_order)
- [ ] **DB-004**: Add foreign key constraints (projects.owner_id → users.id, project_phases.project_id → projects.id with CASCADE delete)
- [ ] **DB-005**: Create `Project` Eloquent model with SoftDeletes, relationships (owner, phases), casts, and fillable
- [ ] **DB-006**: Create `ProjectPhase` Eloquent model with relationships (project), casts, and fillable
- [ ] **DB-007**: Create `ProjectStatus` enum (DRAFT, PLANNING, IN_PROGRESS, ON_HOLD, COMPLETED, CLOSED)
- [ ] **DB-008**: Create `ProjectType` enum (RESIDENTIAL, COMMERCIAL, INFRASTRUCTURE)

### Services & Repositories

- [ ] **SVC-001**: Create `ProjectRepository` extending BaseRepository with role-scoped queries, filtering (status, type, city, date range), and soft-delete handling
- [ ] **SVC-002**: Create `ProjectService` with create, update, delete, getById, and list methods
- [ ] **SVC-003**: Implement status transition logic in ProjectService with valid transition map validation
- [ ] **SVC-004**: Create `ProjectPhaseService` for phase CRUD within a project
- [ ] **SVC-005**: Create `ProjectTimelineService` for timeline data aggregation
- [ ] **SVC-006**: Implement role-scoped project visibility in repository (Admin=all, Customer=owned, others=assigned)

### Controllers & API

- [ ] **API-001**: Create `ProjectController` extending BaseApiController with index, store, show, update methods
- [ ] **API-002**: Implement PUT /api/v1/projects/{id}/status endpoint for status transitions
- [ ] **API-003**: Create `ProjectPhaseController` with index and store methods
- [ ] **API-004**: Create GET /api/v1/projects/{id}/timeline endpoint
- [ ] **API-005**: Register all routes in routes/api/v1 with auth:sanctum and RBAC middleware
- [ ] **API-006**: Apply pagination on list endpoints using BaseApiController::paginated()

### Form Requests

- [ ] **VAL-001**: Create `StoreProjectRequest` — validate name_ar (required, max:255), name_en (required, max:255), city (required), type (required, in:residential,commercial,infrastructure), budget_estimated (nullable, numeric, min:0), start_date/end_date (nullable, date, end_date >= start_date), location_lat (nullable, between:-90,90), location_lng (nullable, between:-180,180)
- [ ] **VAL-002**: Create `UpdateProjectRequest` — same as store but all fields optional; reject if project is CLOSED
- [ ] **VAL-003**: Create `StoreProjectPhaseRequest` — validate name_ar, name_en, sort_order, start_date, end_date (within project range), completion_percentage (0-100)
- [ ] **VAL-004**: Create `TransitionProjectStatusRequest` — validate status (required, in valid transitions from current status)

### API Resources

- [ ] **RES-001**: Create `ProjectResource` with nested includes support (phases, owner)
- [ ] **RES-002**: Create `ProjectPhaseResource`
- [ ] **RES-003**: Create `ProjectTimelineResource`

### Policies

- [ ] **POL-001**: Create `ProjectPolicy` — viewAny (all authenticated), view (Admin, owner, assigned), create (Admin), update (Admin, owner for non-CLOSED), delete (Admin), transitionStatus (Admin)
- [ ] **POL-002**: Create `ProjectPhasePolicy` — view (same as project access), create (Admin), update (Admin)

### Error Handling

- [ ] **ERR-001**: Use VALIDATION_ERROR (422) for invalid inputs
- [ ] **ERR-002**: Use WORKFLOW_INVALID_TRANSITION (422) for invalid status transitions
- [ ] **ERR-003**: Use RBAC_ROLE_DENIED (403) for unauthorized actions
- [ ] **ERR-004**: Use RESOURCE_NOT_FOUND (404) for missing or soft-deleted projects

---

## Frontend Requirements

### Pages

- [ ] **FE-001**: Create project listing page at `/projects` with role-scoped data, search, and filters (status, type, city)
- [ ] **FE-002**: Create project detail page at `/projects/{id}` with tabbed navigation (overview, phases, tasks, team, documents, timeline)
- [ ] **FE-003**: Create project creation wizard at `/projects/create` with steps: Basic Info → Location → Budget & Timeline → Review & Submit

### Components

- [ ] **FE-004**: Create `ProjectCard` component — displays project name, status badge, type, city, budget, dates
- [ ] **FE-005**: Create `ProjectStatusBadge` component — color-coded status indicator using Nuxt UI UBadge
- [ ] **FE-006**: Create `ProjectTimeline` component — Gantt-style read-only timeline visualization
- [ ] **FE-007**: Create project creation form steps as individual components
- [ ] **FE-008**: Create project filter sidebar/toolbar component

### Composables & Stores

- [ ] **FE-009**: Create `useProjects` composable for API calls (list, get, create, update, transitionStatus)
- [ ] **FE-010**: Create `useProjectPhases` composable for phase API calls
- [ ] **FE-011**: Create Pinia `projectStore` for project state management
- [ ] **FE-012**: Implement pagination composable integration for project listing

### RTL & i18n

- [ ] **FE-013**: All project pages and components MUST render correctly in RTL mode
- [ ] **FE-014**: All labels, placeholders, and messages MUST use i18n translation keys (Arabic primary, English secondary)
- [ ] **FE-015**: Form inputs MUST support Arabic text entry with proper RTL alignment

### Validation (Frontend)

- [ ] **FE-016**: Implement client-side validation on wizard steps using VeeValidate + Zod schemas
- [ ] **FE-017**: Display server-side validation errors inline on relevant form fields

---

## Testing Requirements

### Backend Tests

- [ ] **TEST-001**: Unit test — ProjectStatus enum transition validation (all valid transitions pass, all invalid transitions fail)
- [ ] **TEST-002**: Unit test — ProjectType enum values
- [ ] **TEST-003**: Feature test — POST /api/v1/projects (create project with valid data, returns 201)
- [ ] **TEST-004**: Feature test — POST /api/v1/projects (validation errors return 422 with field details)
- [ ] **TEST-005**: Feature test — GET /api/v1/projects (Admin sees all, Customer sees owned, others see assigned)
- [ ] **TEST-006**: Feature test — GET /api/v1/projects/{id} (authorized access returns 200, unauthorized returns 403)
- [ ] **TEST-007**: Feature test — PUT /api/v1/projects/{id} (update succeeds for Admin/owner, fails for others)
- [ ] **TEST-008**: Feature test — PUT /api/v1/projects/{id}/status (valid transitions succeed, invalid fail with WORKFLOW_INVALID_TRANSITION)
- [ ] **TEST-009**: Feature test — POST /api/v1/projects/{id}/phases (add phase with valid data)
- [ ] **TEST-010**: Feature test — GET /api/v1/projects/{id}/phases (phases returned in sort_order)
- [ ] **TEST-011**: Feature test — GET /api/v1/projects/{id}/timeline (timeline data structure)
- [ ] **TEST-012**: Feature test — RBAC enforcement on all endpoints for all 5 roles
- [ ] **TEST-013**: Feature test — Soft-delete: deleted projects hidden from non-Admin queries
- [ ] **TEST-014**: Feature test — Pagination metadata correctness on list endpoints

### Frontend Tests

- [ ] **TEST-015**: Vitest — ProjectCard renders with correct data
- [ ] **TEST-016**: Vitest — ProjectStatusBadge renders correct color for each status
- [ ] **TEST-017**: Vitest — Project creation wizard step navigation preserves data
- [ ] **TEST-018**: Vitest — Project listing filters correctly update API query parameters
- [ ] **TEST-019**: Vitest — Project detail tabs render correct content

---

## Cross-Cutting Requirements

- [ ] **XC-001**: All API responses follow unified error contract `{ success, data, error }`
- [ ] **XC-002**: Structured logging with correlation IDs on all project mutations
- [ ] **XC-003**: Forward-only database migrations with rollback (`down()`) methods
- [ ] **XC-004**: Arabic/English bilingual support on all user-facing text
- [ ] **XC-005**: All protected routes use auth:sanctum + RBAC middleware
