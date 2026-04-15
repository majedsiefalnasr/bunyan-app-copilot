# Tasks Report — STAGE_09_SUPPLIERS

> **Phase:** 02_CATALOG_AND_INVENTORY
> **Generated:** 2026-04-15T00:30:00Z
> **Branch:** `spec/009-suppliers`

---

## Task Summary

| Metric             | Value |
| ------------------ | ----- |
| Total Tasks        | 39    |
| Parallelizable [P] | 10    |
| Sequential         | 29    |
| HIGH Risk          | 7     |
| MEDIUM Risk        | 16    |
| LOW Risk           | 16    |

**Phase breakdown:**

| Phase               | Tasks     | Count |
| ------------------- | --------- | ----- |
| P0 — Database       | T001–T005 | 5     |
| P1 — Backend Core   | T006–T009 | 4     |
| P2 — HTTP Layer     | T010–T018 | 9     |
| P3 — Frontend       | T019–T028 | 10    |
| P4 — Tests + i18n   | T029–T036 | 8     |
| Validation Pipeline | T037–T039 | 3     |

---

## Risk-Ranked Task View

### 🔴 HIGH Risk Tasks

| ID   | Description                                             | Risk Factor                                                             |
| ---- | ------------------------------------------------------- | ----------------------------------------------------------------------- |
| T002 | Create `create_supplier_profiles_table` migration       | Schema change: 2 FKs, unique constraints, ENUM column, soft deletes     |
| T007 | Create `SupplierRepository`                             | `scopeVisibleTo` OR-visibility logic; N+1 risk if lazy loaded           |
| T008 | Create `SupplierService`                                | 3-branch admin create logic; ADR-009-01 enforcement; RBAC gates         |
| T009 | Register binding + route model binding                  | AppServiceProvider change; incorrect binding breaks entire module       |
| T016 | Create `SupplierController`                             | ADR-009-01: `show()` intentionally skips `authorize()` — must be exact  |
| T034 | `SupplierControllerTest` feature test suite (20 cases)  | Full RBAC matrix; index visibility, store 409 conflict, admin on-behalf |
| T035 | `SupplierVerificationWorkflowTest` (6 transition tests) | All 6 status transitions including `suspended → verified` direct path   |

### 🟡 MEDIUM Risk Tasks

| ID   | Description                                 | Risk Factor                                              |
| ---- | ------------------------------------------- | -------------------------------------------------------- |
| T003 | Create `SupplierProfile` Eloquent model     | `$fillable` exclusions (SEC-FINDING-A), `scopeVisibleTo` |
| T005 | Create `SupplierProfileFactory`             | Factory states tied to UserFactory contractor state      |
| T006 | Create `SupplierRepositoryInterface`        | PHPStan level-8 typed signatures; no base extension      |
| T010 | Create `StoreSupplierRequest`               | `user_id` nullable admin-only field security boundary    |
| T011 | Create `UpdateSupplierRequest`              | `unique()->ignore()` rule; exclusion of protected fields |
| T014 | Create `SupplierPolicy`                     | `Gate::before` admin bypass must not bleed to non-admin  |
| T015 | Create `SupplierResource`                   | Enum cast + nullable `toISOString()` safety              |
| T017 | Create supplier route file                  | Public vs auth split; route naming prefix                |
| T018 | Register route file in `api.php`            | Affects global routing; could mask other routes          |
| T020 | Create `useSupplier` composable             | Typed Promise returns; all 7 HTTP calls                  |
| T021 | Create `useSupplierStore` Pinia store       | State management; reactive meta synchronization          |
| T024 | Create `SupplierForm.vue`                   | Zod schema parity with backend validation rules          |
| T036 | `SupplierServiceTest` unit tests (11 cases) | Mockery repository mocks; CONFLICT_ERROR branch          |
| T037 | Backend lint + PHPStan analysis             | PHPStan level-8 on full module                           |
| T038 | Run backend PHPUnit tests                   | SQLite in-memory migration compatibility                 |
| T039 | Frontend lint + typecheck + Vitest          | TS strict mode; composable + store + component coverage  |

### 🟢 LOW Risk Tasks

