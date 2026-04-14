# Data Model — RBAC System

> **Stage:** STAGE_04 — RBAC System  
> **Created:** 2026-04-13  
> **Based on:** `specs/runtime/004-rbac-system/spec.md`

---

## Entity Relationship Diagram

```
┌─────────────────────┐
│       users          │
├─────────────────────┤
│ id            (PK)   │
│ name                 │
│ email                │
│ role     (enum)      │◄── UserRole enum cast
│ is_active   (bool)   │
│ ...                  │
│ deleted_at           │
└──────────┬──────────┘
           │
           │ belongsToMany
           │ (role_user pivot)
           │
           ▼
┌─────────────────────┐         ┌──────────────────────┐
│       roles          │         │    permission_role    │
├─────────────────────┤         ├──────────────────────┤
│ id            (PK)   │◄───────│ role_id       (FK)    │
│ name                 │         │ permission_id (FK)    │
│ display_name         │         │ created_at            │
│ display_name_ar      │         │ updated_at            │
│ description          │         └──────────┬───────────┘
│ created_at           │                    │
│ updated_at           │                    │
└─────────────────────┘                    │
                                           │
                                           ▼
                               ┌─────────────────────┐
                               │    permissions       │
                               ├─────────────────────┤
                               │ id            (PK)   │
                               │ name                 │
                               │ display_name         │
                               │ group                │
                               │ description          │
                               │ created_at           │
                               │ updated_at           │
                               └─────────────────────┘

┌─────────────────────┐
│     role_user        │
├─────────────────────┤
│ role_id       (FK)   │──► roles.id     (CASCADE)
│ user_id       (FK)   │──► users.id     (CASCADE)
│ created_at           │
│ updated_at           │
└─────────────────────┘
```

---

## Table Schemas (Existing — No Migrations Needed)

### `users` Table

| Column              | Type                                                                             | Nullable | Default      | Notes                   |
| ------------------- | -------------------------------------------------------------------------------- | -------- | ------------ | ----------------------- |
| `id`                | `bigint unsigned` (PK)                                                           | No       | AI           |                         |
| `name`              | `varchar(255)`                                                                   | No       |              |                         |
| `email`             | `varchar(255)` UNIQUE                                                            | No       |              |                         |
| `email_verified_at` | `timestamp`                                                                      | Yes      | NULL         |                         |
| `password`          | `varchar(255)`                                                                   | No       |              | Hashed via cast         |
| `role`              | `enum('customer','contractor','supervising_architect','field_engineer','admin')` | No       | `'customer'` | Cast to `UserRole` enum |
| `phone`             | `varchar(255)`                                                                   | Yes      | NULL         |                         |
| `avatar`            | `varchar(255)`                                                                   | Yes      | NULL         |                         |
| `is_active`         | `boolean`                                                                        | No       | `true`       | Cast to boolean         |
| `remember_token`    | `varchar(100)`                                                                   | Yes      | NULL         |                         |
| `created_at`        | `timestamp`                                                                      | Yes      | NULL         |                         |
| `updated_at`        | `timestamp`                                                                      | Yes      | NULL         |                         |
| `deleted_at`        | `timestamp`                                                                      | Yes      | NULL         | SoftDeletes             |

**Security note:** `role` is excluded from `$fillable` to prevent mass-assignment privilege escalation. Assigned only via explicit `$user->role = UserRole::X`.

### `roles` Table

| Column            | Type                   | Nullable | Default | Notes                           |
| ----------------- | ---------------------- | -------- | ------- | ------------------------------- |
| `id`              | `bigint unsigned` (PK) | No       | AI      |                                 |
| `name`            | `varchar(255)` UNIQUE  | No       |         | Slug: `admin`, `customer`, etc. |
| `display_name`    | `varchar(255)`         | No       |         | English display                 |
| `display_name_ar` | `varchar(255)`         | No       |         | Arabic display                  |
| `description`     | `text`                 | Yes      | NULL    |                                 |
| `created_at`      | `timestamp`            | Yes      | NULL    |                                 |
| `updated_at`      | `timestamp`            | Yes      | NULL    |                                 |

