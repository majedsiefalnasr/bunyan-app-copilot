# Technical Plan — Projects (المشاريع)

> **Phase:** 03_PROJECT_MANAGEMENT > **Based on:** `specs/runtime/012-projects/spec.md` > **Created:** 2026-04-19

---

## Architecture Overview

The Projects module introduces the core project entity for the Bunyan construction marketplace. It follows the established layered architecture:

```
Routes → Middleware (auth:sanctum, role) → Controller → Service → Repository → Model
                                             ↓
                                        Form Requests (validation)
                                             ↓
                                        API Resources (response formatting)
                                             ↓
                                        Policies (authorization)
```

Key architectural decisions:

- **Status machine** on the `ProjectStatus` enum with `allowedTransitions()` and `canTransitionTo()` methods (no external state machine library)
- **Role-scoped queries** via `scopeForUser()` on the Project model, with a stub for team-based scoping until STAGE_15
- **Optimistic locking** via `updated_at` comparison for concurrent status transitions
- **Thin controller** pattern with all business logic delegated to `ProjectService` and `ProjectPhaseService`
- **ProjectPhase** does NOT extend `BaseModel` (no SoftDeletes on phases — hard-delete per spec)

---

## Database Design

### New Tables

| Table          | Purpose                                    | Key Columns                                                          |
| -------------- | ------------------------------------------ | -------------------------------------------------------------------- |
| projects       | Core project entity with lifecycle status  | owner_id (FK→users), status (enum), type (enum), city, budget, dates |
| project_phases | Major construction stages within a project | project*id (FK→projects), sort_order, status (enum), completion*%    |

### Modified Tables

_None._

### Eloquent Relationships

```
User ──hasMany──► Project (via owner_id)
Project ──belongsTo──► User (owner)
Project ──hasMany──► ProjectPhase
ProjectPhase ──belongsTo──► Project
```

### Migration Details

See [data-model.md](./data-model.md) for complete column definitions, indexes, and constraints.

**Migration 1:** `create_projects_table` — 17 columns, 5 indexes, 1 FK
**Migration 2:** `create_project_phases_table` — 11 columns, 2 indexes, 1 FK (depends on Migration 1)

---

## Enums

| Enum          | File                          | Values                                                   | Methods                                                |
| ------------- | ----------------------------- | -------------------------------------------------------- | ------------------------------------------------------ |
| ProjectStatus | `app/Enums/ProjectStatus.php` | draft, planning, in_progress, on_hold, completed, closed | `label()`, `allowedTransitions()`, `canTransitionTo()` |
| ProjectType   | `app/Enums/ProjectType.php`   | residential, commercial, infrastructure                  | `label()`                                              |
| PhaseStatus   | `app/Enums/PhaseStatus.php`   | pending, in_progress, completed                          | `label()`                                              |

---

## API Design

### New Endpoints

| Method | Route                                     | Controller@Action                  | Middleware               | Description                           |
| ------ | ----------------------------------------- | ---------------------------------- | ------------------------ | ------------------------------------- |
| GET    | /api/v1/projects                          | ProjectController@index            | auth:sanctum             | List projects (role-scoped, filtered) |
| POST   | /api/v1/projects                          | ProjectController@store            | auth:sanctum, role:admin | Create a new project                  |
| GET    | /api/v1/projects/{project}                | ProjectController@show             | auth:sanctum             | Get project details                   |
| PUT    | /api/v1/projects/{project}                | ProjectController@update           | auth:sanctum             | Update project (policy-authorized)    |
| PUT    | /api/v1/projects/{project}/status         | ProjectController@transitionStatus | auth:sanctum, role:admin | Transition project status             |
| GET    | /api/v1/projects/{project}/phases         | ProjectPhaseController@index       | auth:sanctum             | List phases for a project             |
| POST   | /api/v1/projects/{project}/phases         | ProjectPhaseController@store       | auth:sanctum, role:admin | Add phase to project                  |
| PUT    | /api/v1/projects/{project}/phases/{phase} | ProjectPhaseController@update      | auth:sanctum, role:admin | Update a phase                        |
| DELETE | /api/v1/projects/{project}/phases/{phase} | ProjectPhaseController@destroy     | auth:sanctum, role:admin | Delete a phase                        |
| GET    | /api/v1/projects/{project}/timeline       | ProjectController@timeline         | auth:sanctum             | Get project timeline data             |

