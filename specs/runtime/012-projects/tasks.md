# Tasks: Projects (المشاريع)

**Input**: Design documents from `specs/runtime/012-projects/`
**Prerequisites**: plan.md ✅, spec.md ✅, data-model.md ✅, research.md ✅

**Organization**: Tasks are grouped into foundational phases (blocking) followed by user-story phases. Backend files are created as complete units in the foundational phase since controllers/services are single files with all methods. Frontend pages/components are organized per user story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US-ALL)
- Exact file paths included in all descriptions

## Path Conventions

- **Backend**: `backend/` (Laravel)
- **Frontend**: `frontend/` (Nuxt.js / Vue 3)

---

## Phase 1: Setup — Database Foundation

**Purpose**: Create database tables required by all downstream code

- [ ] T001 [US-ALL] Create migration `backend/database/migrations/xxxx_xx_xx_xxxxxx_create_projects_table.php` — 17 columns (owner_id FK→users, name_ar, name_en, description, city, district, location_lat DECIMAL(10,7), location_lng DECIMAL(10,7), status ENUM, type ENUM, budget_estimated DECIMAL(15,2), budget_actual DECIMAL(15,2), start_date, end_date, timestamps, soft_delete), 5 indexes (owner_id, status, type, city, deleted_at), 1 FK (owner_id→users CASCADE)
- [ ] T002 [US-ALL] Create migration `backend/database/migrations/xxxx_xx_xx_xxxxxx_create_project_phases_table.php` — 11 columns (project_id FK→projects, name_ar, name_en, sort_order, status ENUM, start_date, end_date, completion_percentage TINYINT CHECK 0–100, timestamps), 2 indexes (project_id, project_id+sort_order composite), 1 FK (project_id→projects CASCADE). Depends on T001.

**Checkpoint**: Run `php artisan migrate` — both tables exist with correct schema

---

## Phase 2: Foundational — Backend Domain & Application Layer

**Purpose**: All backend infrastructure required before any user story can be tested end-to-end

**⚠️ CRITICAL**: No user story frontend work or integration testing can begin until this phase is complete

### Enums

- [ ] T003 [P] [US-ALL] Create `ProjectStatus` enum at `backend/app/Enums/ProjectStatus.php` — backed string enum with values: draft, planning, in_progress, on_hold, completed, closed; methods: `label()` (Arabic labels), `allowedTransitions()` (returns array of valid next states per R1), `canTransitionTo(self $target): bool`
- [ ] T004 [P] [US-ALL] Create `ProjectType` enum at `backend/app/Enums/ProjectType.php` — backed string enum with values: residential, commercial, infrastructure; method: `label()` (Arabic labels: سكني, تجاري, بنية تحتية)
- [ ] T005 [P] [US6] Create `PhaseStatus` enum at `backend/app/Enums/PhaseStatus.php` — backed string enum with values: pending, in_progress, completed; method: `label()` (Arabic labels)

### Models

- [ ] T006 [P] [US-ALL] Create `Project` model at `backend/app/Models/Project.php` — extends BaseModel (SoftDeletes, HasFactory); casts: status→ProjectStatus, type→ProjectType, budget_estimated→decimal:2, budget_actual→decimal:2, start_date→date, end_date→date; relationships: `owner()` BelongsTo User, `phases()` HasMany ProjectPhase ordered by sort_order; scopes: `scopeForUser(Builder, User)` (Admin→all, Customer→owner_id, others→empty stub per R2), `scopeStatus()`, `scopeType()`, `scopeCity()`; helper: `isEditable(): bool` (false when CLOSED)
- [ ] T007 [P] [US6] Create `ProjectPhase` model at `backend/app/Models/ProjectPhase.php` — extends Model directly (NOT BaseModel — no SoftDeletes, hard-delete per spec); uses HasFactory; $guarded=[]; casts: status→PhaseStatus, sort_order→integer, completion_percentage→integer, start_date→date, end_date→date; relationship: `project()` BelongsTo Project

### Factories

- [ ] T006b [P] [US-ALL] Create `ProjectFactory` at `backend/database/factories/ProjectFactory.php` — default: owner_id→User::factory()->customer(), name_ar, name_en, city, district, type→residential, status→draft, budget_estimated; states: `planning()`, `inProgress()`, `onHold()`, `completed()`, `closed()` for each status; `commercial()`, `infrastructure()` for types. Depends on T006.
- [ ] T007b [P] [US6] Create `ProjectPhaseFactory` at `backend/database/factories/ProjectPhaseFactory.php` — default: project_id→Project::factory(), name_ar, name_en, sort_order, status→pending, start_date, end_date within project range, completion_percentage→0; states: `inProgress()`, `completed()` for status. Depends on T007.