**Collation:** `utf8mb4_unicode_ci` — No SoftDeletes.

### `permissions` Table

| Column         | Type                   | Nullable | Default | Notes                                 |
| -------------- | ---------------------- | -------- | ------- | ------------------------------------- |
| `id`           | `bigint unsigned` (PK) | No       | AI      |                                       |
| `name`         | `varchar(255)` UNIQUE  | No       |         | Dot notation: `projects.view`         |
| `display_name` | `varchar(255)`         | No       |         | Human-readable                        |
| `group`        | `varchar(255)`         | No       |         | Category: `projects`, `reports`, etc. |
| `description`  | `text`                 | Yes      | NULL    |                                       |
| `created_at`   | `timestamp`            | Yes      | NULL    |                                       |
| `updated_at`   | `timestamp`            | Yes      | NULL    |                                       |

**Collation:** `utf8mb4_unicode_ci` — No SoftDeletes.

### `role_user` Pivot Table

| Column       | Type                   | Nullable | Notes                          |
| ------------ | ---------------------- | -------- | ------------------------------ |
| `role_id`    | `bigint unsigned` (FK) | No       | → `roles.id` ON DELETE CASCADE |
| `user_id`    | `bigint unsigned` (FK) | No       | → `users.id` ON DELETE CASCADE |
| `created_at` | `timestamp`            | Yes      |                                |
| `updated_at` | `timestamp`            | Yes      |                                |

**Primary key:** Composite (`role_id`, `user_id`).

### `permission_role` Pivot Table

| Column          | Type                   | Nullable | Notes                                |
| --------------- | ---------------------- | -------- | ------------------------------------ |
| `permission_id` | `bigint unsigned` (FK) | No       | → `permissions.id` ON DELETE CASCADE |
| `role_id`       | `bigint unsigned` (FK) | No       | → `roles.id` ON DELETE CASCADE       |
| `created_at`    | `timestamp`            | Yes      |                                      |
| `updated_at`    | `timestamp`            | Yes      |                                      |

**Primary key:** Composite (`permission_id`, `role_id`).

---

## Eloquent Models & Relationships

### User Model (Existing — Modified)

```php
class User extends Authenticatable
{
    // Existing casts
    protected function casts(): array
    {
        return [
            'role' => UserRole::class,       // Enum cast
            'is_active' => 'boolean',
        ];
    }

    // Existing relationships
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    // Existing methods
    public function hasRole(string $roleSlug): bool;
    public function hasEnumRole(UserRole $role): bool;
    public function hasAnyRole(UserRole ...$roles): bool;

    // NEW — added by this stage
    public function hasPermission(string $permissionName): bool
    {
        return $this->roles
            ->flatMap(fn (Role $role) => $role->permissions)
            ->contains('name', $permissionName);
    }
}
```

### Role Model (Existing — No Changes)

```php
class Role extends Model
{
    protected $fillable = ['name', 'display_name', 'display_name_ar', 'description'];

    public function users(): BelongsToMany;      // → role_user pivot
    public function permissions(): BelongsToMany; // → permission_role pivot
}
```

### Permission Model (Existing — No Changes)

```php
class Permission extends Model
{
    protected $fillable = ['name', 'display_name', 'group', 'description'];

    public function roles(): BelongsToMany;       // → permission_role pivot
    public function scopeByGroup(Builder $query, string $group): Builder;
}
```

---

## Seed Data

### Roles (5 — Fixed, No CRUD)

| ID  | name                    | display_name          | display_name_ar  |
| --- | ----------------------- | --------------------- | ---------------- |
| 1   | `admin`                 | Administrator         | الإدارة          |
| 2   | `customer`              | Customer              | العميل           |
| 3   | `contractor`            | Contractor            | المقاول          |
| 4   | `supervising_architect` | Supervising Architect | المهندس المشرف   |
| 5   | `field_engineer`        | Field Engineer        | المهندس الميداني |

### Permissions (32 — 10 Groups)