### Request/Response Contracts

#### POST /api/v1/projects

**Request:**

```json
{
  "owner_id": 5,
  "name_ar": "مشروع فيلا سكنية",
  "name_en": "Residential Villa Project",
  "description": "Construction of a 3-story residential villa",
  "city": "الرياض",
  "district": "حي النرجس",
  "location_lat": 24.7136,
  "location_lng": 46.6753,
  "type": "residential",
  "budget_estimated": 1500000.0,
  "start_date": "2026-06-01",
  "end_date": "2027-06-01"
}
```

**Response (201):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "owner_id": 5,
    "owner": { "id": 5, "name": "أحمد العميل" },
    "name_ar": "مشروع فيلا سكنية",
    "name_en": "Residential Villa Project",
    "description": "Construction of a 3-story residential villa",
    "city": "الرياض",
    "district": "حي النرجس",
    "location_lat": "24.7136000",
    "location_lng": "46.6753000",
    "status": "draft",
    "type": "residential",
    "budget_estimated": "1500000.00",
    "budget_actual": "0.00",
    "start_date": "2026-06-01",
    "end_date": "2027-06-01",
    "phases_count": 0,
    "created_at": "2026-04-19T12:00:00.000000Z",
    "updated_at": "2026-04-19T12:00:00.000000Z"
  },
  "error": null
}
```

#### PUT /api/v1/projects/{id}/status

**Request:**

```json
{
  "status": "planning",
  "expected_updated_at": "2026-04-19T12:00:00.000000Z"
}
```

**Response (200):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "status": "planning",
    "updated_at": "2026-04-19T12:05:00.000000Z"
  },
  "error": null
}
```

#### POST /api/v1/projects/{id}/phases

**Request:**

```json
{
  "name_ar": "مرحلة الأساسات",
  "name_en": "Foundation Phase",
  "sort_order": 1,
  "start_date": "2026-06-01",
  "end_date": "2026-08-31",
  "completion_percentage": 0
}
```

**Response (201):**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "project_id": 1,
    "name_ar": "مرحلة الأساسات",
    "name_en": "Foundation Phase",
    "sort_order": 1,
    "status": "pending",
    "start_date": "2026-06-01",
    "end_date": "2026-08-31",
    "completion_percentage": 0,
    "created_at": "2026-04-19T12:10:00.000000Z",
    "updated_at": "2026-04-19T12:10:00.000000Z"
  },
  "error": null
}
```

#### GET /api/v1/projects/{id}/timeline

**Response (200):**

```json
{
  "success": true,
  "data": {
    "project": {
      "id": 1,
      "name_ar": "مشروع فيلا سكنية",
      "name_en": "Residential Villa Project",
      "start_date": "2026-06-01",
      "end_date": "2027-06-01",
      "status": "in_progress"
    },
    "phases": [
      {
        "id": 1,
        "name_ar": "مرحلة الأساسات",
        "name_en": "Foundation Phase",
        "start_date": "2026-06-01",
        "end_date": "2026-08-31",
        "status": "completed",
        "completion_percentage": 100,
        "sort_order": 1
      },
      {
        "id": 2,
        "name_ar": "مرحلة الهيكل",
        "name_en": "Structure Phase",
        "start_date": "2026-09-01",
        "end_date": "2027-01-31",
        "status": "in_progress",
        "completion_percentage": 45,
        "sort_order": 2
      }
    ]
  },
  "error": null
}
```

#### GET /api/v1/projects (paginated listing)

**Response (200):**

```json
{
  "success": true,
  "data": [ { "...ProjectResource..." } ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 42,
    "last_page": 3
  },
  "error": null
}
```

---

## Service Layer Design

| Service             | Methods                                                                                                                                                   | Dependencies                              |
| ------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------- |
| ProjectService      | `list(User, filters)`, `find(int)`, `create(array)`, `update(int, array)`, `transitionStatus(int, ProjectStatus, string)`, `timeline(int)`, `delete(int)` | ProjectRepository                         |
| ProjectPhaseService | `listForProject(int)`, `create(int, array)`, `update(int, int, array)`, `delete(int, int)`                                                                | ProjectPhaseRepository, ProjectRepository |

### ProjectService Method Details

```php
class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
    ) {}

    // List projects with role-scoped visibility and filters
    public function list(User $user, array $filters = []): array

    // Find project by ID or throw RESOURCE_NOT_FOUND
    public function find(int $id): Project

    // Create project (validates owner is Customer)
    public function create(array $data): Project

    // Update project (rejects if CLOSED)
    public function update(int $id, array $data): Project

    // Transition status with optimistic locking
    public function transitionStatus(int $id, ProjectStatus $newStatus, string $expectedUpdatedAt): Project

    // Get timeline data (project + ordered phases)
    public function timeline(int $id): array

    // Soft-delete project
    public function delete(int $id): bool
}
```

### ProjectPhaseService Method Details

```php
class ProjectPhaseService
{
    public function __construct(
        private readonly ProjectPhaseRepository $phaseRepository,
        private readonly ProjectRepository $projectRepository,
    ) {}