### Repositories

- [ ] T008 [P] [US-ALL] Create `ProjectRepository` at `backend/app/Repositories/ProjectRepository.php` — extends BaseRepository; methods: `paginateForUser(User $user, array $filters, int $perPage = 15)` (applies scopeForUser + filter scopes + withTrashed for Admin when with_trashed param), `findWithPhases(int $id)` (eager loads phases + owner). Depends on T006.
- [ ] T009 [P] [US6] Create `ProjectPhaseRepository` at `backend/app/Repositories/ProjectPhaseRepository.php` — extends BaseRepository; methods: `findForProject(int $projectId, int $phaseId)`, `listForProject(int $projectId)` (ordered by sort_order). Depends on T007.

### Form Requests

- [ ] T010 [P] [US1] Create `StoreProjectRequest` at `backend/app/Http/Requests/StoreProjectRequest.php` — validates: owner_id (required, exists:users, Customer role check via after()), name_ar (required, string, min:2, max:255), name_en (required, string, min:2, max:255), city (required, string, max:100), type (required, Rule::in ProjectType), description (nullable, string), district (nullable, string, max:100), location_lat (nullable, numeric, between:-90,90), location_lng (nullable, numeric, between:-180,180), budget_estimated (nullable, numeric, min:0.01 per R8), start_date (nullable, date), end_date (nullable, date, after_or_equal:start_date)
- [ ] T011 [P] [US5] Create `UpdateProjectRequest` at `backend/app/Http/Requests/UpdateProjectRequest.php` — same fields as StoreProjectRequest but all optional (sometimes rules); after() rejects if project status is CLOSED with WORKFLOW_INVALID_TRANSITION
- [ ] T012 [P] [US4] Create `TransitionProjectStatusRequest` at `backend/app/Http/Requests/TransitionProjectStatusRequest.php` — validates: status (required, Rule::in ProjectStatus), expected_updated_at (required, date) for optimistic locking per R5
- [ ] T013 [P] [US6] Create `StoreProjectPhaseRequest` at `backend/app/Http/Requests/StoreProjectPhaseRequest.php` — validates: name_ar (required), name_en (required), sort_order (required, integer, min:0); optional: status (PhaseStatus), start_date (date), end_date (date, after_or_equal:start_date), completion_percentage (integer, between:0,100); after() validates date containment within project range per R4
- [ ] T014 [P] [US6] Create `UpdateProjectPhaseRequest` at `backend/app/Http/Requests/UpdateProjectPhaseRequest.php` — all fields optional (sometimes rules); after() validates date containment per R4

### API Resources

- [ ] T015 [P] [US-ALL] Create `ProjectResource` at `backend/app/Http/Resources/ProjectResource.php` — extends BaseApiResource; outputs: id, owner_id, owner (whenLoaded UserSummaryResource), name_ar, name_en, description, city, district, location_lat, location_lng, status (->value), type (->value), budget_estimated, budget_actual, start_date, end_date, phases_count (whenCounted), phases (whenLoaded collection), created_at, updated_at
- [ ] T016 [P] [US6] Create `ProjectPhaseResource` at `backend/app/Http/Resources/ProjectPhaseResource.php` — extends BaseApiResource; outputs: id, project_id, name_ar, name_en, sort_order, status (->value), start_date, end_date, completion_percentage, created_at, updated_at

### Policy

- [ ] T017 [P] [US-ALL] Create `ProjectPolicy` at `backend/app/Policies/ProjectPolicy.php` — methods: viewAny (all authenticated), view (Customer: own project; Contractor/Architect/Engineer: stub false until STAGE_15), create (Admin only via Gate::before), update (Customer: own + not CLOSED), transitionStatus (Admin only), delete (Admin only), addPhase (Admin only). Admin bypasses via Gate::before() in AppServiceProvider.

### Services

- [ ] T018 [US-ALL] Create `ProjectService` at `backend/app/Services/ProjectService.php` — constructor injects ProjectRepository; methods: `list(User, filters)` (delegates to paginateForUser), `find(int)` (findWithPhases or throw RESOURCE_NOT_FOUND), `create(array)` (create project with default DRAFT status), `update(int, array)` (reject CLOSED per FR-005), `transitionStatus(int, ProjectStatus, string)` (optimistic locking via updated_at check per R5, validate canTransitionTo, throw WORKFLOW_INVALID_TRANSITION or CONFLICT_ERROR), `timeline(int)` (return project + ordered phases), `delete(int)` (soft-delete). Depends on T008.
- [ ] T019 [US6] Create `ProjectPhaseService` at `backend/app/Services/ProjectPhaseService.php` — constructor injects ProjectPhaseRepository + ProjectRepository; methods: `listForProject(int)`, `create(int, array)` (validates project exists, creates phase), `update(int, int, array)`, `delete(int, int)` (hard-delete). Depends on T008, T009.