| #   | name               | display_name     | group    |
| --- | ------------------ | ---------------- | -------- |
| 1   | `projects.view`    | View Projects    | projects |
| 2   | `projects.create`  | Create Projects  | projects |
| 3   | `projects.update`  | Update Projects  | projects |
| 4   | `projects.delete`  | Delete Projects  | projects |
| 5   | `phases.view`      | View Phases      | phases   |
| 6   | `phases.create`    | Create Phases    | phases   |
| 7   | `phases.update`    | Update Phases    | phases   |
| 8   | `phases.delete`    | Delete Phases    | phases   |
| 9   | `tasks.view`       | View Tasks       | tasks    |
| 10  | `tasks.create`     | Create Tasks     | tasks    |
| 11  | `tasks.update`     | Update Tasks     | tasks    |
| 12  | `tasks.delete`     | Delete Tasks     | tasks    |
| 13  | `reports.view`     | View Reports     | reports  |
| 14  | `reports.create`   | Create Reports   | reports  |
| 15  | `reports.approve`  | Approve Reports  | reports  |
| 16  | `users.view`       | View Users       | users    |
| 17  | `users.manage`     | Manage Users     | users    |
| 18  | `users.deactivate` | Deactivate Users | users    |
| 19  | `orders.view`      | View Orders      | orders   |
| 20  | `orders.create`    | Create Orders    | orders   |
| 21  | `orders.manage`    | Manage Orders    | orders   |
| 22  | `products.view`    | View Products    | products |
| 23  | `products.create`  | Create Products  | products |
| 24  | `products.update`  | Update Products  | products |
| 25  | `products.delete`  | Delete Products  | products |
| 26  | `payments.view`    | View Payments    | payments |
| 27  | `payments.process` | Process Payments | payments |
| 28  | `payments.refund`  | Refund Payments  | payments |
| 29  | `settings.view`    | View Settings    | settings |
| 30  | `settings.manage`  | Manage Settings  | settings |
| 31  | `roles.view`       | View Roles       | roles    |
| 32  | `roles.manage`     | Manage Roles     | roles    |

### Default Role-Permission Matrix

| Permission         | Customer | Contractor | Sup. Architect | Field Engineer | Admin |
| ------------------ | :------: | :--------: | :------------: | :------------: | :---: |
| `projects.view`    |    ✓     |     ✓      |       ✓        |       ✓        |   ✓   |
| `projects.create`  |    ✓     |            |                |                |   ✓   |
| `projects.update`  |          |     ✓      |       ✓        |                |   ✓   |
| `projects.delete`  |          |            |                |                |   ✓   |
| `phases.view`      |    ✓     |     ✓      |       ✓        |       ✓        |   ✓   |
| `phases.create`    |          |     ✓      |       ✓        |                |   ✓   |
| `phases.update`    |          |     ✓      |       ✓        |                |   ✓   |
| `phases.delete`    |          |            |                |                |   ✓   |
| `tasks.view`       |    ✓     |     ✓      |       ✓        |       ✓        |   ✓   |
| `tasks.create`     |          |     ✓      |       ✓        |       ✓        |   ✓   |
| `tasks.update`     |          |     ✓      |       ✓        |       ✓        |   ✓   |
| `tasks.delete`     |          |            |                |                |   ✓   |
| `reports.view`     |    ✓     |     ✓      |       ✓        |       ✓        |   ✓   |
| `reports.create`   |          |            |                |       ✓        |   ✓   |
| `reports.approve`  |          |            |       ✓        |                |   ✓   |
| `users.view`       |          |            |       ✓        |                |   ✓   |
| `users.manage`     |          |            |                |                |   ✓   |
| `users.deactivate` |          |            |                |                |   ✓   |
| `orders.view`      |    ✓     |     ✓      |                |                |   ✓   |
| `orders.create`    |    ✓     |            |                |                |   ✓   |
| `orders.manage`    |          |            |                |                |   ✓   |
| `products.view`    |    ✓     |     ✓      |                |                |   ✓   |
| `products.create`  |          |            |                |                |   ✓   |
| `products.update`  |          |            |                |                |   ✓   |
| `products.delete`  |          |            |                |                |   ✓   |
| `payments.view`    |    ✓     |     ✓      |                |                |   ✓   |
| `payments.process` |          |            |                |                |   ✓   |
| `payments.refund`  |          |            |                |                |   ✓   |
| `settings.view`    |          |            |                |                |   ✓   |
| `settings.manage`  |          |            |                |                |   ✓   |
| `roles.view`       |          |            |                |                |   ✓   |
| `roles.manage`     |          |            |                |                |   ✓   |