    // List phases for a project, ordered by sort_order
    public function listForProject(int $projectId): Collection

    // Create phase with date containment validation
    public function create(int $projectId, array $data): ProjectPhase

    // Update phase
    public function update(int $projectId, int $phaseId, array $data): ProjectPhase

    // Delete phase (hard-delete)
    public function delete(int $projectId, int $phaseId): bool
}
```

---

## Repository Layer

| Repository             | Extends        | Custom Methods                                                                |
| ---------------------- | -------------- | ----------------------------------------------------------------------------- |
| ProjectRepository      | BaseRepository | `paginateForUser(User, filters, perPage)`, `findWithPhases(int)`              |
| ProjectPhaseRepository | BaseRepository | `findForProject(int projectId, int phaseId)`, `listForProject(int projectId)` |

---

## Form Requests

| Form Request                   | Validates                                                                                                       |
| ------------------------------ | --------------------------------------------------------------------------------------------------------------- |
| StoreProjectRequest            | name_ar, name_en, city, type (required); description, district, lat, lng, budget, dates, owner_id (conditional) |
| UpdateProjectRequest           | Same as Store but all optional; rejects if project is CLOSED                                                    |
| TransitionProjectStatusRequest | status (required, valid ProjectStatus), expected_updated_at (required, date)                                    |
| StoreProjectPhaseRequest       | name_ar, name_en, sort_order (required); status, dates, completion_percentage (optional)                        |
| UpdateProjectPhaseRequest      | Same as StorePhase but all optional                                                                             |

### Key Validation Rules

```php
// StoreProjectRequest
'owner_id' => ['required', 'integer', 'exists:users,id,deleted_at,NULL'],
'name_ar' => ['required', 'string', 'min:2', 'max:255'],
'name_en' => ['required', 'string', 'min:2', 'max:255'],
'city' => ['required', 'string', 'max:100'],
'type' => ['required', 'string', Rule::in(ProjectType::values())],
'budget_estimated' => ['nullable', 'numeric', 'min:0.01'],
'location_lat' => ['nullable', 'numeric', 'between:-90,90'],
'location_lng' => ['nullable', 'numeric', 'between:-180,180'],
'start_date' => ['nullable', 'date'],
'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],

// TransitionProjectStatusRequest
'status' => ['required', 'string', Rule::in(ProjectStatus::values())],
'expected_updated_at' => ['required', 'date'],

