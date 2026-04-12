# Data Model — Authentication

> **Stage:** 003-authentication
> **Phase:** 01_PLATFORM_FOUNDATION
> **Created:** 2026-04-13T00:00:00Z

## Purpose

Document the data model for the authentication stage — existing tables used and any schema changes required.

---

## Existing Tables (No Changes Required)

### `users` table

**Migration:** `0001_01_01_000000_create_users_table.php`
**Modified by:** `2026_04_11_000001_add_profile_columns_to_users_table.php`

| Column            | Type                                                                           | Nullable | Default        | Notes                                 |
| ----------------- | ------------------------------------------------------------------------------ | -------- | -------------- | ------------------------------------- |
| id                | bigint unsigned (PK)                                                           | No       | auto-increment |                                       |
| name              | varchar(255)                                                                   | No       | —              |                                       |
| email             | varchar(255)                                                                   | No       | —              | Unique index                          |
| email_verified_at | timestamp                                                                      | Yes      | null           | Set by verification flow              |
| password          | varchar(255)                                                                   | No       | —              | Bcrypt hashed via `hashed` cast       |
| phone             | varchar(20)                                                                    | Yes      | null           | Saudi format: `/^(\+9665\|05)\d{8}$/` |
| role              | enum('customer','contractor','supervising_architect','field_engineer','admin') | No       | —              | `UserRole` enum cast                  |
| is_active         | boolean                                                                        | No       | true           | Account active flag                   |
| avatar            | varchar(255)                                                                   | Yes      | null           | Future — not used in this stage       |
| remember_token    | varchar(100)                                                                   | Yes      | null           | Hidden from API responses             |
| deleted_at        | timestamp                                                                      | Yes      | null           | SoftDeletes                           |
| created_at        | timestamp                                                                      | Yes      | null           |                                       |
| updated_at        | timestamp                                                                      | Yes      | null           |                                       |

**Eloquent Model:** `App\Models\User`

Key traits: `HasApiTokens`, `HasFactory`, `Notifiable`, `SoftDeletes`

Key casts:

- `email_verified_at` → `datetime`
- `password` → `hashed`
- `role` → `UserRole::class`
- `is_active` → `boolean`

Hidden attributes: `password`, `remember_token`

Fillable: `name`, `email`, `password`, `phone`, `is_active`, `avatar`

**Note:** `role` is intentionally excluded from `$fillable` to prevent privilege escalation (SEC-FINDING-A). Role must be set via explicit assignment: `$user->role = UserRole::from($validated['role'])`.

---

### `personal_access_tokens` table

**Migration:** `2026_04_10_155208_create_personal_access_tokens_table.php`

| Column         | Type                 | Nullable | Default        | Notes                                |
| -------------- | -------------------- | -------- | -------------- | ------------------------------------ |
| id             | bigint unsigned (PK) | No       | auto-increment |                                      |
| tokenable_type | varchar(255)         | No       | —              | Polymorphic type (`App\Models\User`) |
| tokenable_id   | bigint unsigned      | No       | —              | User ID                              |
| name           | varchar(255)         | No       | —              | Token name (e.g., `"api"`)           |
| token          | varchar(64)          | No       | —              | SHA-256 hash of plaintext token      |
| abilities      | text                 | Yes      | null           | JSON array of abilities              |
| last_used_at   | timestamp            | Yes      | null           |                                      |
| expires_at     | timestamp            | Yes      | null           |                                      |
| created_at     | timestamp            | Yes      | null           |                                      |
| updated_at     | timestamp            | Yes      | null           |                                      |

**Managed by:** Laravel Sanctum (`HasApiTokens` trait)

**Usage in this stage:**

- `$user->createToken('api')` — creates token on login/register
- `$user->currentAccessToken()->delete()` — revokes current token on logout
- `$user->tokens()->delete()` — revokes ALL tokens on password reset

---

### `password_reset_tokens` table

**Migration:** `0001_01_01_000000_create_users_table.php` (bundled)

| Column     | Type              | Nullable | Default | Notes                                       |
| ---------- | ----------------- | -------- | ------- | ------------------------------------------- |
| email      | varchar(255) (PK) | No       | —       | User's email                                |
| token      | varchar(255)      | No       | —       | Hashed reset token                          |
| created_at | timestamp         | Yes      | null    | Token creation time (used for expiry check) |

**Managed by:** Laravel's `Password` broker

**Usage in this stage:**

- `Password::sendResetLink(['email' => $email])` — creates reset token and sends email
- `Password::reset($credentials, $callback)` — validates token and resets password
- Token expiry: 60 minutes (configurable in `config/auth.php`)

---

## Existing Support Tables (Referenced but Not Modified)

### `roles` table

**Migration:** `2026_04_11_000002_create_roles_table.php`

The pivot-based RBAC system (`roles`, `permissions`, `role_user`, `permission_role`) exists from STAGE_02 but is **not used in this stage**. Authentication uses the `role` enum column on `users` directly. RBAC via pivot tables is deferred to the RBAC stage.

---

## New Tables

**None required.** All needed tables already exist from prior stages (STAGE_01 and STAGE_02).

---

## Eloquent Relationships (Relevant to Auth)

```
User --hasMany--> PersonalAccessToken (via Sanctum HasApiTokens)
User --belongsToMany--> Role (via role_user pivot) [not used in this stage]
```

---

## Model Changes Required

### `User` model additions

1. **Add `MustVerifyEmail` interface** — enables Laravel's built-in email verification.
2. **No new relationships** — all needed relationships already exist.
3. **No new scopes** — `scopeActive` already exists.

```php
// Change:
class User extends Authenticatable
// To:
class User extends Authenticatable implements MustVerifyEmail
```

---

## Frontend Type Changes

### `AuthUser` type expansion

The existing `AuthUser` interface in `frontend/types/index.ts` needs additional fields:

```typescript
// Current:
export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: UserRoleType;
  avatar?: string;
}

// Required by spec:
export interface AuthUser {
  id: number;
  name: string;
  email: string;
  phone: string;
  role: UserRoleType;
  is_active: boolean;
  email_verified_at: string | null;
  created_at: string;
  avatar?: string;
}
```