**Permission counts per role:**

| Role                  | Total Permissions |
| --------------------- | :---------------: |
| Admin                 |        32         |
| Customer              |        11         |
| Contractor            |        12         |
| Supervising Architect |        11         |
| Field Engineer        |         8         |

---

## Data Integrity Rules

### Constraint 1: Enum-Pivot Synchronization (NFR-007)

The `users.role` enum column and `role_user` pivot **MUST** be in sync at all times.

**Enforcement:**

- `RoleService::assignRoleToUser()` wraps both updates in `DB::transaction()`
- If either step fails, the entire operation rolls back
- No partial state is possible

**Invariant:** For any user U:

```
U.role == UserRole::X  ⟺  U.roles()->first()->name == 'x'
```

### Constraint 2: Single Role Per User

Each user has exactly one role at any time.

**Enforcement:**

- `users.role` is a single enum column (not an array)
- `RoleService::assignRoleToUser()` uses `sync()` (not `attach()`) on the pivot — replaces previous role

### Constraint 3: Role Immutability

The five roles are system-defined and cannot be created or deleted via API.

**Enforcement:**

- No create/delete role endpoints exist
- Only permission syncing is available via `PUT /admin/roles/{id}/permissions`
- `RoleSeeder` uses `updateOrCreate` keyed on `name` — canonical metadata

### Constraint 4: Admin Self-Lockout Prevention

An Admin cannot remove the Admin role from their own account.

**Enforcement:**

- `RoleService::assignRoleToUser()` checks `$targetUser->id === $performingAdmin->id`
- Throws `ValidationException` (not `RoleNotAllowedException` — this is a business rule, not authorization)

### Constraint 5: Cascade Deletes

- Deleting a user cascades to `role_user` pivot (foreign key on delete cascade)
- Deleting a role cascades to `role_user` and `permission_role` pivots
- Deleting a permission cascades to `permission_role` pivot

**Note:** Roles and permissions are never deleted in this stage (fixed set). Cascades are a safety net.

---

## Query Patterns

### Load User with Permissions (Per-Request Eager Load)

```php
// In middleware or controller
$user->load('roles.permissions');

// SQL generated:
// SELECT * FROM roles INNER JOIN role_user ON ... WHERE role_user.user_id = ?
// SELECT * FROM permissions INNER JOIN permission_role ON ... WHERE permission_role.role_id IN (?)
```

**Query count:** 2 (always — regardless of permission count).

### Check User Permission

```php
// Via User::hasPermission()
$user->roles->flatMap(fn ($role) => $role->permissions)->contains('name', 'projects.create');

// Requires roles.permissions already eager-loaded (no additional queries)
```

### List Permissions Grouped

```php
// Via PermissionRepository
Permission::all()->groupBy('group');

// Returns: ['projects' => [...], 'reports' => [...], ...]
```

### Role with Permission Count

```php
// For admin list
Role::withCount('permissions')->get();

// SQL: SELECT roles.*, (SELECT COUNT(*) FROM permission_role WHERE ...) as permissions_count FROM roles
```

---

## Frontend Data Types

### Updated `AuthUser` Interface

```typescript
export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: UserRoleType;
  phone: string;
  is_active: boolean;
  email_verified_at: string | null;
  created_at: string;
  avatar?: string;
  permissions: string[]; // NEW — e.g., ['projects.view', 'projects.create', ...]
}
```

### Role Type (Admin UI)

```typescript
export interface Role {
  id: number;
  name: string;
  display_name: string;
  display_name_ar: string;
  description: string;
  permissions_count: number;
  permissions?: Permission[];
}

export interface Permission {
  id: number;
  name: string;
  display_name: string;
  group: string;
  description?: string;
}

export type GroupedPermissions = Record<string, Permission[]>;
```