### Controllers

- [ ] T020 [US-ALL] Create `ProjectController` at `backend/app/Http/Controllers/Api/ProjectController.php` — uses ApiResponseTrait; constructor injects ProjectService; methods: index (authorize viewAny→service->list→paginated ProjectResource), store (StoreProjectRequest→service->create→ProjectResource 201), show (authorize view→service->find→ProjectResource), update (authorize update→UpdateProjectRequest→service->update→ProjectResource), destroy (authorize delete→service->delete→success response 200), transitionStatus (authorize transitionStatus→TransitionProjectStatusRequest→service->transitionStatus→ProjectResource), timeline (authorize view→service->timeline→custom response per R12). Depends on T018, T010–T012, T015, T017.
- [ ] T021 [US6] Create `ProjectPhaseController` at `backend/app/Http/Controllers/Api/ProjectPhaseController.php` — uses ApiResponseTrait; constructor injects ProjectPhaseService; methods: index (authorize view project→service->listForProject→ProjectPhaseResource collection), store (authorize addPhase→StoreProjectPhaseRequest→service->create→ProjectPhaseResource 201), update (authorize addPhase→UpdateProjectPhaseRequest→service->update→ProjectPhaseResource), destroy (authorize addPhase→service->delete→success response). Depends on T019, T013–T014, T016, T017.

### Routes & Registration

- [ ] T022 [US-ALL] Create route file at `backend/routes/api/v1/projects.php` — defines all 11 endpoints: GET/POST /projects, GET/PUT/DELETE /projects/{project}, PUT /projects/{project}/status, GET /projects/{project}/timeline, GET/POST /projects/{project}/phases, PUT/DELETE /projects/{project}/phases/{phase}; middleware: auth:sanctum on all, role:admin on store/transitionStatus/delete/phase mutations. Depends on T020, T021.
- [ ] T023 [US-ALL] Register project routes in `backend/routes/api.php` — add `require __DIR__.'/api/v1/projects.php';` statement. Depends on T022.

### Translations

- [ ] T024 [P] [US-ALL] Create `backend/lang/ar/projects.php` — Arabic translations for project/phase labels, status names (مسودة, تخطيط, قيد التنفيذ, متوقف, مكتمل, مغلق), type names (سكني, تجاري, بنية تحتية), phase status names, validation messages, error messages
- [ ] T025 [P] [US-ALL] Create `backend/lang/en/projects.php` — English translations matching Arabic keys

### Service Provider

- [ ] T026 [US-ALL] Update `backend/app/Providers/AppServiceProvider.php` — register() bindings: ProjectRepository, ProjectPhaseRepository, ProjectService, ProjectPhaseService; boot(): Gate::policy(Project::class, ProjectPolicy::class), Route::model('project', Project::class). Depends on T006–T009, T017–T019.

**Checkpoint**: All backend endpoints respond correctly via `php artisan route:list | grep projects` — 10 routes registered. Run `php artisan test --filter=Project` for smoke test.

---

## Phase 3: US1 + US2 + US3 — Core Project CRUD (Priority: P1) 🎯 MVP

**Goal**: Users can create projects, view project listings (role-scoped), and view project details with tabbed navigation

**Independent Test**: Admin creates a project → project appears in listing → project detail shows all data in tabbed view

### Frontend Foundation (shared across all stories)

- [ ] T027 [P] [US-ALL] Create `frontend/types/project.ts` — TypeScript interfaces: Project, ProjectPhase, ProjectFormData, ProjectFilters, ProjectStatus (enum), ProjectType (enum), PhaseStatus (enum), PhaseFormData, TimelineData, ProjectListResponse (paginated)
- [ ] T028 [P] [US-ALL] Create `frontend/composables/useProjects.ts` — API client composable with methods: fetchProjects(filters, page), fetchProject(id), createProject(data), updateProject(id, data), transitionStatus(id, status, expectedUpdatedAt), deleteProject(id); uses Laravel API client composable. Depends on T027.
- [ ] T029 [US-ALL] Create `frontend/stores/projectStore.ts` — Pinia store; state: projects[], selectedProject, isLoading, error, filters; actions: loadProjects(), loadProject(id), createProject(data), updateProject(id, data), transitionStatus(id, status), deleteProject(id); getters: filteredProjects, projectById(id). Depends on T028.
- [ ] T030 [P] [US-ALL] Create `frontend/components/projects/ProjectStatusBadge.vue` — Nuxt UI UBadge with color mapping per status (draft→gray, planning→blue, in_progress→yellow, on_hold→orange, completed→green, closed→red); accepts status prop; Arabic label display

