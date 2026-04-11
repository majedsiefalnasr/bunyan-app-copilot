# Clarify Report — DATABASE_SCHEMA

> **Phase:** 01_PLATFORM_FOUNDATION
> **Generated:** 2026-04-11T00:02:00Z

## Clarification Summary

| Metric                | Value |
| --------------------- | ----- |
| Questions Asked       | 5     |
| Questions Resolved    | 5     |
| Spec Sections Updated | 3 (Assumptions, new Migration Strategy, new Clarifications) |

## Resolved Clarifications

| # | Topic | Resolution | Impact |
|---|-------|------------|--------|
| CLR-001 | UUID vs Auto-Increment | Bigint auto-increment confirmed (matches existing STAGE_01 models + migration) | No impact — spec already used bigint |
| CLR-002 | Translation column strategy | Separate `display_name_ar` column per translatable entity | Simpler schema; no polymorphic translations table needed |
| CLR-003 | Existing users table conflict | New `add_profile_columns_to_users_table.php` migration adds phone/is_active/avatar/deleted_at | STAGE_01 migration preserved; forward-only discipline maintained |
| CLR-004 | `role` enum coexistence | Existing enum kept; `roles` table + `role_user` are ADDITIVE | STAGE_03/STAGE_04 compatibility preserved; no breaking change |
| CLR-005 | NFR-002 scope (no ENUM) | Rule scoped to NEW STAGE_02 tables only; existing STAGE_01 enum is exempt | No retro-breaking of existing schema |

## Remaining Ambiguities

- None — all clarifications resolved.

## Checklists Generated

| Checklist            | Path                                                  | Items |
| -------------------- | ----------------------------------------------------- | ----- |
| Requirements         | checklists/requirements.md                            | 88    |
| Security coverage    | Embedded in requirements.md (CHK058–064)              | 7     |
| DB integrity         | Embedded in requirements.md (CHK065–072)              | 8     |
| Testing coverage     | Embedded in requirements.md (CHK079–088)              | 10    |

## Spec Sections Added/Modified

1. **Migration Strategy** — New section documenting 5-migration sequence, STAGE_01 immutability, `role` enum coexistence
2. **Assumptions** — Replaced 2 `[NEEDS CLARIFICATION]` markers with confirmed decisions
3. **Clarifications** — New section with all 5 resolutions documented
