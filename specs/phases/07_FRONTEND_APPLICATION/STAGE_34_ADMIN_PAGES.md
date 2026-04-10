# STAGE_34 — Admin Pages

> **Phase:** 07_FRONTEND_APPLICATION
> **Status:** NOT STARTED
> **Scope:** Admin panel, user management, settings, platform config
> **Risk Level:** MEDIUM

## Stage Status

Status: NOT STARTED
Step: —
Risk Level: MEDIUM

## Objective

Implement the admin panel with user management, platform settings, and system configuration pages using **Nuxt UI** components.

## Scope

### Frontend Pages

| Page                   | Route                | Description                        |
| ---------------------- | -------------------- | ---------------------------------- |
| Admin Dashboard        | /admin               | Admin overview with KPIs           |
| User Management        | /admin/users         | User CRUD, role assignment, status |
| User Detail            | /admin/users/:id     | User profile with activity         |
| Role Management        | /admin/roles         | Role and permission management     |
| Category Management    | /admin/categories    | Category tree management           |
| Supplier Verification  | /admin/suppliers     | Supplier review and verification   |
| Platform Settings      | /admin/settings      | General platform configuration     |
| Notification Templates | /admin/notifications | Email/SMS template management      |
| Activity Log           | /admin/activity-log  | Platform-wide activity log         |
| Reports Center         | /admin/reports       | Report generation center           |
| Analytics Dashboard    | /admin/analytics     | Platform analytics                 |

### Nuxt UI Component Map

| Element                      | Nuxt UI Component                                        |
| ---------------------------- | -------------------------------------------------------- |
| Admin layout (wide sidebar)  | `UDashboardLayout` + `UDashboardSidebar`                 |
| KPI stat cards               | `UDashboardCard` + `UStatBlock`                          |
| User data table              | `UTable` (sortable, filterable, paginated)               |
| User status badge            | `UBadge` (active=green, suspended=red, pending=orange)   |
| Role permission matrix       | `UTable` + `UCheckbox` per permission                    |
| Category tree                | `UTree` / custom tree with `UAccordion`                  |
| Supplier verification card   | `UCard` with approve/reject `UButtonGroup`               |
| Settings form                | `UForm` + `UFormField` + `USwitch` toggles               |
| Activity log feed            | `UFeed` / `UTable` with actor + subject                  |
| Notification template editor | `UTextarea` (with preview `UCard`)                       |
| Report generation            | `UForm` + `USelect` (type/period) + `UButton` (download) |
| Analytics charts             | Chart.js (via `vue-chartjs`) in `UCard` wrappers         |
| Search / filter bar          | `UInput` + `USelect` filters                             |
| Bulk actions                 | `UCheckbox` row select + `UDropdownMenu` action menu     |

### Components

- `AdminLayout` — `UDashboardLayout` with admin navigation tree
- `UserTable` — `UTable` with sortable columns, role badge, status action
- `RolePermissionMatrix` — `UTable` + `UCheckbox` grid for role-permission management
- `SettingsForm` — dynamic `UForm` with `USwitch` and `UInput` fields
- `VerificationCard` — `UCard` supplier verification with approve/reject `UButtonGroup`
- `DataTable` — generic reusable `UTable` with pagination, sort, filter, column config

## Testing

### Unit Tests (Vitest)

- `useAdminUsers` — pagination cursor, filter params build correctly
- Permission matrix — toggling permission updates store and emits correct payload

### E2E Tests (Playwright)

| Test Case                             | Scenario                                                          |
| ------------------------------------- | ----------------------------------------------------------------- |
| Admin can view user list              | Login as Admin → /admin/users → table with users visible          |
| Admin can suspend user                | Click user row → "Suspend" action → status badge changes to red   |
| Admin approves supplier               | /admin/suppliers → click "Approve" → status changes to "Verified" |
| Role permission matrix toggles        | Toggle permission → save → reload → permission state persists     |
| Category tree displays hierarchically | /admin/categories → parent/child nesting visible                  |
| Settings toggle saved                 | Toggle a platform setting → page reload → setting persists        |
| Activity log paginates                | /admin/activity-log → next page → different entries visible       |
| Non-admin cannot access admin routes  | Login as Customer → visit /admin → redirected to dashboard        |

```typescript
// tests/e2e/admin.spec.ts
import {test, expect} from '@playwright/test'

test('non-admin is redirected from admin panel', async ({page}) => {
  await page.goto('/auth/login')
  await page.fill('[data-testid="email-input"]', 'customer@example.com')
  await page.fill('[data-testid="password-input"]', 'password123')
  await page.click('[data-testid="login-button"]')
  await page.goto('/admin')
  await expect(page).not.toHaveURL('/admin')
})

test('admin can approve a pending supplier', async ({page}) => {
  await page.goto('/admin/suppliers')
  await page.click('[data-testid="supplier-approve-btn"]:first-child')
  await page.click('[data-testid="confirm-approve"]')
  await expect(page.locator('[data-testid="supplier-status"]:first-child')).toContainText(
    'Verified'
  )
})
```

## Dependencies

- **Upstream:** All backend stages, STAGE_29_NUXT_SHELL
- **Downstream:** None