### US1 — Admin Creates a New Construction Project

- [ ] T031 [US1] Create `frontend/pages/projects/create.vue` — project creation page; simple form layout using Nuxt UI UForm with fields: owner_id, name_ar, name_en, description, city, district, location_lat, location_lng, type (USelect), budget_estimated, start_date, end_date; VeeValidate + Zod validation; on submit calls createProject composable; redirects to project detail on success; Admin-only middleware. Depends on T028, T029.

### US2 — Role-Scoped Project Listing

- [ ] T032 [P] [US2] Create `frontend/components/projects/ProjectCard.vue` — project summary card using Nuxt UI UCard; displays: name (locale-aware ar/en), ProjectStatusBadge, type badge, city, budget_estimated formatted, start_date–end_date range; click navigates to project detail. Depends on T030.
- [ ] T033 [P] [US2] Create `frontend/components/projects/ProjectFilters.vue` — filter bar with Nuxt UI USelect for status (all + ProjectStatus values), type (all + ProjectType values), city (text input); emits filter-change event with ProjectFilters object
- [ ] T034 [US2] Create `frontend/pages/projects/index.vue` — project listing page; uses projectStore.loadProjects(); renders ProjectFilters + grid of ProjectCards + Nuxt UI pagination; responsive RTL layout; Admin sees "Create Project" button. Depends on T029, T032, T033.

### US3 — Project Detail View

- [ ] T035 [P] [US3] Create `frontend/components/projects/tabs/PlaceholderTab.vue` — placeholder component for unimplemented tabs (tasks, team, documents); displays i18n message "قريبًا في تحديث مستقبلي" / "Coming soon in a future update" with icon
- [ ] T036 [P] [US3] Create `frontend/components/projects/tabs/OverviewTab.vue` — project overview displaying: name, description, status with badge, type, city/district, location coordinates, budget (estimated vs actual), date range, owner info; uses Nuxt UI UCard sections
- [ ] T036b [P] [US4] Create `frontend/components/projects/StatusTransitionControl.vue` — Admin-only status transition dropdown; shows current status badge + allowed transitions as UDropdown items; on select opens UModal confirmation with optimistic locking (expected_updated_at); calls projectStore.transitionStatus(); displays success/error feedback; hidden for non-Admin users. Depends on T029, T030.
- [ ] T037 [US3] Create `frontend/components/projects/ProjectDetailTabs.vue` — tabbed container using Nuxt UI UTabs with 6 tabs: Overview (OverviewTab), Phases (PhasesTab→placeholder initially, replaced in US6), Tasks (PlaceholderTab), Team (PlaceholderTab), Documents (PlaceholderTab), Timeline (TimelineTab→placeholder initially, replaced in US7); accepts project prop. Depends on T035, T036.
- [ ] T038 [US3] Create `frontend/pages/projects/[id].vue` — project detail page; loads project via projectStore.loadProject(id); renders ProjectDetailTabs; shows loading/error states; role-scoped access (redirects if unauthorized). Depends on T029, T037.

**Checkpoint**: Admin can create a project → listing shows all projects → clicking a project shows tabbed detail view with overview data. Customer sees only owned projects. Non-assigned roles see empty listing.

---

## Phase 4: US4 + US5 — Status Transitions & Project Update (Priority: P2)

**Goal**: Admins can transition project status through the lifecycle; Admins and project owners can update project details

**Independent Test**: Admin transitions project DRAFT→PLANNING→IN_PROGRESS; invalid transitions are rejected. Admin/owner updates project name and budget.

### US5 — Project Update

- [ ] T039 [US5] Create `frontend/pages/projects/[id]/edit.vue` — project edit form page; loads current project data into form; uses Nuxt UI UForm with same fields as create; disables form for CLOSED projects with warning message; submits via updateProject composable; Admin or owner-only middleware. Depends on T028, T029.

**Checkpoint**: Admin can update project fields. CLOSED projects show immutable warning. Non-authorized users are redirected.

---

## Phase 5: US6 — Project Phases Management (Priority: P2)

**Goal**: Admins can add, update, reorder, and delete phases within a project

**Independent Test**: Admin adds 3 phases to a project → phases appear ordered by sort_order → update a phase → delete a phase