| ID   | Description                                      | Risk Factor                                    |
| ---- | ------------------------------------------------ | ---------------------------------------------- |
| T001 | Create `SupplierVerificationStatus` enum         | Additive; no existing code affected            |
| T004 | Add `supplierProfile()` HasOne to `User` model   | Single additive relationship method            |
| T012 | Create `VerifySupplierRequest` (empty body)      | Trivial stub request class                     |
| T013 | Create `SuspendSupplierRequest` (empty body)     | Mirror of T012                                 |
| T019 | Create `supplier.ts` TypeScript type definitions | Frontend types only; no runtime logic          |
| T022 | Create `SupplierCard.vue`                        | Presentational component; no side effects      |
| T023 | Create `VerificationStatusBadge.vue`             | Presentational; i18n keys only                 |
| T025 | Create suppliers directory page                  | Standard list page with store integration      |
| T026 | Create supplier detail page                      | Standard show page + stub products section     |
| T027 | Create contractor profile management page        | Standard dashboard create/edit page            |
| T028 | Create admin supplier management page            | Admin CRUD table with inline actions           |
| T029 | Create `SupplierSeeder`                          | Dev-only seeder; no production impact          |
| T030 | Create `lang/ar/suppliers.php`                   | New translation file; additive                 |
| T031 | Create `lang/en/suppliers.php`                   | Mirror translation file; additive              |
| T032 | Add supplier keys to `frontend/locales/ar.json`  | `modify` existing file; additive key additions |
| T033 | Add supplier keys to `frontend/locales/en.json`  | Mirror of T032; additive                       |

---

## External Dependency Tasks

No new third-party package dependencies introduced. All implementation uses existing Bunyan stack:

- Laravel 11 (Eloquent, Policies, Form Requests, Resources)
- PHPStan (level-8) — already configured in `backend/phpstan.neon`
- Nuxt UI / Vue 3 / Pinia / VeeValidate + Zod — already in frontend stack

---

## High-Downstream-Impact Tasks

| Task ID | Description                       | Downstream Impact                                                       |
| ------- | --------------------------------- | ----------------------------------------------------------------------- |
| T001    | `SupplierVerificationStatus` enum | Required by T002 (migration), T003 (cast), T007 (repository), T008      |
| T002    | Migration                         | Required by ALL P1+ tasks; blocks the entire implementation             |
| T003    | `SupplierProfile` model           | Required by T005, T006, T007, T008, T010–T018                           |
| T006    | `SupplierRepositoryInterface`     | Required by T007 (implementation), T008 (injection), T009 (binding)     |
| T008    | `SupplierService`                 | Required by T016 (controller), T034–T036 (tests)                        |
| T009    | AppServiceProvider binding        | Required by T016 (DI resolution at runtime)                             |
| T019    | `supplier.ts` TypeScript types    | Required by T020 (composable), T021 (store), T022–T028 (all components) |

---

## Parallel Execution Groups

| Group     | Tasks                  | Condition                                                  |
| --------- | ---------------------- | ---------------------------------------------------------- |
| Group P-1 | T010, T011, T012, T013 | All P2 Form Request classes — no dependencies between them |
| Group P-2 | T014, T015             | Policy + Resource — independent; both need T003 done       |
| Group P-3 | T022, T023             | Presentational components — both need T019 done            |
| Group P-4 | T030, T031, T032, T033 | i18n files — fully independent                             |

---

## Architecture Compliance Notes

1. **ADR-009-01 (binding):** T016 `show()` must skip `authorize()` — enforced via comment + service throws `RESOURCE_NOT_FOUND`. Reviewed in T034.
2. **SEC-FINDING-A (T003):** `$fillable` MUST exclude `verification_status`, `user_id`, `verified_at`, `verified_by`, `rating_avg`, `total_ratings`.
3. **T014 Admin bypass:** `Gate::before` handles admin globally; individual policy methods return `false` for admin-only actions (`verify`, `suspend`, `delete`).
4. **T008 3-branch create logic:** Admin+`user_id` → validate contractor target; Admin+no `user_id` → VALIDATION_ERROR; Contractor → use actor ID. Covered in T034 + T036.
5. **T007 scopeVisibleTo:** OR visibility (verified + own) expressed via Eloquent scope passed to repository; avoids leaking visibility logic into controller.