// StoreProjectPhaseRequest — after() validation for date containment
'completion_percentage' => ['nullable', 'integer', 'between:0,100'],
```

---

## API Resources

| Resource             | Output Fields                                                                                                                                                                                                                               |
| -------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ProjectResource      | id, owner_id, owner (whenLoaded), name_ar, name_en, description, city, district, location_lat, location_lng, status, type, budget_estimated, budget_actual, start_date, end_date, phases_count, phases (whenLoaded), created_at, updated_at |
| ProjectPhaseResource | id, project_id, name_ar, name_en, sort_order, status, start_date, end_date, completion_percentage, created_at, updated_at                                                                                                                   |

### ProjectResource Pattern

```php
class ProjectResource extends BaseApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'owner' => new UserSummaryResource($this->whenLoaded('owner')),
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'description' => $this->description,
            'city' => $this->city,
            'district' => $this->district,
            'location_lat' => $this->location_lat,
            'location_lng' => $this->location_lng,
            'status' => $this->status->value,
            'type' => $this->type->value,
            'budget_estimated' => $this->budget_estimated,
            'budget_actual' => $this->budget_actual,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'phases_count' => $this->whenCounted('phases'),
            'phases' => ProjectPhaseResource::collection($this->whenLoaded('phases')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

---

## Policy: ProjectPolicy

| Method           | Admin        | Customer (owner) | Contractor/Architect/Engineer | Unauthenticated |
| ---------------- | ------------ | ---------------- | ----------------------------- | --------------- |
| viewAny          | Gate::before | true             | true                          | false           |
| view             | Gate::before | own project      | assigned (stub: false)        | false           |
| create           | Gate::before | false            | false                         | false           |
| update           | Gate::before | own + not CLOSED | false                         | false           |
| transitionStatus | Gate::before | false            | false                         | false           |
| delete           | Gate::before | false            | false                         | false           |
| addPhase         | Gate::before | false            | false                         | false           |

Admin bypasses all checks via `Gate::before()` in `AppServiceProvider`. The policy handles non-Admin authorization.

---

## Controller Design

### ProjectController

```php
class ProjectController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly ProjectService $projectService,
    ) {}

    // index: authorize('viewAny', Project::class) → service->list(auth()->user(), filters) → paginated response
    // store: StoreProjectRequest → service->create(validated) → ProjectResource (201)
    // show: authorize('view', $project) → service->find($id) → ProjectResource
    // update: authorize('update', $project) → UpdateProjectRequest → service->update($id, validated) → ProjectResource
    // transitionStatus: authorize('transitionStatus', $project) → TransitionProjectStatusRequest → service->transitionStatus() → ProjectResource
    // timeline: authorize('view', $project) → service->timeline($id) → custom response
}
```

### ProjectPhaseController

```php
class ProjectPhaseController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly ProjectPhaseService $phaseService,
    ) {}

    // index: authorize('view', $project) → service->listForProject($projectId) → ProjectPhaseResource collection
    // store: authorize('addPhase', $project) → StoreProjectPhaseRequest → service->create($projectId, validated) → ProjectPhaseResource (201)
    // update: authorize('addPhase', $project) → UpdateProjectPhaseRequest → service->update($projectId, $phaseId, validated) → ProjectPhaseResource
    // destroy: authorize('addPhase', $project) → service->delete($projectId, $phaseId) → success response
}
```

---

## Routes: `routes/api/v1/projects.php`

```php
Route::prefix('projects')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [ProjectController::class, 'index'])
        ->name('api.v1.projects.index');
    Route::post('/', [ProjectController::class, 'store'])
        ->middleware('role:admin')
        ->name('api.v1.projects.store');
    Route::get('{project}', [ProjectController::class, 'show'])
        ->name('api.v1.projects.show');
    Route::put('{project}', [ProjectController::class, 'update'])
        ->name('api.v1.projects.update');
    Route::put('{project}/status', [ProjectController::class, 'transitionStatus'])
        ->middleware('role:admin')
        ->name('api.v1.projects.transition-status');
    Route::get('{project}/timeline', [ProjectController::class, 'timeline'])
        ->name('api.v1.projects.timeline');

    // Phases (nested resource)
    Route::prefix('{project}/phases')->group(function () {
        Route::get('/', [ProjectPhaseController::class, 'index'])
            ->name('api.v1.projects.phases.index');
        Route::post('/', [ProjectPhaseController::class, 'store'])
            ->middleware('role:admin')
            ->name('api.v1.projects.phases.store');
        Route::put('{phase}', [ProjectPhaseController::class, 'update'])
            ->middleware('role:admin')
            ->name('api.v1.projects.phases.update');
        Route::delete('{phase}', [ProjectPhaseController::class, 'destroy'])
            ->middleware('role:admin')
            ->name('api.v1.projects.phases.destroy');
    });
});
```

Registration in `routes/api.php`:

```php
require __DIR__.'/api/v1/projects.php';
```

---

## Frontend Design

### Pages

| Route                        | Page Component               | Layout    | Auth Required | Roles        |
| ---------------------------- | ---------------------------- | --------- | ------------- | ------------ |
| /{locale}/projects           | pages/projects/index.vue     | dashboard | Yes           | All          |
| /{locale}/projects/create    | pages/projects/create.vue    | dashboard | Yes           | Admin        |
| /{locale}/projects/{id}      | pages/projects/[id].vue      | dashboard | Yes           | Role-scoped  |
| /{locale}/projects/{id}/edit | pages/projects/[id]/edit.vue | dashboard | Yes           | Admin, Owner |

### Components

| Component                      | Purpose                             | Location                                     |
| ------------------------------ | ----------------------------------- | -------------------------------------------- |
| ProjectCard.vue                | Project summary card for listing    | components/projects/ProjectCard.vue          |
| ProjectStatusBadge.vue         | Colored badge for project status    | components/projects/ProjectStatusBadge.vue   |
| ProjectFilters.vue             | Filter bar (status, type, city)     | components/projects/ProjectFilters.vue       |
| ProjectWizard.vue              | Multi-step creation wizard          | components/projects/ProjectWizard.vue        |
| ProjectWizardStepBasicInfo.vue | Step 1: name, description, type     | components/projects/wizard/StepBasicInfo.vue |
| ProjectWizardStepLocation.vue  | Step 2: city, district, coordinates | components/projects/wizard/StepLocation.vue  |
| ProjectWizardStepBudget.vue    | Step 3: budget, start/end dates     | components/projects/wizard/StepBudget.vue    |
| ProjectWizardStepReview.vue    | Step 4: review & submit             | components/projects/wizard/StepReview.vue    |
| ProjectDetailTabs.vue          | Tabbed detail view container        | components/projects/ProjectDetailTabs.vue    |
| ProjectOverviewTab.vue         | Overview tab content                | components/projects/tabs/OverviewTab.vue     |
| ProjectPhasesTab.vue           | Phases tab with CRUD                | components/projects/tabs/PhasesTab.vue       |
| ProjectTimelineTab.vue         | Timeline/Gantt visualization        | components/projects/tabs/TimelineTab.vue     |
| ProjectTabPlaceholder.vue      | Placeholder for unimplemented tabs  | components/projects/tabs/PlaceholderTab.vue  |
| PhaseForm.vue                  | Phase create/edit form              | components/projects/PhaseForm.vue            |
| PhaseListItem.vue              | Phase row in phases tab             | components/projects/PhaseListItem.vue        |

### State Management (Pinia)

| Store        | State                                                | Actions                                                                                  | Getters                       |
| ------------ | ---------------------------------------------------- | ---------------------------------------------------------------------------------------- | ----------------------------- |
| projectStore | projects, selectedProject, isLoading, error, filters | loadProjects, loadProject, createProject, updateProject, transitionStatus, deleteProject | filteredProjects, projectById |

### Composables

| Composable          | Purpose                                                                                               |
| ------------------- | ----------------------------------------------------------------------------------------------------- |
| useProjects.ts      | API calls: fetchProjects, fetchProject, createProject, updateProject, transitionStatus, deleteProject |
| useProjectPhases.ts | API calls: fetchPhases, createPhase, updatePhase, deletePhase, fetchTimeline                          |

### Types

| File             | Types                                                                                                                        |
| ---------------- | ---------------------------------------------------------------------------------------------------------------------------- |
| types/project.ts | Project, ProjectPhase, ProjectFormData, ProjectFilters, ProjectStatus, ProjectType, PhaseStatus, PhaseFormData, TimelineData |

---

## Middleware Chain

```
Request → CORS → CorrelationId → Auth (Sanctum) → RoleMiddleware → Rate Limit → Controller → Policy
```

---

## Service Provider Bindings

Add to `AppServiceProvider::register()`:

```php
// Repositories
$this->app->bind(ProjectRepository::class, fn () => new ProjectRepository(new Project));
$this->app->bind(ProjectPhaseRepository::class, fn () => new ProjectPhaseRepository(new ProjectPhase));

// Services
$this->app->bind(ProjectService::class, fn ($app) => new ProjectService(
    $app->make(ProjectRepository::class),
));
$this->app->bind(ProjectPhaseService::class, fn ($app) => new ProjectPhaseService(
    $app->make(ProjectPhaseRepository::class),
    $app->make(ProjectRepository::class),
));
```

Add to `AppServiceProvider::boot()`:

```php
// Route model binding
Route::model('project', Project::class);

// Policy registration
Gate::policy(Project::class, ProjectPolicy::class);
```

---

## Error Handling

| Scenario                          | Error Code                  | HTTP Status | Message (AR)                                           |
| --------------------------------- | --------------------------- | ----------- | ------------------------------------------------------ |
| Invalid status transition         | WORKFLOW_INVALID_TRANSITION | 422         | لا يمكن الانتقال من الحالة الحالية إلى الحالة المطلوبة |
| CLOSED project update attempt     | WORKFLOW_INVALID_TRANSITION | 422         | لا يمكن تعديل مشروع مغلق                               |
| Unauthorized project access       | RBAC_ROLE_DENIED            | 403         | ليس لديك صلاحية للوصول إلى هذا المشروع                 |
| Project not found                 | RESOURCE_NOT_FOUND          | 404         | المشروع غير موجود                                      |
| Validation failure                | VALIDATION_ERROR            | 422         | بيانات غير صالحة (with field details)                  |
| Concurrent modification conflict  | CONFLICT_ERROR              | 409         | تم تعديل المشروع بواسطة مستخدم آخر                     |
| Owner not a Customer              | VALIDATION_ERROR            | 422         | يجب أن يكون مالك المشروع من نوع عميل                   |
| Phase dates outside project range | VALIDATION_ERROR            | 422         | تواريخ المرحلة يجب أن تكون ضمن نطاق تواريخ المشروع     |

---

## Testing Strategy

| Layer      | Tool       | Coverage Target | Key Test Cases                                                         |
| ---------- | ---------- | --------------- | ---------------------------------------------------------------------- |
| Unit (PHP) | PHPUnit    | 80%             | ProjectStatus transitions, enum methods, model scopes, resource output |
| Feature    | PHPUnit    | Key flows       | CRUD endpoints, RBAC for all 5 roles, status transitions, phase CRUD   |
| Unit (JS)  | Vitest     | 80%             | Store actions, composable methods, component rendering                 |
| E2E        | Playwright | Critical paths  | Project creation wizard, status transition, phase management           |

### Backend Test Files

```
tests/Unit/Enums/ProjectStatusTest.php         — transition map validation
tests/Unit/Models/ProjectTest.php              — scopes, casts, relationships
tests/Feature/Api/V1/ProjectControllerTest.php — all CRUD + RBAC + status transitions
tests/Feature/Api/V1/ProjectPhaseControllerTest.php — phase CRUD + date validation
```

### Frontend Test Files

```
tests/unit/composables/useProjects.test.ts
tests/unit/stores/projectStore.test.ts
tests/unit/components/projects/ProjectCard.test.ts
tests/unit/components/projects/ProjectStatusBadge.test.ts
tests/e2e/projects.spec.ts
```

---

## Security Considerations

- [x] Input validation via Form Requests (5 Form Request classes)
- [x] RBAC middleware on all protected routes (`role:admin` on create/status/phase mutate)
- [x] Policy-based authorization for view/update (owner check)
- [x] SQL injection prevention (Eloquent parameterized queries)
- [x] XSS prevention (API Resources sanitize output, Nuxt auto-escaping)
- [x] CSRF protection (Sanctum token-based, not session)
- [x] Rate limiting on API endpoints (inherited from `api` rate limiter)
- [x] Owner ID validated against Customer role (prevents privilege escalation)
- [x] Soft-deleted records hidden from non-Admin queries
- [x] Optimistic locking prevents race conditions on status transitions

---

## i18n / RTL Considerations

- [x] All user-facing strings use translation keys (`lang/ar/projects.php`, `lang/en/projects.php`)
- [x] RTL layout verified for Arabic (Nuxt UI native RTL support)
- [x] Date formatting uses locale-aware helpers
- [x] Bilingual fields (name_ar, name_en) on all entities
- [x] Form validation messages in Arabic and English
- [x] Status/type enum labels provide Arabic translations

---

## Risk Assessment

| Risk                                                     | Likelihood | Impact | Mitigation                                                   |
| -------------------------------------------------------- | ---------- | ------ | ------------------------------------------------------------ |
| Team-scoped visibility incomplete without STAGE_15       | High       | Low    | Stub returns empty results; documented in spec assumptions   |
| Concurrent status transitions cause data corruption      | Low        | High   | Optimistic locking via updated_at comparison                 |
| Migration conflicts with parallel development            | Medium     | Medium | Forward-only migrations, timestamp-based naming              |
| Phase date validation edge cases with NULL project dates | Medium     | Low    | Explicit NULL-skip logic documented in research.md (R4)      |
| Owner deactivation leaves orphaned projects              | Low        | Low    | Projects remain accessible; owner becomes read-only per spec |

---

## File Inventory

### Backend Files to Create

| File                                                       | Type         |
| ---------------------------------------------------------- | ------------ |
| `database/migrations/YYYY_create_projects_table.php`       | Migration    |
| `database/migrations/YYYY_create_project_phases_table.php` | Migration    |
| `app/Enums/ProjectStatus.php`                              | Enum         |
| `app/Enums/ProjectType.php`                                | Enum         |
| `app/Enums/PhaseStatus.php`                                | Enum         |
| `app/Models/Project.php`                                   | Model        |
| `app/Models/ProjectPhase.php`                              | Model        |
| `app/Repositories/ProjectRepository.php`                   | Repository   |
| `app/Repositories/ProjectPhaseRepository.php`              | Repository   |
| `app/Services/ProjectService.php`                          | Service      |
| `app/Services/ProjectPhaseService.php`                     | Service      |
| `app/Http/Requests/StoreProjectRequest.php`                | Form Request |
| `app/Http/Requests/UpdateProjectRequest.php`               | Form Request |
| `app/Http/Requests/TransitionProjectStatusRequest.php`     | Form Request |
| `app/Http/Requests/StoreProjectPhaseRequest.php`           | Form Request |
| `app/Http/Requests/UpdateProjectPhaseRequest.php`          | Form Request |
| `app/Http/Resources/ProjectResource.php`                   | API Resource |
| `app/Http/Resources/ProjectPhaseResource.php`              | API Resource |
| `app/Policies/ProjectPolicy.php`                           | Policy       |
| `app/Http/Controllers/ProjectController.php`               | Controller   |
| `app/Http/Controllers/ProjectPhaseController.php`          | Controller   |
| `routes/api/v1/projects.php`                               | Route File   |
| `lang/ar/projects.php`                                     | Translation  |
| `lang/en/projects.php`                                     | Translation  |

### Backend Files to Modify

| File                                   | Change                                                      |
| -------------------------------------- | ----------------------------------------------------------- |
| `routes/api.php`                       | Add `require` for projects route file                       |
| `app/Providers/AppServiceProvider.php` | Register repository/service bindings, policy, model binding |

### Frontend Files to Create

| File                                            | Type        |
| ----------------------------------------------- | ----------- |
| `types/project.ts`                              | Types       |
| `composables/useProjects.ts`                    | Composable  |
| `composables/useProjectPhases.ts`               | Composable  |
| `stores/projectStore.ts`                        | Pinia Store |
| `pages/projects/index.vue`                      | Page        |
| `pages/projects/create.vue`                     | Page        |
| `pages/projects/[id].vue`                       | Page        |
| `pages/projects/[id]/edit.vue`                  | Page        |
| `components/projects/ProjectCard.vue`           | Component   |
| `components/projects/ProjectStatusBadge.vue`    | Component   |
| `components/projects/ProjectFilters.vue`        | Component   |
| `components/projects/ProjectWizard.vue`         | Component   |
| `components/projects/wizard/StepBasicInfo.vue`  | Component   |
| `components/projects/wizard/StepLocation.vue`   | Component   |
| `components/projects/wizard/StepBudget.vue`     | Component   |
| `components/projects/wizard/StepReview.vue`     | Component   |
| `components/projects/ProjectDetailTabs.vue`     | Component   |
| `components/projects/tabs/OverviewTab.vue`      | Component   |
| `components/projects/tabs/PhasesTab.vue`        | Component   |
| `components/projects/tabs/TimelineTab.vue`      | Component   |
| `components/projects/tabs/PlaceholderTab.vue`   | Component   |
| `components/projects/PhaseForm.vue`             | Component   |
| `components/projects/PhaseListItem.vue`         | Component   |
| `locales/ar/projects.json` (or extend existing) | i18n        |
| `locales/en/projects.json` (or extend existing) | i18n        |

### Test Files to Create

| File                                                  | Type         |
| ----------------------------------------------------- | ------------ |
| `tests/Unit/Enums/ProjectStatusTest.php`              | Unit Test    |
| `tests/Unit/Models/ProjectTest.php`                   | Unit Test    |
| `tests/Feature/Api/V1/ProjectControllerTest.php`      | Feature Test |
| `tests/Feature/Api/V1/ProjectPhaseControllerTest.php` | Feature Test |
| `tests/unit/composables/useProjects.test.ts`          | Vitest       |
| `tests/unit/stores/projectStore.test.ts`              | Vitest       |
| `tests/unit/components/projects/ProjectCard.test.ts`  | Vitest       |
| `tests/e2e/projects.spec.ts`                          | Playwright   |
