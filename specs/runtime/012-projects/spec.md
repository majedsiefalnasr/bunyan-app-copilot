# Feature Specification: Projects (المشاريع)

**Feature Branch**: `spec/012-projects`  
**Created**: 2026-04-19  
**Status**: Draft  
**Phase**: 03_PROJECT_MANAGEMENT  
**Stage**: STAGE_12_PROJECTS  
**Input**: Project CRUD, phases, timelines, status tracking for a construction services marketplace

---

## User Scenarios & Testing _(mandatory)_

### User Story 1 — Admin Creates a New Construction Project (Priority: P1)

An Admin creates a new construction project by entering project details (Arabic/English name, description, city, district, geolocation, type, estimated budget, and timeline). The project starts in DRAFT status. The Admin can view the newly created project in their dashboard.

**Why this priority**: Project creation is the foundation of the entire project management module. Without the ability to create projects, no downstream features (phases, tasks, workflow, team management) can function.

**Independent Test**: Can be fully tested by submitting the project creation form and verifying the project appears in the listing with DRAFT status and correct data.

**Acceptance Scenarios**:

1. **Given** an authenticated Admin, **When** they submit the project creation form with valid data (name_ar, name_en, description, city, type, budget_estimated, start_date, end_date, owner_id), **Then** a project is created with status DRAFT, the specified Customer is set as owner (owner_id), and the project appears in the project listing.
2. **Given** an authenticated Admin, **When** they submit the project creation form with missing required fields (e.g., no name_ar), **Then** a 422 VALIDATION_ERROR response is returned with field-level details.
3. **Given** an authenticated Admin, **When** they submit the project creation form with an end_date before start_date, **Then** a 422 VALIDATION_ERROR response is returned indicating invalid date range.
4. **Given** an unauthenticated user, **When** they attempt to create a project, **Then** a 401 AUTH_TOKEN_EXPIRED or AUTH_INVALID_CREDENTIALS error is returned.

---

### User Story 2 — Role-Scoped Project Listing (Priority: P1)

Each user role sees only the projects they are authorized to view. Admins see all projects. Customers see projects they own. Contractors, Supervising Architects, and Field Engineers see projects they are assigned to.

**Why this priority**: Listing is the primary entry point for all users. RBAC-scoped visibility is a hard security requirement that must be enforced from the start.

**Independent Test**: Can be tested by logging in as different roles and verifying each role sees only their authorized projects.

**Acceptance Scenarios**:

1. **Given** an authenticated Admin, **When** they request GET /api/v1/projects, **Then** all projects are returned with pagination metadata.
2. **Given** an authenticated Customer, **When** they request GET /api/v1/projects, **Then** only projects where they are the owner are returned.
3. **Given** an authenticated Contractor, **When** they request GET /api/v1/projects, **Then** only projects where they are assigned as a team member are returned.
4. **Given** an authenticated Supervising Architect, **When** they request GET /api/v1/projects, **Then** only projects where they are assigned are returned.
5. **Given** a user with no assigned projects, **When** they request the project listing, **Then** an empty data array with pagination metadata is returned (not an error).
6. **Given** an authenticated user, **When** they request projects with filter parameters (status, type, city), **Then** results are filtered accordingly within their role-scoped visibility.

---

### User Story 3 — Project Detail View (Priority: P1)

A user views detailed information about a specific project, including overview data, phases, and timeline. The detail view uses tabbed navigation (overview, phases, tasks, team, documents, timeline).

**Why this priority**: Detail view is the primary working context for all project participants and is required for all downstream features.

**Independent Test**: Can be tested by navigating to a project detail page and verifying all tabs render with correct data.

**Acceptance Scenarios**:

1. **Given** an authenticated user with access to a project, **When** they request GET /api/v1/projects/{id}, **Then** the full project data is returned including nested relationships.
2. **Given** an authenticated user without access to a project, **When** they request GET /api/v1/projects/{id}, **Then** a 403 RBAC_ROLE_DENIED error is returned.
3. **Given** a request for a non-existent project ID, **When** the API is called, **Then** a 404 RESOURCE_NOT_FOUND error is returned.
4. **Given** a soft-deleted project, **When** a non-Admin requests it, **Then** a 404 RESOURCE_NOT_FOUND error is returned.

---

### User Story 4 — Project Status Transitions (Priority: P2)

An Admin transitions a project through its lifecycle statuses: DRAFT → PLANNING → IN_PROGRESS → ON_HOLD → COMPLETED → CLOSED. Each transition is validated server-side against the allowed transitions. Invalid transitions are rejected.