- [ ] T040 [P] [US6] Create `frontend/composables/useProjectPhases.ts` — API client composable with methods: fetchPhases(projectId), createPhase(projectId, data), updatePhase(projectId, phaseId, data), deletePhase(projectId, phaseId), fetchTimeline(projectId); uses Laravel API client composable. Depends on T027.
- [ ] T041 [P] [US6] Create `frontend/components/projects/PhaseForm.vue` — phase create/edit form using Nuxt UI UForm; fields: name_ar, name_en, sort_order (UInput number), status (USelect PhaseStatus), start_date, end_date (date inputs), completion_percentage (URange or UInput 0–100); VeeValidate + Zod validation; emits submit event
- [ ] T042 [P] [US6] Create `frontend/components/projects/PhaseListItem.vue` — phase row component; displays: name (locale-aware), status badge, completion percentage bar (UProgress), sort_order, date range; action buttons: edit, delete (with confirmation modal); emits edit/delete events
- [ ] T043 [US6] Create `frontend/components/projects/tabs/PhasesTab.vue` — phases management tab; lists phases via PhaseListItem; "Add Phase" button opens PhaseForm in UModal; edit action opens PhaseForm pre-filled; delete action shows UModal confirmation; uses useProjectPhases composable; Admin-only mutations (other roles see read-only list). Depends on T040, T041, T042.

**Checkpoint**: Admin adds phases to a project → phases render in sorted order → edit/delete work correctly → date containment validation triggers on invalid dates.

---

## Phase 6: US7 + US8 — Timeline View & Creation Wizard (Priority: P3)

**Goal**: Users view project timeline visualization; Admins use multi-step wizard for project creation

**Independent Test**: Timeline tab shows phase durations visually. Wizard completes all 4 steps and creates a project.

### US7 — Project Timeline View

- [ ] T044 [US7] Create `frontend/components/projects/tabs/TimelineTab.vue` — timeline/Gantt-style visualization; calls fetchTimeline(projectId) via useProjectPhases composable; renders phases as horizontal bars with start/end dates, completion percentage overlay, status color coding; handles empty phases gracefully; read-only view. Depends on T040.

### US8 — Project Creation Wizard (Frontend)

- [ ] T045 [P] [US8] Create `frontend/components/projects/wizard/StepBasicInfo.vue` — Wizard Step 1: owner_id (USelect for Customer users), name_ar, name_en, description (UTextarea), type (USelect ProjectType); VeeValidate validation; emits step-complete with partial form data
- [ ] T046 [P] [US8] Create `frontend/components/projects/wizard/StepLocation.vue` — Wizard Step 2: city (UInput), district (UInput), location_lat (UInput number), location_lng (UInput number); coordinate validation (lat -90 to 90, lng -180 to 180); emits step-complete
- [ ] T047 [P] [US8] Create `frontend/components/projects/wizard/StepBudget.vue` — Wizard Step 3: budget_estimated (UInput currency formatted), start_date (date input), end_date (date input, after_or_equal:start_date); emits step-complete
- [ ] T048 [P] [US8] Create `frontend/components/projects/wizard/StepReview.vue` — Wizard Step 4: read-only summary of all entered data across steps; "Submit" button triggers project creation; "Back" button returns to previous steps; displays validation errors from API inline
- [ ] T049 [US8] Create `frontend/components/projects/ProjectWizard.vue` — multi-step wizard container; manages step navigation (1→2→3→4), preserves data across steps in reactive state, validates per step before advancing, handles back navigation without data loss; on final submit calls createProject composable; redirects to project detail on success. Depends on T045–T048.
- [ ] T050 [US8] Update `frontend/pages/projects/create.vue` — replace simple form with ProjectWizard component; maintain Admin-only middleware. Depends on T049. **Note**: This modifies T031's output.

**Checkpoint**: Wizard navigates all 4 steps → back navigation preserves data → submit creates project → timeline tab renders phase bars correctly for a project with phases.

---

## Phase 7: Testing

**Purpose**: Comprehensive test coverage for all user stories

### Backend Unit Tests

- [ ] T051 [P] [US4] Create `backend/tests/Unit/Enums/ProjectStatusTest.php` — test: all 6 values exist, label() returns Arabic strings, allowedTransitions() returns correct arrays per transition map (DRAFT→[PLANNING], PLANNING→[IN_PROGRESS], IN_PROGRESS→[ON_HOLD,COMPLETED], ON_HOLD→[IN_PROGRESS], COMPLETED→[CLOSED], CLOSED→[]), canTransitionTo() returns true/false correctly, forbidden transitions rejected (e.g., DRAFT→COMPLETED)
- [ ] T052 [P] [US-ALL] Create `backend/tests/Unit/Models/ProjectTest.php` — test: scopeForUser returns all for Admin, owner-only for Customer, empty for Contractor/Architect/Engineer (stub); scopeStatus/scopeType/scopeCity filter correctly; isEditable() returns false for CLOSED, true otherwise; casts work correctly; relationships (owner, phases) resolve; soft-delete behavior

