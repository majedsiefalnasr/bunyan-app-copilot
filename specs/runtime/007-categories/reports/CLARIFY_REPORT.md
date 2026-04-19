# Clarify Report — STAGE_07_CATEGORIES

> **Phase:** 02_CATALOG_AND_INVENTORY | **Generated:** 2026-04-15

## Clarification Summary

| Metric                | Value                               |
| --------------------- | ----------------------------------- |
| Questions Asked       | 5 targeted clarification questions  |
| Questions Resolved    | 5 (100%)                            |
| Spec Sections Updated | 6 (FR, assumptions, clarifications) |

## Resolved Clarifications

| #   | Topic                  | Resolution                                        | Impact                                                |
| --- | ---------------------- | ------------------------------------------------- | ----------------------------------------------------- |
| 1   | Slug Immutability      | Slugs are immutable; names don't regenerate       | Preserves URL stability, adds `version` field         |
| 2   | Parent Soft-Delete     | Children remain with parent_id; no cascade        | Preserves audit trail, admin has orphan control       |
| 3   | Tree Response Format   | Nested structure with recursive `children` arrays | Simplifies frontend rendering, aligns with REST       |
| 4   | Soft-Delete Visibility | Soft-deleted categories invisible globally        | Keeps queries clean, audit-only visibility for admins |
| 5   | Concurrent Reorder     | Optimistic locking via `version` field            | Prevents data loss from simultaneous admin edits      |

## Clarification Details

### 1. Slug Immutability ✅

**Decision**: Slugs remain immutable after creation. When category names are updated (name_ar, name_en), the slug does NOT regenerate.

**Rationale**: Immutable slugs preserve URL stability for external links and SEO. Frontend links to `/categories/building-materials` won't break if the name changes to "مواد بناء". A `version` field prevents version-based reorder conflicts.

**Implementation**: Add `version` field to Category model. Use soft-delete scoping by default. Update `PUT /api/v1/categories/{id}` to validate slug immutability.

### 2. Parent Soft-Delete Behavior ✅

**Decision**: When a parent category is soft-deleted, children remain with their parent_id intact. No cascading delete. No orphaning logic.

**Rationale**: Preserves audit trail. Gives admins explicit control to restore parents or reassign children. Prevents accidental data cascades.

**Implementation**: Add `withoutTrashed()` scope in CategoryRepository when fetching active tree. Admin soft-delete scope (`onlyTrashed()`) allows viewing orphaned records.

### 3. API Tree Response Format ✅

**Decision**: Tree responses use nested structure with recursive `children` arrays, not flat parent_id references.

**Rationale**: Simplifies frontend rendering. Modern REST conventions favor nested data over flat + foreign keys.

**Implementation**: CategoryResource must recursively load children. Use `when()` to conditionally load children based on depth. Cache tree at top-level to avoid N+1.

### 4. Soft-Delete Visibility ✅

**Decision**: Soft-deleted categories are globally invisible except in dedicated admin scopes.

**Rationale**: Keeps query results clean. Products and users never see deleted categories. Admins access via `?withTrashed=true` or admin-only routes.

**Implementation**: Use Laravel's native soft-delete scoping (`withoutTrashed()` by default). Create admin routes with `withTrashed()` scopes.

### 5. Concurrent Reorder Handling ✅

**Decision**: Implement optimistic locking via a `version` field on categories. Reorder requests include `version`; if version mismatch, return 409 Conflict.

**Rationale**: Prevents data loss when two admins reorder simultaneously. Tells client to refresh and retry.

**Implementation**: Add `version` incremented on every update (reorder, edit). Use raw SQL `UPDATE categories SET sort_order = ?, version = version + 1 WHERE id = ? AND version = ?`.

---

## Checklists Generated

| Checklist     | Path                        | Items |
| ------------- | --------------------------- | ----- |
| Requirements  | checklists/requirements.md  | 150+  |
| Security      | checklists/security.md      | 29    |
| Performance   | checklists/performance.md   | 30    |
| Accessibility | checklists/accessibility.md | 39    |

## Remaining Ambiguities

✅ None — all clarifications resolved and integrated.

## Next Steps

→ **Step 3 — Plan**: Generate technical plan with dependencies, migrations, entity models, and API contracts.

**Status**: ✅ **READY FOR PLANNING**
