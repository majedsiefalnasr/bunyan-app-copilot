# Security Checklist — Projects (المشاريع)

**Feature**: STAGE_12_PROJECTS  
**Spec**: `specs/runtime/012-projects/spec.md`  
**Generated**: 2026-04-19  
**Purpose**: Validate that security requirements are fully and unambiguously specified.

---

## RBAC & Authorization

- [ ] **CHK-SEC-001**: Are all 10 API endpoints explicitly mapped to their allowed roles in the spec? (Verify: every route in the API Contract table has a "Roles" column entry that resolves to specific `UserRole` enum values, not vague terms like "authorized users".)
- [ ] **CHK-SEC-002**: Is it specified that role checks MUST be enforced server-side via Laravel middleware/policies — not via client-side guards alone?
- [ ] **CHK-SEC-003**: Is the "Owner (Customer)" update permission scoped precisely — i.e., does the spec state that `owner_id` must match the authenticated user's ID, not just that the user has the Customer role?
- [ ] **CHK-SEC-004**: Are the role-scoping rules for project listing quantified for all 5 roles? (Admin=all, Customer=owned, Contractor/Architect/Engineer=assigned — each must be an explicit requirement, not implied.)
- [ ] **CHK-SEC-005**: Is the `with_trashed=true` query parameter restricted to Admin-only? Is it specified that non-Admin requests with this parameter are rejected or silently ignored?
- [ ] **CHK-SEC-006**: Are status transition permissions restricted to Admin-only? Does the spec explicitly forbid Customer/Contractor/Architect/Engineer from calling `PUT /projects/{id}/status`?
- [ ] **CHK-SEC-007**: Is phase CRUD (create, update, delete) restricted to Admin-only with explicit 403 RBAC_ROLE_DENIED for all other roles?

## Input Validation & Injection Prevention

- [ ] **CHK-SEC-008**: Are all input fields enumerated with type constraints and maximum lengths? (e.g., `name_ar` VARCHAR(255), `city` VARCHAR(100), `budget_estimated` DECIMAL(15,2))
- [ ] **CHK-SEC-009**: Is geolocation validation specified with precise ranges? (lat: -90 to 90, lng: -180 to 180 — are these boundary values included or excluded?)
- [ ] **CHK-SEC-010**: Is `owner_id` validated to reference an existing user with the Customer role? Does the spec require existence + role verification, not just FK constraint?
- [ ] **CHK-SEC-011**: Are Form Request classes explicitly listed for every mutation endpoint? (StoreProjectRequest, UpdateProjectRequest, StoreProjectPhaseRequest, UpdateProjectPhaseRequest, TransitionProjectStatusRequest)
- [ ] **CHK-SEC-012**: Is it specified that `status` cannot be set directly via create/update payloads — only via the dedicated status transition endpoint?
- [ ] **CHK-SEC-013**: Are date range validations specified with edge cases? (e.g., same-day start/end, null start_date with non-null end_date, phase dates within project dates)
- [ ] **CHK-SEC-014**: Is `budget_estimated` required to be positive? Is zero explicitly allowed or rejected?
- [ ] **CHK-SEC-015**: Is `type` constrained to the enum values only (residential, commercial, infrastructure)? Is free-text input explicitly rejected?

## Data Exposure & Information Leakage

- [ ] **CHK-SEC-016**: Does the spec require that 403 errors do NOT reveal whether a project exists? (i.e., unauthorized access to an existing project returns 403, but does the spec consider returning 404 instead to prevent enumeration?)
- [ ] **CHK-SEC-017**: Is it specified that soft-deleted projects return 404 (not 403 or a "deleted" indicator) for non-Admin users?
- [ ] **CHK-SEC-018**: Are API Resource response shapes defined to exclude sensitive fields? (e.g., does the project response include `owner_id` but not the owner's email/phone to unauthorized roles?)
- [ ] **CHK-SEC-019**: Is it specified that error responses for 5xx errors MUST NOT expose stack traces, SQL queries, or internal paths?

## Concurrency & Race Conditions

- [ ] **CHK-SEC-020**: Is the optimistic locking mechanism for status transitions fully specified? (Which field is used — `updated_at`? What HTTP status is returned on conflict — 409 CONFLICT_ERROR?)
- [ ] **CHK-SEC-021**: Is concurrent phase creation on the same project addressed? (e.g., two Admins adding phases simultaneously — is `sort_order` uniqueness enforced or merely advisory?)

## Audit & Observability

- [ ] **CHK-SEC-022**: Are all project mutations (create, update, status transition, delete) required to produce structured audit logs with correlation IDs and user context?
- [ ] **CHK-SEC-023**: Is it specified which fields must be logged on status transitions? (from_status, to_status, user_id, timestamp)
- [ ] **CHK-SEC-024**: Are failed authorization attempts (403 responses) required to be logged?

## Soft-Delete Security

- [ ] **CHK-SEC-025**: Is it specified that soft-deleted projects are excluded from all query scopes by default (global scope), not just filtered in the repository layer?
- [ ] **CHK-SEC-026**: Can a soft-deleted project be restored? If yes, is the restore action Admin-only? If no, is permanent deletion addressed?
- [ ] **CHK-SEC-027**: Are phases of a soft-deleted project also hidden from non-Admin queries?