### Backend Feature Tests

- [ ] T053 [US-ALL] Create `backend/tests/Feature/Api/V1/ProjectControllerTest.php` — RBAC matrix: test ALL 5 roles (Admin, Customer, Contractor, Supervising Architect, Field Engineer) on ALL endpoints. POST create (Admin→201, Customer→403, Contractor→403, Architect→403, Engineer→403, unauthenticated→401; validation errors→422, non-Customer owner→422); GET index (Admin sees all, Customer sees owned only, Contractor/Architect/Engineer see empty; filter by status/type/city; pagination); GET show (Admin→200, Customer-owner→200, Customer-non-owner→403, Contractor→403, Architect→403, Engineer→403, not found→404, soft-deleted→404 for non-Admin); PUT update (Admin→200, Customer-owner→200, Customer-non-owner→403, Contractor→403, CLOSED project→422); DELETE destroy (Admin→200 soft-delete, Customer→403, non-Admin→403); PUT status (Admin valid→200, Admin invalid transition→422, Admin optimistic lock conflict→409, Customer→403, Contractor→403, Architect→403, Engineer→403); GET timeline (Admin→200, Customer-owner→200, empty phases→200). All responses match error contract. Depends on T006b.
- [ ] T054 [US6] Create `backend/tests/Feature/Api/V1/ProjectPhaseControllerTest.php` — RBAC matrix: test ALL 5 roles on ALL phase endpoints. POST create (Admin→201, Customer→403, Contractor→403; date outside project range→422; NULL project dates→201 containment skipped); GET index (Admin→200 ordered by sort_order, Customer-owner→200, non-assigned→403); PUT update (Admin→200, date containment→422, NULL project dates→201); DELETE destroy (Admin→200 hard-delete, Customer→403). Depends on T007b.

### Frontend Unit Tests

- [ ] T055 [P] [US-ALL] Create `frontend/tests/unit/composables/useProjects.test.ts` — test: fetchProjects calls correct API endpoint with filters, fetchProject calls /projects/{id}, createProject sends POST, updateProject sends PUT, transitionStatus sends PUT /projects/{id}/status, deleteProject sends DELETE; error handling for API failures
- [ ] T056 [P] [US-ALL] Create `frontend/tests/unit/stores/projectStore.test.ts` — test: loadProjects populates state, loadProject sets selectedProject, createProject adds to list, updateProject updates in list, transitionStatus updates status, deleteProject removes from list; isLoading/error state management; getters filteredProjects and projectById
- [ ] T057 [P] [US2] Create `frontend/tests/unit/components/projects/ProjectCard.test.ts` — test: renders project name (Arabic/English based on locale), displays correct status badge color, shows budget formatted, shows date range, click emits navigation event
- [ ] T057b [P] [US4] Create `frontend/tests/unit/components/projects/StatusTransitionControl.test.ts` — test: renders allowed transitions dropdown for Admin, hidden for non-Admin, confirm modal appears on selection, calls transitionStatus on confirm
- [ ] T057c [P] [US-ALL] Create `frontend/tests/unit/components/projects/ProjectStatusBadge.test.ts` — test: renders correct color per status, displays Arabic label, handles all 6 status values
- [ ] T057d [P] [US6] Create `frontend/tests/unit/composables/useProjectPhases.test.ts` — test: fetchPhases calls correct endpoint, createPhase sends POST, updatePhase sends PUT, deletePhase sends DELETE, error handling

### Frontend E2E Test

- [ ] T058 [US-ALL] Create `frontend/tests/e2e/projects.spec.ts` — Playwright E2E tests: Admin creates project via wizard (all 4 steps), project appears in listing, project detail shows correct data in tabs, Admin transitions status DRAFT→PLANNING→IN_PROGRESS, Customer sees only owned projects, edit page updates project fields, phase CRUD within detail page

**Checkpoint**: All backend tests pass via `php artisan test --filter=Project`. All frontend tests pass via `npm run test`. E2E tests pass via `npx playwright test projects`.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: i18n, documentation, validation pipeline

### Frontend i18n

