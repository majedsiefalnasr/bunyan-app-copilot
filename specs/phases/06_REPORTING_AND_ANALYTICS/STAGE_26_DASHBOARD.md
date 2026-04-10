# STAGE_26 — Dashboard

> **Phase:** 06_REPORTING_AND_ANALYTICS
> **Status:** NOT STARTED
> **Scope:** Admin and user dashboards, KPI widgets
> **Risk Level:** MEDIUM

## Stage Status

Status: NOT STARTED
Step: —
Risk Level: MEDIUM

## Objective

Implement role-specific dashboards with KPI widgets, recent activity, and quick actions.

## Scope

### Backend

- Dashboard service (aggregate metrics per role)
- Dashboard widget data endpoints
- Caching strategy for dashboard queries (Redis)
- Metric calculations: revenue, orders, projects, pending approvals

### Frontend

- Admin dashboard (platform overview: users, orders, revenue, projects)
- Customer dashboard (my projects, my orders, pending quotes)
- Contractor/Supplier dashboard (products, orders received, revenue)
- Engineer dashboard (assigned projects, tasks, pending approvals)
- Dashboard widget components (stat card, chart, recent list, quick actions)
- Chart integration (Chart.js or ApexCharts)

### API Endpoints

| Method | Route                             | Description                      |
| ------ | --------------------------------- | -------------------------------- |
| GET    | /api/v1/dashboard                 | Get dashboard data (role-scoped) |
| GET    | /api/v1/dashboard/metrics         | Get KPI metrics                  |
| GET    | /api/v1/dashboard/recent-activity | Recent activity summary          |

## Dependencies

- **Upstream:** All feature modules (aggregates data)
- **Downstream:** None