**Why this priority**: Status management is critical for workflow and business logic, but depends on project creation being stable first.

**Independent Test**: Can be tested by transitioning a project through valid statuses and verifying each transition succeeds, and that invalid transitions are rejected.

**Acceptance Scenarios**:

1. **Given** a project in DRAFT status, **When** an Admin transitions it to PLANNING, **Then** the status is updated and the change is persisted.
2. **Given** a project in DRAFT status, **When** an Admin attempts to transition it directly to COMPLETED, **Then** a 422 WORKFLOW_INVALID_TRANSITION error is returned.
3. **Given** a project in IN_PROGRESS status, **When** an Admin transitions it to ON_HOLD, **Then** the status is updated and the end_date is not modified.
4. **Given** a project in ON_HOLD status, **When** an Admin transitions it back to IN_PROGRESS, **Then** the status is restored.
5. **Given** a project in COMPLETED status, **When** an Admin transitions it to CLOSED, **Then** the project is finalized and no further transitions are allowed.
6. **Given** a non-Admin user, **When** they attempt to transition a project status, **Then** a 403 RBAC_ROLE_DENIED error is returned.

---

### User Story 5 — Project Update (Priority: P2)

An Admin or the project owner (Customer) updates project details such as name, description, budget, and dates. Only editable fields can be modified; status changes use the dedicated status endpoint.

**Why this priority**: Updates are essential for project lifecycle management but secondary to creation and viewing.

**Independent Test**: Can be tested by updating project fields and verifying the changes are persisted and returned in subsequent reads.

**Acceptance Scenarios**:

1. **Given** an authenticated Admin, **When** they update a project's name_ar, description, and budget_estimated, **Then** the project is updated and a success response is returned.
2. **Given** an authenticated Customer who owns the project, **When** they update the project, **Then** the update succeeds for allowed fields.
3. **Given** a project in CLOSED status, **When** an Admin attempts to update it, **Then** a 422 WORKFLOW_INVALID_TRANSITION error is returned (closed projects are immutable).
4. **Given** a Contractor, **When** they attempt to update a project, **Then** a 403 RBAC_ROLE_DENIED error is returned.

---

### User Story 6 — Project Phases Management (Priority: P2)

An Admin adds phases to a project. Phases represent major construction stages (e.g., Foundation, Structure, Finishing). Each phase has Arabic/English names, dates, sort order, and a completion percentage.

**Why this priority**: Phases are the primary organizational structure within a project and are required by downstream Task and Workflow stages.

**Independent Test**: Can be tested by adding phases to a project and verifying they appear in the phases listing with correct ordering.

**Acceptance Scenarios**:

1. **Given** an authenticated Admin and an existing project, **When** they add a phase with valid data (name_ar, name_en, sort_order, start_date, end_date), **Then** the phase is created and linked to the project.
2. **Given** an authenticated Admin, **When** they list phases for a project via GET /api/v1/projects/{id}/phases, **Then** phases are returned ordered by sort_order.
3. **Given** a phase with start_date before the project's start_date, or end_date after the project's end_date, **When** submitted, **Then** a 422 VALIDATION_ERROR is returned. Phase dates must be fully contained within the project's date range. If the project's start_date or end_date is NULL, the corresponding containment check is skipped.
4. **Given** an authenticated Admin, **When** they update a phase via PUT /api/v1/projects/{id}/phases/{phaseId} with valid data, **Then** the phase is updated and the updated data is returned.
5. **Given** an authenticated Admin, **When** they delete a phase via DELETE /api/v1/projects/{id}/phases/{phaseId}, **Then** the phase is removed and a success response is returned.
6. **Given** a non-Admin user, **When** they attempt to add, update, or delete a phase, **Then** a 403 RBAC_ROLE_DENIED error is returned.

---

### User Story 7 — Project Timeline View (Priority: P3)

Users view a timeline/Gantt-style visualization of a project's phases and milestones. The timeline shows phase durations, overlaps, and completion status.

**Why this priority**: Timeline is a read-only visualization that enhances project comprehension but is not a blocker for core CRUD functionality.

**Independent Test**: Can be tested by requesting the timeline endpoint and verifying the returned data includes all phases with their date ranges and completion percentages.

**Acceptance Scenarios**:

1. **Given** a project with multiple phases, **When** a user requests GET /api/v1/projects/{id}/timeline, **Then** a structured timeline response is returned with phases ordered chronologically.
2. **Given** a project with no phases, **When** the timeline is requested, **Then** an empty timeline structure is returned (not an error).

---

### User Story 8 — Project Creation Wizard (Frontend) (Priority: P3)

The frontend provides a multi-step wizard for creating projects. Steps include: Basic Info → Location → Budget & Timeline → Review & Submit. The wizard supports Arabic/English input with full RTL layout.

**Why this priority**: The wizard improves UX but the underlying API (User Story 1) can function with a simpler form.

**Independent Test**: Can be tested by completing all wizard steps and verifying the project is created successfully.

**Acceptance Scenarios**:

1. **Given** a user on the project creation wizard, **When** they complete all steps and submit, **Then** the project is created via the API and the user is redirected to the project detail page.
2. **Given** a user on step 2 of the wizard, **When** they navigate back to step 1, **Then** their previously entered data is preserved.
3. **Given** a user on the wizard, **When** they submit with validation errors, **Then** errors are displayed inline on the relevant step.

---

### Edge Cases

- What happens when a project's budget_estimated is zero or negative? → Reject with VALIDATION_ERROR; budget must be a positive number.
- What happens when two phases have the same sort_order? → Allow but discourage; sort is stable and insertion order is used as tiebreaker.
- What happens when a project is deleted (soft-delete) and then queried? → Returns 404 for non-Admin users; Admin can query with `?with_trashed=true` filter.
- What happens when the owner (Customer) account is deactivated? → Project remains accessible to Admin and assigned team members; owner field becomes read-only.
- What happens when location coordinates are outside valid ranges? → Reject with VALIDATION_ERROR; lat must be [-90, 90], lng must be [-180, 180].
- How does the system handle concurrent status transitions? → Use optimistic locking (updated_at check) to prevent race conditions.

---

## Requirements _(mandatory)_

### Functional Requirements

- **FR-001**: System MUST allow Admins to create projects with Arabic/English names, description, location (city, district, lat/lng), type, estimated budget, timeline (start_date, end_date), and owner_id (FK to users, must reference a Customer-role user).
- **FR-002**: System MUST enforce role-based project visibility — Admins see all; Customers see owned; Contractors/Architects/Engineers see assigned.
- **FR-003**: System MUST enforce the project status machine: DRAFT → PLANNING → IN_PROGRESS → ON_HOLD → COMPLETED → CLOSED with validated transitions.
- **FR-004**: System MUST allow ON_HOLD ↔ IN_PROGRESS bidirectional transitions for project pausing and resumption.
- **FR-005**: System MUST prevent any modifications to projects in CLOSED status.
- **FR-006**: System MUST support full CRUD for project phases (project_phases) with name_ar, name_en, sort_order, status, date range, and completion_percentage. Phases can be created, listed, updated, and deleted by Admins.
- **FR-007**: System MUST support filtering projects by status, type, city, and date range within role-scoped queries.
- **FR-008**: System MUST support pagination on all list endpoints with standardized meta (current_page, per_page, total, last_page).
- **FR-009**: System MUST use soft-deletes on projects; only Admins can view soft-deleted records.
- **FR-010**: System MUST return project data via API Resources with support for nested includes (phases, timeline data).
- **FR-011**: System MUST validate all inputs via Form Request classes (StoreProjectRequest, UpdateProjectRequest, StoreProjectPhaseRequest, UpdateProjectPhaseRequest, TransitionProjectStatusRequest).
- **FR-012**: System MUST provide a timeline endpoint returning chronologically ordered phases with date ranges and completion data.
- **FR-013**: System MUST accept an explicit owner_id in the project creation payload. The owner_id MUST reference a user with the Customer role. If owner_id is not provided, the system MUST reject the request with VALIDATION_ERROR.
- **FR-014**: Frontend MUST provide a multi-step project creation wizard with Arabic RTL support.
- **FR-015**: Frontend MUST display project listings as cards with status badges, filterable by status/type/city.
- **FR-016**: Frontend MUST provide a tabbed project detail page (overview, phases, tasks, team, documents, timeline).

### Key Entities

