# Research — Projects (المشاريع)

> **Phase:** 03_PROJECT_MANAGEMENT > **Stage:** STAGE_12_PROJECTS
> **Created:** 2026-04-19

---

## R1: Status Machine Implementation Strategy

**Question:** How should the project status machine be implemented — dedicated state machine class, enum with transitions map, or service-level validation?

**Resolution:** Use a `ProjectStatus` enum with a static `allowedTransitions()` map and a `canTransitionTo(ProjectStatus $to)` method on the enum itself. The `ProjectService::transitionStatus()` method calls `$currentStatus->canTransitionTo($newStatus)` and throws `ApiException` with `WORKFLOW_INVALID_TRANSITION` on failure. This keeps the transition logic co-located with the enum and avoids over-engineering a full state machine library for a simple linear flow.

**Transition Map:**

```
DRAFT → [PLANNING]
PLANNING → [IN_PROGRESS]
IN_PROGRESS → [ON_HOLD, COMPLETED]
ON_HOLD → [IN_PROGRESS]
COMPLETED → [CLOSED]
CLOSED → [] (terminal state)
```

---

## R2: Role-Scoped Project Visibility

**Question:** How should role-based project listing be implemented given that `team_members` pivot table (STAGE_15) doesn't exist yet?

**Resolution:** Per spec assumptions, until STAGE_15 is implemented:

- **Admin:** See all projects (no scope applied)
- **Customer:** See projects where `owner_id = auth()->id()`
- **Contractor / Supervising Architect / Field Engineer:** Return empty results (stub). These roles require the `project_team_members` pivot table from STAGE_15.

Implementation: Add a `scopeForUser(Builder $query, User $user)` scope on the `Project` model that applies the appropriate filter. The scope checks `$user->role` enum and applies the correct WHERE clause. When STAGE_15 adds the pivot table, update the scope to include team member lookups.

---

## R3: Owner Validation on Project Creation

**Question:** Should `owner_id` allow any user or only Customers?

**Resolution:** Per FR-013, `owner_id` is **required** and MUST reference a user with `role = 'customer'`. Validation in `StoreProjectRequest`:

```php
'owner_id' => ['required', 'integer', 'exists:users,id,deleted_at,NULL'],
```

Plus a custom validation rule or `after()` hook to verify the referenced user has `role = 'customer'`. This prevents Admins from accidentally assigning non-Customer users as project owners.

---

## R4: Phase Date Containment Validation

**Question:** How to validate that phase dates are within the project's date range when project dates may be NULL?

**Resolution:** Per spec acceptance scenario 6.3:

- If the project's `start_date` is NULL, skip the "phase start_date >= project start_date" check
- If the project's `end_date` is NULL, skip the "phase end_date <= project end_date" check
- If both project dates exist, enforce full containment

Implementation: Custom validation rule in `StoreProjectPhaseRequest` and `UpdateProjectPhaseRequest` using `after()` method to load the parent project and compare dates.

---

## R5: Optimistic Locking for Concurrent Status Transitions

**Question:** How to prevent race conditions on project status transitions?

**Resolution:** Per the edge cases in the spec, use optimistic locking via `updated_at` comparison. The `TransitionProjectStatusRequest` accepts an `expected_updated_at` field. The service checks:

```php
if ($project->updated_at->ne(Carbon::parse($expectedUpdatedAt))) {
    throw ApiException::make(ApiErrorCode::CONFLICT_ERROR, 'Project was modified by another user');
}
```

This is lighter than database-level locking and matches the existing `CategoryService` pattern (version-based optimistic locking).

---

## R6: Soft-Delete Visibility for Admin

**Question:** How should Admin's `?with_trashed=true` filter work?

**Resolution:** In `ProjectRepository`, add a `paginateWithFilters()` method that conditionally applies `withTrashed()` when the authenticated user is Admin and the `with_trashed` query param is present. Non-admin requests never include trashed records (enforced in repository, not just controller).

---

## R7: Frontend Team Tab Placeholder

**Question:** The spec says tabs for documents/team will be present as placeholders. How to implement?

**Resolution:** Create the tabbed detail page with all 6 tabs (overview, phases, tasks, team, documents, timeline). The "tasks", "team", and "documents" tabs will show a placeholder component (`ProjectTabPlaceholder.vue`) with an i18n message like "Coming soon in a future update" / "قريبًا في تحديث مستقبلي". This avoids layout shifts when those stages are implemented later.

---

## R8: Budget Validation

**Question:** Can `budget_estimated` be zero?

**Resolution:** Per edge cases: budget must be a positive number. Validation rule: `['nullable', 'numeric', 'min:0.01']`. The field is nullable (a project can be created without a budget in DRAFT), but if provided, it must be > 0. `budget_actual` defaults to 0 and is not user-editable in this stage.

---

## R9: Geolocation Precision

**Question:** What precision for lat/lng storage?

**Resolution:** Per spec: `DECIMAL(10,7)` — provides ~1.1cm precision, more than sufficient for construction site locations. Validation: lat `[-90, 90]`, lng `[-180, 180]` with `decimal:0,7` rule.

---

## R10: ProjectType Enum vs Database Column

**Question:** Should `type` be a PHP enum or a raw DB enum?

**Resolution:** Both. Database column uses `ENUM('residential','commercial','infrastructure')` for data integrity. PHP uses a `ProjectType` backed enum with `label()` method for i18n (Arabic/English labels). The model casts `type` to `ProjectType::class`.

---

## R11: Controller Response Pattern

**Question:** The existing `CategoryController` uses manual `response()->json()` while `ApiResponseTrait` exists. Which pattern to follow?

**Resolution:** Use `ApiResponseTrait` methods (`$this->success()` and `$this->error()`) for consistency with the trait's correlation ID propagation. The controller should `use ApiResponseTrait` and call `$this->success(new ProjectResource($project), null, 201)` for creation responses.

---

## R12: Timeline Endpoint Data Structure

**Question:** What does the timeline endpoint return?

**Resolution:** The timeline endpoint (`GET /api/v1/projects/{id}/timeline`) returns a structured response:

```json
{
  "success": true,
  "data": {
    "project": {
      "id": 1,
      "name_ar": "...",
      "start_date": "2026-01-01",
      "end_date": "2026-12-31"
    },
    "phases": [
      {
        "id": 1,
        "name_ar": "...",
        "name_en": "...",
        "start_date": "2026-01-01",
        "end_date": "2026-03-31",
        "completion_percentage": 75,
        "status": "in_progress",
        "sort_order": 1
      }
    ]
  }
}
```

This is a read-only projection. No separate model or table is needed — it's a query on `project_phases` ordered by `start_date, sort_order`.
