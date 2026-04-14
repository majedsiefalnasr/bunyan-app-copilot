# Clarify Report — API Foundation

> **Phase:** 01_PLATFORM_FOUNDATION
> **Generated:** 2026-04-14T00:02:00Z

## Clarification Summary

| Metric                | Value                       |
| --------------------- | --------------------------- |
| Questions Asked       | 5                           |
| Questions Resolved    | 5                           |
| Spec Sections Updated | 1 (## Clarifications added) |

## Resolved Clarifications

| #      | Topic                              | Resolution                                                                                                                           | Impact                 |
| ------ | ---------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ | ---------------------- |
| CLR-01 | Middleware registration location   | Use `bootstrap/app.php` (Laravel 11 style), NOT `Kernel.php` — FR-016/017/018 terminology corrected                                  | FR-016, FR-017, FR-018 |
| CLR-02 | Rate limiter conflict resolution   | Named limiters applied per route group only; no default blanket throttle on `api` group to avoid conflict with FR-021/FR-022         | FR-017, FR-021, FR-022 |
| CLR-03 | Health check vs built-in `/up`     | `GET /up` (Laravel built-in liveness) and `GET /api/health` (readiness probe) coexist — different purposes, neither removed          | FR-034                 |
| CLR-04 | HealthController pattern exemption | `HealthController` is explicitly exempt from Service/Repository pattern — DB+cache probes are infrastructure diagnostics, not domain | FR-035, FR-037, FR-038 |
| CLR-05 | l5-swagger configuration           | Annotation scan path = `app_path('Http/Controllers')`; relative server URL = `/api`; annotations isolated in `OpenApiAnnotations`    | FR-041, FR-042, FR-046 |

## Remaining Ambiguities

- None — all clarifications resolved. Spec status: **Implementation-ready**.

## Checklists Generated

| Checklist     | Path                        | Items |
| ------------- | --------------------------- | ----- |
| Requirements  | checklists/requirements.md  | 64+   |
| Security      | checklists/security.md      | 33    |
| Performance   | checklists/performance.md   | 17    |
| Accessibility | checklists/accessibility.md | 14    |
