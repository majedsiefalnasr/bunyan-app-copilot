# Clarify Report — STAGE_09 Suppliers

> **Phase:** 02_CATALOG_AND_INVENTORY
> **Generated:** 2026-04-15T00:10:00Z

## Clarification Summary

| Metric                | Value                                |
| --------------------- | ------------------------------------ |
| Questions Asked       | 8                                    |
| Questions Resolved    | 8                                    |
| Spec Sections Updated | 5 (§3.2, §4, §5.1, §5.7, §6.2, §6.4) |

## Resolved Clarifications

| #   | Topic                                           | Resolution                                                                                         | Impact                        |
| --- | ----------------------------------------------- | -------------------------------------------------------------------------------------------------- | ----------------------------- |
| 1   | Logo field type                                 | URL string only; file upload deferred to STAGE_15_FILE_MANAGEMENT                                  | §4 column note updated        |
| 2   | Admin notifications on submission               | Deferred to notifications stage. Already in §10 Out of Scope                                       | No spec change needed         |
| 3   | Contractor viewing another's unverified profile | **No.** Only Admin + owning Contractor see non-verified profiles                                   | §6.2 visibility rules updated |
| 4   | Phone regex `^05\d{8}$`                         | Confirmed correct for Saudi 10-digit mobile format                                                 | No change                     |
| 5   | `aggregateRatings` mechanism                    | Stub-only no-op in STAGE_09. Future ratings stage triggers updates                                 | §6.4 stub boundary added      |
| 6   | Pagination response shape                       | **Corrected**: uses `data: [...]` + `meta: {...}` at root (matches BaseApiController::paginated()) | §5.1, §5.7 updated            |
| 7   | Route model binding `{supplier}`                | Binds to `App\Models\SupplierProfile`. Register in `AppServiceProvider::boot()`                    | §3.2 route binding note added |
| 8   | Soft-deleted profiles visibility                | Invisible to all actors including Admin. Admin uses `suspend` to hide profiles                     | §6.2 updated                  |

## Remaining Ambiguities

- None — all clarifications resolved.

## Checklists Generated

| Checklist    | Path                       | Items |
| ------------ | -------------------------- | ----- |
| Requirements | checklists/requirements.md | 57    |
| Security     | checklists/security.md     | 54    |
| Testing      | checklists/testing.md      | 120+  |
