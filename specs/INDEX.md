# Bunyan — Master Index

> **Version:** 1.0.0
> **Last Updated:** 2025-01-01
> **Platform:** Bunyan (بنيان) — Arabic Construction Services Marketplace

---

## Phase Structure

### Phase 01 — PLATFORM FOUNDATION

| Stage    | Name                   | Status      | Risk   | Scope                                    |
| -------- | ---------------------- | ----------- | ------ | ---------------------------------------- |
| STAGE_01 | Project Initialization | NOT STARTED | LOW    | Laravel + Nuxt.js setup, tooling, CI     |
| STAGE_02 | Database Schema        | NOT STARTED | MEDIUM | Core MySQL schema, Eloquent patterns     |
| STAGE_03 | Authentication         | NOT STARTED | HIGH   | Sanctum auth, registration, login        |
| STAGE_04 | RBAC System            | NOT STARTED | HIGH   | Roles, permissions, middleware           |
| STAGE_05 | Error Handling         | NOT STARTED | LOW    | Error contract, logging                  |
| STAGE_06 | API Foundation         | NOT STARTED | MEDIUM | Routing, middleware stack, rate limiting |

### Phase 02 — CATALOG & INVENTORY

| Stage    | Name       | Status      | Risk   | Scope                                 |
| -------- | ---------- | ----------- | ------ | ------------------------------------- |
| STAGE_07 | Categories | NOT STARTED | LOW    | Hierarchical product categories       |
| STAGE_08 | Products   | NOT STARTED | MEDIUM | Product catalog, variants, attributes |
| STAGE_09 | Suppliers  | NOT STARTED | MEDIUM | Supplier profiles, verification       |
| STAGE_10 | Inventory  | NOT STARTED | MEDIUM | Stock tracking, availability          |
| STAGE_11 | Pricing    | NOT STARTED | MEDIUM | Pricing rules, bulk tiers             |

### Phase 03 — PROJECT MANAGEMENT

| Stage    | Name                | Status      | Risk   | Scope                           |
| -------- | ------------------- | ----------- | ------ | ------------------------------- |
| STAGE_12 | Projects            | NOT STARTED | HIGH   | Project CRUD, phases, timelines |
| STAGE_13 | Tasks               | NOT STARTED | MEDIUM | Task management, assignment     |
| STAGE_14 | Workflow Engine     | NOT STARTED | HIGH   | Approvals, state machine        |
| STAGE_15 | Team Management     | NOT STARTED | LOW    | Teams, roles, invitations       |
| STAGE_16 | Document Management | NOT STARTED | LOW    | File uploads, versioning        |

### Phase 04 — COMMERCIAL LAYER

| Stage    | Name           | Status      | Risk   | Scope                      |
| -------- | -------------- | ----------- | ------ | -------------------------- |
| STAGE_17 | Cost Estimator | NOT STARTED | HIGH   | BOQ, material calculations |
| STAGE_18 | Quotations     | NOT STARTED | MEDIUM | RFQ, supplier quotes       |
| STAGE_19 | Orders         | NOT STARTED | HIGH   | Order management, checkout |
| STAGE_20 | Payments       | NOT STARTED | HIGH   | Payment processing         |
| STAGE_21 | Invoicing      | NOT STARTED | MEDIUM | Invoices, VAT, ZATCA       |

### Phase 05 — COMMUNICATION & MEDIA

| Stage    | Name          | Status      | Risk   | Scope                    |
| -------- | ------------- | ----------- | ------ | ------------------------ |
| STAGE_22 | Notifications | NOT STARTED | MEDIUM | Push, email, SMS, in-app |
| STAGE_23 | Messaging     | NOT STARTED | MEDIUM | In-app messaging         |
| STAGE_24 | Media Library | NOT STARTED | LOW    | Media uploads, galleries |
| STAGE_25 | Activity Log  | NOT STARTED | LOW    | Audit trail              |

### Phase 06 — REPORTING & ANALYTICS

| Stage    | Name      | Status      | Risk   | Scope                    |
| -------- | --------- | ----------- | ------ | ------------------------ |
| STAGE_26 | Dashboard | NOT STARTED | MEDIUM | Role-specific dashboards |
| STAGE_27 | Reports   | NOT STARTED | MEDIUM | Business reports, export |
| STAGE_28 | Analytics | NOT STARTED | LOW    | Usage analytics, KPIs    |

### Phase 07 — FRONTEND APPLICATION

| Stage    | Name             | Status      | Risk   | Scope                         |
| -------- | ---------------- | ----------- | ------ | ----------------------------- |
| STAGE_29 | Nuxt Shell       | NOT STARTED | MEDIUM | App shell, layouts, nav, RTL  |
| STAGE_30 | Auth Pages       | NOT STARTED | LOW    | Login, register, reset        |
| STAGE_31 | Catalog Pages    | NOT STARTED | MEDIUM | Products, categories, search  |
| STAGE_32 | Project Pages    | NOT STARTED | HIGH   | Project management UI         |
| STAGE_33 | Commercial Pages | NOT STARTED | HIGH   | Orders, payments, invoices UI |
| STAGE_34 | Admin Pages      | NOT STARTED | MEDIUM | Admin panel, settings         |

---

## Dependency Graph

```
STAGE_01 → STAGE_02 → STAGE_03 → STAGE_04 → STAGE_06
STAGE_01 → STAGE_05 → STAGE_06
STAGE_06 → STAGE_07 → STAGE_08
STAGE_04 → STAGE_09
STAGE_08 → STAGE_10
STAGE_08 → STAGE_11
STAGE_04 → STAGE_12 → STAGE_13 → STAGE_14
STAGE_12 → STAGE_15
STAGE_12 → STAGE_16
STAGE_08 + STAGE_11 + STAGE_12 → STAGE_17 → STAGE_18
STAGE_08 + STAGE_10 + STAGE_18 → STAGE_19 → STAGE_20 → STAGE_21
STAGE_06 → STAGE_22, STAGE_23, STAGE_24, STAGE_25
ALL → STAGE_26, STAGE_27, STAGE_28
STAGE_01 → STAGE_29 → STAGE_30, STAGE_31, STAGE_32, STAGE_33, STAGE_34
```

---

## Totals

- **Phases:** 7
- **Stages:** 34
- **High Risk:** 8 stages
- **Medium Risk:** 14 stages
- **Low Risk:** 12 stages