- **Project (مشروع)**: A construction job with bilingual naming, geographic location, budget tracking (estimated vs actual), timeline (start/end dates), type classification (residential/commercial/infrastructure), and lifecycle status. Owned by a Customer; managed by Admin. Soft-deletable.
- **Project Phase (مرحلة المشروع)**: A major stage within a project (e.g., Foundation, Structure, Finishing). Has bilingual naming, sort ordering, date range, and completion percentage. Belongs to one Project. Status is a simple enum: pending, in_progress, completed (no validated transition machine — Admin sets directly).
- **Project Status**: An enum representing the project lifecycle: DRAFT, PLANNING, IN_PROGRESS, ON_HOLD, COMPLETED, CLOSED. Transitions are validated server-side.
- **Project Type**: A classification enum: residential (سكني), commercial (تجاري), infrastructure (بنية تحتية).

### Status Transition Map

```
DRAFT ──────→ PLANNING
PLANNING ───→ IN_PROGRESS
IN_PROGRESS → ON_HOLD
IN_PROGRESS → COMPLETED
ON_HOLD ────→ IN_PROGRESS
COMPLETED ──→ CLOSED
```

Forbidden transitions: Any not listed above (e.g., DRAFT → COMPLETED, CLOSED → anything).

---

## Non-Functional Requirements

- **NFR-001**: Project listing API MUST respond within 500ms for up to 1,000 projects with standard pagination (15 per page).
- **NFR-002**: All API responses MUST follow the unified error contract: `{ success, data, error: { code, message, details } }`.
- **NFR-003**: All user-facing text (error messages, labels) MUST support Arabic and English localization.
- **NFR-004**: Frontend MUST render correctly in RTL mode with full Nuxt UI component compatibility.
- **NFR-005**: Database queries MUST use repository pattern with proper indexing on owner_id, status, type, and city columns.
- **NFR-006**: Soft-deleted projects MUST NOT appear in any non-Admin query results.
- **NFR-007**: All project mutations MUST be logged with structured logging (correlation IDs, user context).

---

## Success Criteria _(mandatory)_

### Measurable Outcomes

- **SC-001**: Admins can create, update, and manage projects through the complete status lifecycle (DRAFT → CLOSED) without errors.
- **SC-002**: Each of the 5 user roles sees only their authorized projects — verified by RBAC tests for all roles.
- **SC-003**: Project listing supports filtering by status, type, and city with paginated responses returning within 500ms.
- **SC-004**: Project phases can be added, listed, and ordered within a project.
- **SC-005**: Invalid status transitions are rejected with the correct WORKFLOW_INVALID_TRANSITION error code.
- **SC-006**: The frontend project creation wizard completes successfully with Arabic RTL input in under 3 minutes.
- **SC-007**: All API endpoints pass validation, RBAC, and error contract compliance tests.

---

## Out of Scope

- **Team assignment/management** — Handled by STAGE_15_TEAM_MANAGEMENT.
- **Tasks within phases** — Handled by STAGE_13_TASKS.
- **Workflow engine (approval chains, reporting rules)** — Handled by STAGE_14_WORKFLOW_ENGINE.
- **Document/file uploads for projects** — Deferred to a future stage; tabs for documents/team will be present as placeholders in the UI.
- **Project deletion by non-Admin users** — Only soft-delete by Admin is supported.
- **Budget tracking calculations (actual vs estimated)** — budget_actual is stored but not computed; calculation logic is deferred.
- **Notifications on status changes** — Deferred to notification system stage.
- **Gantt chart interactive editing** — Timeline view is read-only in this stage.

---

## Assumptions

- RBAC system (STAGE_04) is fully implemented with role middleware and the `UserRole` enum.
- API foundation (STAGE_06) is in place with `BaseApiController`, `ApiResponseTrait`, pagination support, and correlation ID middleware.
- The `ApiErrorCode` enum includes all required error codes (VALIDATION_ERROR, WORKFLOW_INVALID_TRANSITION, RBAC_ROLE_DENIED, RESOURCE_NOT_FOUND).
- Authentication via Laravel Sanctum is operational.
- The frontend uses Nuxt UI components with Tailwind CSS v4 and supports RTL layout via `dir="rtl"`.
- The team_members relationship (for Contractor/Architect/Engineer scoping) will use a pivot table introduced in STAGE_15; until then, role-scoped listing for non-Customer/Admin roles will return empty results or use a stub.
- MySQL database with UTF-8mb4 encoding for Arabic text support.
- Geolocation data (lat/lng) is stored as DECIMAL(10,7) for sufficient precision.

---

## API Contract Summary