- [ ] T059 [P] [US-ALL] Create `frontend/locales/ar/projects.json` — Arabic translations: page titles (المشاريع, مشروع جديد, تفاصيل المشروع, تعديل المشروع), form labels (اسم المشروع بالعربية, اسم المشروع بالإنجليزية, الوصف, المدينة, الحي, خط العرض, خط الطول, النوع, الميزانية المقدرة, تاريخ البدء, تاريخ الانتهاء), status labels, type labels, phase labels, tab names, button labels, error/success messages, wizard step titles, placeholder text
- [ ] T060 [P] [US-ALL] Create `frontend/locales/en/projects.json` — English translations matching all Arabic keys

### Final Validation

- [ ] T061 [US-ALL] Run full validation pipeline: `composer run lint && composer run test && npm run lint && npm run typecheck && npm run test` — all checks must pass with zero errors

---

## Dependencies & Execution Order

### Phase Dependencies

```
Phase 1 (Database) ← no dependencies, start immediately
  │
  ▼
Phase 2 (Backend Core) ← depends on Phase 1 completion
  │
  ▼
Phase 3 (P1 Stories) ← depends on Phase 2 completion
  │
  ├──▶ Phase 4 (P2: Update/Status) ← depends on Phase 3 (listing + detail pages exist)
  │
  ├──▶ Phase 5 (P2: Phases) ← depends on Phase 3 (detail page tabs exist)
  │
  └──▶ Phase 6 (P3: Timeline + Wizard) ← depends on Phase 3 + Phase 5 (phases exist for timeline)
         │
         ▼
       Phase 7 (Testing) ← depends on all story phases
         │
         ▼
       Phase 8 (Polish) ← depends on all phases
```

### Within Phase 2 (Backend) — Internal Dependencies

```
Enums (T003–T005) ← no internal deps, all [P]
  │
  ▼
Models (T006–T007) ← depend on Enums for casts
  │
  ▼
Repositories (T008–T009) ← depend on Models
  ├──▶ Form Requests (T010–T014) ← depend on Enums only, [P] with Repos
  ├──▶ API Resources (T015–T016) ← depend on Models only, [P] with Repos
  └──▶ Policy (T017) ← depends on Models only, [P] with Repos
         │
         ▼
Services (T018–T019) ← depend on Repositories
  │
  ▼
Controllers (T020–T021) ← depend on Services, Form Requests, Resources, Policy
  │
  ▼
Routes (T022–T023) ← depend on Controllers
  │
  ▼
Provider (T026) ← depend on Models, Repos, Services, Policy
```

### User Story Dependencies (Cross-Story)

- **US1 (Create)**: Standalone after Phase 2 — no dependencies on other stories
- **US2 (Listing)**: Standalone after Phase 2 — needs ProjectCard, ProjectFilters
- **US3 (Detail)**: Standalone after Phase 2 — needs tab components
- **US4 (Status)**: Backend complete in Phase 2; frontend is part of detail view (US3)
- **US5 (Update)**: Needs US3 detail page to exist for navigation context
- **US6 (Phases)**: Needs US3 detail page tabs container to mount PhasesTab
- **US7 (Timeline)**: Needs US6 phases to exist for meaningful timeline data
- **US8 (Wizard)**: Replaces US1 simple form — can be done independently after Phase 3

### Parallel Opportunities

```
Phase 1:  T001 → T002 (sequential: T002 FK depends on T001)
Phase 2:  T003 ∥ T004 ∥ T005 (all enums parallel)
          T006 ∥ T007 (models parallel, after enums)
          T008 ∥ T009 ∥ T010 ∥ T011 ∥ T012 ∥ T013 ∥ T014 ∥ T015 ∥ T016 ∥ T017 (repos, requests, resources, policy — all parallel after models)
          T018 ∥ T019 (services parallel, after repos)
          T020 ∥ T021 (controllers parallel, after services+requests+resources+policy)
          T024 ∥ T025 (translations parallel, anytime)
Phase 3:  T027 ∥ T030 (types + status badge parallel)
          T028 after T027; T029 after T028
          T032 ∥ T033 ∥ T035 ∥ T036 (cards, filters, tabs — all parallel)
Phase 5:  T040 ∥ T041 ∥ T042 (composable, form, list item — all parallel)
Phase 6:  T045 ∥ T046 ∥ T047 ∥ T048 (all wizard steps parallel)
Phase 7:  T051 ∥ T052 ∥ T055 ∥ T056 ∥ T057 (unit tests all parallel)
Phase 8:  T059 ∥ T060 (i18n files parallel)
```

---

## Parallel Example: Phase 2 Backend (3 waves)

