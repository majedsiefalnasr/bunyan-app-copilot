# Bunyan — Dependency Graph

> Visual dependency map between all stages.

```
Phase 01: Platform Foundation
┌──────────────────────────────────────────────────────┐
│                                                      │
│  STAGE_01 ──→ STAGE_02 ──→ STAGE_03 ──→ STAGE_04   │
│     │            │                         │         │
│     │            │                    STAGE_06       │
│     │            │                      ↑            │
│     └─────────→ STAGE_05 ──────────────┘            │
│                                                      │
└──────────────────────────────────────────────────────┘

Phase 02: Catalog & Inventory
┌──────────────────────────────────────────────────────┐
│                                                      │
│  STAGE_06 ──→ STAGE_07 ──→ STAGE_08                 │
│                               │                      │
│  STAGE_04 ──→ STAGE_09       ├──→ STAGE_10          │
│                               └──→ STAGE_11          │
│                                                      │
└──────────────────────────────────────────────────────┘

Phase 03: Project Management
┌──────────────────────────────────────────────────────┐
│                                                      │
│  STAGE_04 ──→ STAGE_12 ──→ STAGE_13 ──→ STAGE_14   │
│                  │                                   │
│                  ├──→ STAGE_15                       │
│                  └──→ STAGE_16                       │
│                                                      │
└──────────────────────────────────────────────────────┘

Phase 04: Commercial Layer
┌──────────────────────────────────────────────────────┐
│                                                      │
│  STAGE_08 + STAGE_11 + STAGE_12 ──→ STAGE_17       │
│  STAGE_09 + STAGE_17 ──→ STAGE_18                   │
│  STAGE_08 + STAGE_10 + STAGE_18 ──→ STAGE_19       │
│  STAGE_19 + STAGE_14 ──→ STAGE_20                   │
│  STAGE_19 + STAGE_20 ──→ STAGE_21                   │
│                                                      │
└──────────────────────────────────────────────────────┘

Phase 05: Communication & Media
┌──────────────────────────────────────────────────────┐
│                                                      │
│  STAGE_06 ──→ STAGE_22 (Notifications)              │
│  STAGE_06 ──→ STAGE_23 (Messaging)                  │
│  STAGE_06 ──→ STAGE_24 (Media Library)              │
│  STAGE_06 ──→ STAGE_25 (Activity Log)               │
│                                                      │
└──────────────────────────────────────────────────────┘

Phase 06: Reporting & Analytics
┌──────────────────────────────────────────────────────┐
│                                                      │
│  ALL STAGES ──→ STAGE_26 (Dashboard)                │
│  ALL STAGES ──→ STAGE_27 (Reports)                  │
│  ALL STAGES ──→ STAGE_28 (Analytics)                │
│                                                      │
└──────────────────────────────────────────────────────┘

Phase 07: Frontend Application
┌──────────────────────────────────────────────────────┐
│                                                      │
│  STAGE_01 ──→ STAGE_29 (Nuxt Shell)                 │
│  STAGE_03 + STAGE_29 ──→ STAGE_30 (Auth Pages)     │
│  STAGE_07-09 + STAGE_29 ──→ STAGE_31 (Catalog)     │
│  STAGE_12-16 + STAGE_29 ──→ STAGE_32 (Project)     │
│  STAGE_17-21 + STAGE_29 ──→ STAGE_33 (Commercial)  │
│  ALL + STAGE_29 ──→ STAGE_34 (Admin)               │
│                                                      │
└──────────────────────────────────────────────────────┘
```
