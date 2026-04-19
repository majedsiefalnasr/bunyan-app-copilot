# Performance Checklist — Projects (المشاريع)

**Feature**: STAGE_12_PROJECTS  
**Spec**: `specs/runtime/012-projects/spec.md`  
**Generated**: 2026-04-19  
**Purpose**: Validate that performance requirements are quantified, measurable, and architecturally achievable.

---

## Response Time & SLAs

- [ ] **CHK-PERF-001**: Is the 500ms SLA for project listing (NFR-001) specified with load conditions? (1,000 projects, 15 per page — but what about concurrent users? Is this p50, p95, or p99?)
- [ ] **CHK-PERF-002**: Are response time SLAs defined for project detail (`GET /projects/{id}`) and phase listing (`GET /projects/{id}/phases`) endpoints, or only for the listing endpoint?
- [ ] **CHK-PERF-003**: Is the timeline endpoint response time specified? (Timeline may involve aggregation across multiple phases.)
- [ ] **CHK-PERF-004**: Is the status transition endpoint response time specified? (Includes validation + state change + audit logging.)

## Database & Query Optimization

- [ ] **CHK-PERF-005**: Are database indexes explicitly defined for all filter columns? (Spec lists: `owner_id`, `status`, `type`, `city` — is `start_date`/`end_date` indexed for date-range filtering?)
- [ ] **CHK-PERF-006**: Is the composite index `project_phases(project_id, sort_order)` specified for efficient phase ordering queries?
- [ ] **CHK-PERF-007**: Is the role-scoped query strategy for non-Admin users specified? (Customer: `WHERE owner_id = ?`; Contractor/Architect/Engineer: JOIN on team_members pivot — is the join performance addressed given that the pivot table doesn't exist until STAGE_15?)
- [ ] **CHK-PERF-008**: Is eager loading specified for the project detail endpoint? (Avoiding N+1 queries when loading project + phases + owner relationships.)
- [ ] **CHK-PERF-009**: Is the `with_trashed` Admin query specified to use an index-friendly approach? (Soft-delete queries with `deleted_at IS NOT NULL` may not use indexes efficiently.)

## Pagination

- [ ] **CHK-PERF-010**: Is cursor-based vs offset-based pagination specified? (Offset pagination degrades at high page numbers — is this acceptable for the expected dataset size?)
- [ ] **CHK-PERF-011**: Is the maximum `per_page` value capped? (Can a client request `per_page=10000` and bypass pagination?)
- [ ] **CHK-PERF-012**: Is the pagination response metadata format specified? (`current_page`, `per_page`, `total`, `last_page` — is `total` count query performance addressed for large datasets?)

## Filtering & Search

- [ ] **CHK-PERF-013**: Is the combined filter query performance addressed? (Filtering by status + type + city + date range simultaneously — is there a compound index strategy?)
- [ ] **CHK-PERF-014**: Is the `city` filter specified as exact match or partial/fuzzy match? (Partial matching on VARCHAR without full-text indexing has performance implications.)
- [ ] **CHK-PERF-015**: Are filter parameters validated before query execution to reject empty or wildcard-like inputs that could trigger full table scans?

## Nested Includes & Payload Size

- [ ] **CHK-PERF-016**: Is the `include` parameter for nested relationships defined with allowed values? (e.g., `?include=phases,owner` — is there a limit on include depth?)
- [ ] **CHK-PERF-017**: Is the project listing response payload size addressed? (Listing all projects with nested phases could produce very large responses — are nested includes excluded from list endpoints?)
- [ ] **CHK-PERF-018**: Is the timeline endpoint response shape optimized? (Does it return full phase objects or a projected subset of fields for rendering?)

## Caching Strategy

- [ ] **CHK-PERF-019**: Is caching specified for any project endpoints? (Timeline data and project counts are good caching candidates — is cache invalidation on status transition or phase mutation addressed?)
- [ ] **CHK-PERF-020**: Is the role-scoped listing cacheable? (Per-user cache keys for different role views — is this addressed or deferred?)

## Scalability Assumptions

- [ ] **CHK-PERF-021**: Is the expected project volume specified beyond "up to 1,000"? (Is 1,000 the year-one target or the permanent ceiling? This affects index and query strategy.)
- [ ] **CHK-PERF-022**: Is the expected phases-per-project count specified? (5 phases vs 50 phases significantly affects timeline and detail endpoint performance.)
- [ ] **CHK-PERF-023**: Is concurrent write volume addressed? (How many Admins creating/updating projects simultaneously?)