```bash
# Wave 1 — Enums (no dependencies):
T003 (ProjectStatus) ∥ T004 (ProjectType) ∥ T005 (PhaseStatus) ∥ T024 (lang/ar) ∥ T025 (lang/en)

# Wave 2 — Models + Repos + Requests + Resources + Policy:
T006 (Project) ∥ T007 (ProjectPhase)  →  T008 ∥ T009 ∥ T010 ∥ T011 ∥ T012 ∥ T013 ∥ T014 ∥ T015 ∥ T016 ∥ T017

# Wave 3 — Services → Controllers → Routes → Provider:
T018 ∥ T019  →  T020 ∥ T021  →  T022 → T023 → T026
```

## Parallel Example: Phase 3 Frontend P1 (2 waves)

```bash
# Wave 1 — Foundation + parallel components:
T027 (types) ∥ T030 (StatusBadge) ∥ T032 (Card) ∥ T033 (Filters) ∥ T035 (PlaceholderTab) ∥ T036 (OverviewTab)

# Wave 2 — Composable → Store → Pages:
T028 (useProjects) → T029 (store) → T031 (create page) ∥ T034 (index page) ∥ T037 (DetailTabs) → T038 (detail page)
```

---

## Implementation Strategy

### Recommended Execution Order (Single Developer)

1. **Phase 1**: T001 → T002 (run migrations, verify schema)
2. **Phase 2 Wave 1**: T003, T004, T005, T024, T025 (enums + translations)
3. **Phase 2 Wave 2**: T006, T007 → T008, T009, T010–T017 (models → everything parallel)
4. **Phase 2 Wave 3**: T018, T019 → T020, T021 → T022 → T023 → T026 (services → controllers → routes → provider)
5. **Phase 3**: T027 → T028 → T029, then T030–T038 (frontend foundation → P1 pages)
6. **Phase 4**: T039 (edit page)
7. **Phase 5**: T040–T043 (phases management)
8. **Phase 6**: T044–T050 (timeline + wizard)
9. **Phase 7**: T051–T058 (all tests)
10. **Phase 8**: T059–T061 (i18n + validation)

### Total Task Count

| Phase | Tasks  | Description                        |
| ----- | ------ | ---------------------------------- |
| 1     | 2      | Database migrations                |
| 2     | 24     | Backend domain & application layer |
| 3     | 12     | Frontend foundation + P1 stories   |
| 4     | 1      | P2: Project update page            |
| 5     | 4      | P2: Phases management frontend     |
| 6     | 7      | P3: Timeline + wizard              |
| 7     | 8      | Testing (unit + feature + E2E)     |
| 8     | 3      | i18n + validation pipeline         |
| **∑** | **61** | **Total tasks**                    |

### File Count Verification

| Category              | Count  | Files                                                                                            |
| --------------------- | ------ | ------------------------------------------------------------------------------------------------ |
| Backend migrations    | 2      | create_projects_table, create_project_phases_table                                               |
| Backend enums         | 3      | ProjectStatus, ProjectType, PhaseStatus                                                          |
| Backend models        | 2      | Project, ProjectPhase                                                                            |
| Backend repositories  | 2      | ProjectRepository, ProjectPhaseRepository                                                        |
| Backend services      | 2      | ProjectService, ProjectPhaseService                                                              |
| Backend form requests | 5      | Store/Update Project, TransitionStatus, Store/Update Phase                                       |
| Backend resources     | 2      | ProjectResource, ProjectPhaseResource                                                            |
| Backend policy        | 1      | ProjectPolicy                                                                                    |
| Backend controllers   | 2      | ProjectController, ProjectPhaseController                                                        |
| Backend routes        | 1      | routes/api/v1/projects.php                                                                       |
| Backend translations  | 2      | lang/ar/projects.php, lang/en/projects.php                                                       |
| Backend modified      | 2      | routes/api.php, AppServiceProvider                                                               |
| Frontend types        | 1      | types/project.ts                                                                                 |
| Frontend composables  | 2      | useProjects.ts, useProjectPhases.ts                                                              |
| Frontend store        | 1      | stores/projectStore.ts                                                                           |
| Frontend pages        | 4      | index, create, [id], [id]/edit                                                                   |
| Frontend components   | 15     | ProjectCard, StatusBadge, Filters, Wizard, 4 steps, DetailTabs, 4 tabs, PhaseForm, PhaseListItem |
| Frontend i18n         | 2      | locales/ar/projects.json, locales/en/projects.json                                               |
| Backend tests         | 4      | ProjectStatusTest, ProjectTest, ProjectControllerTest, ProjectPhaseControllerTest                |
| Frontend tests        | 4      | useProjects.test, projectStore.test, ProjectCard.test, projects.spec (E2E)                       |
| **Total files**       | **59** | 26 backend (24 new + 2 modified) + 25 frontend + 8 tests                                         |