| Method | Route                                  | Auth | Roles                   | Description                                      |
| ------ | -------------------------------------- | ---- | ----------------------- | ------------------------------------------------ |
| GET    | /api/v1/projects                       | Yes  | All authenticated       | List projects (role-scoped, filtered, paginated) |
| POST   | /api/v1/projects                       | Yes  | Admin                   | Create a new project                             |
| GET    | /api/v1/projects/{id}                  | Yes  | Role-scoped access      | Get project details with includes                |
| PUT    | /api/v1/projects/{id}                  | Yes  | Admin, Owner (Customer) | Update project (non-CLOSED only)                 |
| PUT    | /api/v1/projects/{id}/status           | Yes  | Admin                   | Transition project status                        |
| GET    | /api/v1/projects/{id}/phases           | Yes  | Role-scoped access      | List phases for a project                        |
| POST   | /api/v1/projects/{id}/phases           | Yes  | Admin                   | Add a phase to a project                         |
| PUT    | /api/v1/projects/{id}/phases/{phaseId} | Yes  | Admin                   | Update a phase                                   |
| DELETE | /api/v1/projects/{id}/phases/{phaseId} | Yes  | Admin                   | Delete a phase                                   |
| GET    | /api/v1/projects/{id}/timeline         | Yes  | Role-scoped access      | Get project timeline data                        |

---

## Database Schema Summary

### projects

| Column           | Type                                                      | Constraints                    |
| ---------------- | --------------------------------------------------------- | ------------------------------ |
| id               | BIGINT UNSIGNED                                           | PK, AUTO_INCREMENT             |
| owner_id         | BIGINT UNSIGNED                                           | FK → users.id, INDEX           |
| name_ar          | VARCHAR(255)                                              | NOT NULL                       |
| name_en          | VARCHAR(255)                                              | NOT NULL                       |
| description      | TEXT                                                      | NULLABLE                       |
| city             | VARCHAR(100)                                              | NOT NULL, INDEX                |
| district         | VARCHAR(100)                                              | NULLABLE                       |
| location_lat     | DECIMAL(10,7)                                             | NULLABLE                       |
| location_lng     | DECIMAL(10,7)                                             | NULLABLE                       |
| status           | ENUM(draft,planning,in_progress,on_hold,completed,closed) | NOT NULL, DEFAULT draft, INDEX |
| type             | ENUM(residential,commercial,infrastructure)               | NOT NULL, INDEX                |
| budget_estimated | DECIMAL(15,2)                                             | NULLABLE                       |
| budget_actual    | DECIMAL(15,2)                                             | NULLABLE, DEFAULT 0            |
| start_date       | DATE                                                      | NULLABLE                       |
| end_date         | DATE                                                      | NULLABLE                       |
| created_at       | TIMESTAMP                                                 | Laravel default                |
| updated_at       | TIMESTAMP                                                 | Laravel default                |
| deleted_at       | TIMESTAMP                                                 | NULLABLE (soft-delete)         |

### project_phases

| Column                | Type                                | Constraints                      |
| --------------------- | ----------------------------------- | -------------------------------- |
| id                    | BIGINT UNSIGNED                     | PK, AUTO_INCREMENT               |
| project_id            | BIGINT UNSIGNED                     | FK → projects.id, INDEX          |
| name_ar               | VARCHAR(255)                        | NOT NULL                         |
| name_en               | VARCHAR(255)                        | NOT NULL                         |
| sort_order            | INTEGER UNSIGNED                    | NOT NULL, DEFAULT 0              |
| status                | ENUM(pending,in_progress,completed) | NOT NULL, DEFAULT 'pending'      |
| start_date            | DATE                                | NULLABLE                         |
| end_date              | DATE                                | NULLABLE                         |
| completion_percentage | TINYINT UNSIGNED                    | NOT NULL, DEFAULT 0, CHECK 0–100 |
| created_at            | TIMESTAMP                           | Laravel default                  |
| updated_at            | TIMESTAMP                           | Laravel default                  |

**Indexes**: projects(owner_id), projects(status), projects(type), projects(city), project_phases(project_id, sort_order).

---

## Dependencies

### Upstream (Required)

- **STAGE_04_RBAC_SYSTEM**: UserRole enum, role middleware, permission checks
- **STAGE_06_API_FOUNDATION**: BaseApiController, ApiResponseTrait, pagination, correlation IDs, error contract

### Downstream (Depends on this)

- **STAGE_13_TASKS**: Tasks belong to project phases
- **STAGE_14_WORKFLOW_ENGINE**: Workflow rules apply to project status transitions
- **STAGE_15_TEAM_MANAGEMENT**: Team assignment to projects (pivot table)
