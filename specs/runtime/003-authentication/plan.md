# Technical Plan — Authentication

> **Phase:** 01_PLATFORM_FOUNDATION
> **Based on:** `specs/runtime/003-authentication/spec.md` > **Created:** 2026-04-13T00:00:00Z

## Architecture Overview

This stage implements token-based API authentication using Laravel Sanctum. The architecture follows the existing layered pattern: **Routes → Middleware → Controller → Service → Repository → Model**. The frontend consumes the API via the existing `useApi` composable, stores auth state in the Pinia auth store (already scaffolded), and protects routes with Nuxt middleware.

**Key architectural decisions:**

- Pure API token auth (no SPA cookie auth)
- Token stored in `useCookie('auth_token')` for SSR compatibility (already implemented)
- Role set via explicit assignment, never mass-assigned (SEC-FINDING-A)
- Admin self-registration blocked at validation layer
- All responses follow `{ success, data, error }` contract via `ApiResponseTrait`
- Email verification uses signed URLs pointing to frontend, which calls the API

---

## Database Design

### New Tables

None. All required tables exist from prior stages:

- `users` — from STAGE_01 + profile columns from STAGE_02
- `personal_access_tokens` — from Sanctum setup in STAGE_02
- `password_reset_tokens` — from STAGE_01 (bundled in users migration)

### Modified Tables

| Table | Changes                    | Migration Name |
| ----- | -------------------------- | -------------- |
| —     | No schema changes required | —              |

### Model Changes

| Model  | Change                          | Rationale                         |
| ------ | ------------------------------- | --------------------------------- |
| `User` | Add `MustVerifyEmail` interface | Enable Laravel email verification |

### Eloquent Relationships

```
User --hasMany--> PersonalAccessToken (via Sanctum HasApiTokens) [already configured]
```

---

## API Design

### New Endpoints

| Method | Route                                   | Controller@Action                   | Middleware                                          | Description               |
| ------ | --------------------------------------- | ----------------------------------- | --------------------------------------------------- | ------------------------- |
| POST   | `/api/v1/auth/register`                 | `AuthController@register`           | `api`, `throttle:auth-register`                     | User registration         |
| POST   | `/api/v1/auth/login`                    | `AuthController@login`              | `api`, `throttle:auth-login`                        | User login                |
| POST   | `/api/v1/auth/logout`                   | `AuthController@logout`             | `api`, `auth:sanctum`                               | Revoke current token      |
| POST   | `/api/v1/auth/forgot-password`          | `AuthController@forgotPassword`     | `api`, `throttle:auth-forgot-password`              | Request password reset    |
| POST   | `/api/v1/auth/reset-password`           | `AuthController@resetPassword`      | `api`                                               | Reset password with token |
| GET    | `/api/v1/auth/user`                     | `AuthController@user`               | `api`, `auth:sanctum`                               | Get authenticated user    |
| PUT    | `/api/v1/auth/user`                     | `AuthController@updateProfile`      | `api`, `auth:sanctum`                               | Update name/phone         |
| GET    | `/api/v1/auth/email/verify/{id}/{hash}` | `AuthController@verifyEmail`        | `api`, `signed`                                     | Verify email (signed URL) |
| POST   | `/api/v1/auth/email/resend`             | `AuthController@resendVerification` | `api`, `auth:sanctum`, `throttle:auth-email-resend` | Resend verification email |

### Request/Response Contracts

#### POST `/api/v1/auth/register`

**Request:**

```json
{
  "name": "أحمد محمد",
  "email": "ahmad@example.com",
  "phone": "0512345678",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "role": "customer"
}
```

**Success Response (201):**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "أحمد محمد",
      "email": "ahmad@example.com",
      "phone": "0512345678",
      "role": "customer",
      "is_active": true,
      "email_verified_at": null,
      "created_at": "2026-04-12T00:00:00Z"
    },
    "token": "1|abc123...",
    "token_type": "Bearer"
  },
  "error": null
}
```

**Error (422):**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "بيانات غير صالحة",
    "details": { "email": ["البريد الإلكتروني مسجل مسبقاً"] }
  }
}
```

**Error (409) — Duplicate Email:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "CONFLICT_ERROR",
    "message": "البريد الإلكتروني مسجل مسبقاً",
    "details": { "email": ["البريد الإلكتروني مسجل مسبقاً"] }
  }
}
```

#### POST `/api/v1/auth/login`

**Request:**

```json
{
  "email": "ahmad@example.com",
  "password": "SecurePass123!"
}
```

**Success Response (200):**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "...",
      "email": "...",
      "phone": "...",
      "role": "customer",
      "is_active": true,
      "email_verified_at": "...",
      "created_at": "..."
    },
    "token": "2|def456...",
    "token_type": "Bearer"
  },
  "error": null
}
```

**Error (401):**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "AUTH_INVALID_CREDENTIALS",
    "message": "بيانات الدخول غير صحيحة",
    "details": null
  }
}
```

**Error (403) — Inactive user:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "AUTH_UNAUTHORIZED",
    "message": "الحساب غير مفعل",
    "details": null
  }
}
```

#### POST `/api/v1/auth/logout`

**Headers:** `Authorization: Bearer {token}`

**Success (200):**

```json
{
  "success": true,
  "data": { "message": "تم تسجيل الخروج بنجاح" },
  "error": null
}
```

#### POST `/api/v1/auth/forgot-password`

**Request:** `{ "email": "ahmad@example.com" }`

**Success (200):**

```json
{
  "success": true,
  "data": { "message": "تم إرسال رابط إعادة تعيين كلمة المرور" },
  "error": null
}
```

**Note:** Always returns success regardless of email existence (no enumeration).

#### POST `/api/v1/auth/reset-password`

**Request:**

```json
{
  "email": "ahmad@example.com",
  "token": "reset-token",
  "password": "NewSecurePass123!",
  "password_confirmation": "NewSecurePass123!"
}
```

**Success (200):**

```json
{
  "success": true,
  "data": { "message": "تم إعادة تعيين كلمة المرور بنجاح" },
  "error": null
}
```

#### GET `/api/v1/auth/user`

**Headers:** `Authorization: Bearer {token}`

**Success (200):**

```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "...",
      "email": "...",
      "phone": "...",
      "role": "customer",
      "is_active": true,
      "email_verified_at": "...",
      "created_at": "..."
    }
  },
  "error": null
}
```

#### PUT `/api/v1/auth/user`

**Request:** `{ "name": "أحمد العلي", "phone": "0598765432" }`

**Success (200):** Same shape as GET `/api/v1/auth/user`.

#### GET `/api/v1/auth/email/verify/{id}/{hash}` (Signed URL)

**Success (200):**

```json
{
  "success": true,
  "data": { "message": "تم التحقق من البريد الإلكتروني بنجاح" },
  "error": null
}
```

#### POST `/api/v1/auth/email/resend`

**Headers:** `Authorization: Bearer {token}`

**Success (200):**

```json
{
  "success": true,
  "data": { "message": "تم إرسال رابط التحقق" },
  "error": null
}
```

---

## Service Layer Design

| Service       | Methods                                        | Dependencies                        |
| ------------- | ---------------------------------------------- | ----------------------------------- |
| `AuthService` | `register(array $data): array`                 | `UserRepository`, `User` model      |
|               | `login(array $credentials): array`             | `UserRepository`                    |
|               | `logout(User $user): void`                     | —                                   |
|               | `forgotPassword(string $email): void`          | `Password` facade                   |
|               | `resetPassword(array $data): void`             | `Password` facade, `UserRepository` |
|               | `getProfile(User $user): User`                 | —                                   |
|               | `updateProfile(User $user, array $data): User` | `UserRepository`                    |
|               | `verifyEmail(int $id, string $hash): void`     | `UserRepository`                    |
|               | `resendVerification(User $user): void`         | —                                   |

### AuthService Implementation Details

```
register(data):
  1. Create user via UserRepository (fillable fields)
  2. Explicitly set role: $user->role = UserRole::from($data['role'])
  3. Save user
  4. Send email verification notification
  5. Create Sanctum token
  6. Return { user: UserResource, token, token_type }

login(credentials):
  1. Find user by email via UserRepository
  2. Verify password hash
  3. Check is_active flag
  4. Create Sanctum token
  5. Return { user: UserResource, token, token_type }

logout(user):
  1. Delete current access token: $user->currentAccessToken()->delete()

forgotPassword(email):
  1. Call Password::sendResetLink(['email' => $email])
  2. Always return success (no email enumeration)

resetPassword(data):
  1. Call Password::reset() with callback
  2. In callback: hash password, save, revoke ALL tokens
  3. Send password changed notification

verifyEmail(id, hash):
  1. Find user by ID
  2. Verify hash matches sha1(user.email)
  3. Mark email as verified
  4. Return success

resendVerification(user):
  1. Check if already verified
  2. Send verification notification
```

---

## Backend File Manifest

### New Files

| File                                               | Type         | Purpose                                    |
| -------------------------------------------------- | ------------ | ------------------------------------------ |
| `app/Http/Controllers/Api/AuthController.php`      | Controller   | Thin controller — delegates to AuthService |
| `app/Services/AuthService.php`                     | Service      | Business logic for all auth operations     |
| `app/Http/Requests/Auth/RegisterRequest.php`       | Form Request | Validates registration input               |
| `app/Http/Requests/Auth/LoginRequest.php`          | Form Request | Validates login input                      |
| `app/Http/Requests/Auth/ForgotPasswordRequest.php` | Form Request | Validates forgot password input            |
| `app/Http/Requests/Auth/ResetPasswordRequest.php`  | Form Request | Validates reset password input             |
| `app/Http/Requests/Auth/UpdateProfileRequest.php`  | Form Request | Validates profile update input             |
| `app/Http/Resources/UserResource.php`              | API Resource | Formats user model for API response        |
| `tests/Feature/Auth/RegisterTest.php`              | Test         | Registration endpoint tests                |
| `tests/Feature/Auth/LoginTest.php`                 | Test         | Login endpoint tests                       |
| `tests/Feature/Auth/LogoutTest.php`                | Test         | Logout endpoint tests                      |
| `tests/Feature/Auth/ForgotPasswordTest.php`        | Test         | Forgot password tests                      |
| `tests/Feature/Auth/ResetPasswordTest.php`         | Test         | Reset password tests                       |
| `tests/Feature/Auth/EmailVerificationTest.php`     | Test         | Email verification tests                   |
| `tests/Feature/Auth/ProfileTest.php`               | Test         | Get/update profile tests                   |
| `tests/Unit/Services/AuthServiceTest.php`          | Test         | AuthService unit tests                     |

### Modified Files

| File                                   | Changes                         |
| -------------------------------------- | ------------------------------- |
| `app/Models/User.php`                  | Add `MustVerifyEmail` interface |
| `routes/api.php`                       | Add auth route group            |
| `app/Providers/AppServiceProvider.php` | Add rate limiter definitions    |

---

## Form Request Validation Rules

### RegisterRequest

| Field      | Rules                                                                               |
| ---------- | ----------------------------------------------------------------------------------- |
| `name`     | `required`, `string`, `max:255`                                                     |
| `email`    | `required`, `string`, `email`, `max:255`, `unique:users`                            |
| `phone`    | `required`, `string`, `regex:/^(\+9665\|05)\d{8}$/`                                 |
| `password` | `required`, `string`, `min:8`, `confirmed`                                          |
| `role`     | `required`, `string`, `in:customer,contractor,supervising_architect,field_engineer` |

**Note:** `admin` is deliberately excluded from the `role` `in` rule.

### LoginRequest

| Field      | Rules                         |
| ---------- | ----------------------------- |
| `email`    | `required`, `string`, `email` |
| `password` | `required`, `string`          |

### ForgotPasswordRequest

| Field   | Rules                         |
| ------- | ----------------------------- |
| `email` | `required`, `string`, `email` |

### ResetPasswordRequest

| Field      | Rules                                      |
| ---------- | ------------------------------------------ |
| `email`    | `required`, `string`, `email`              |
| `token`    | `required`, `string`                       |
| `password` | `required`, `string`, `min:8`, `confirmed` |

### UpdateProfileRequest

| Field   | Rules                                                            |
| ------- | ---------------------------------------------------------------- |
| `name`  | `sometimes`, `required`, `string`, `max:255`                     |
| `phone` | `sometimes`, `required`, `string`, `regex:/^(\+9665\|05)\d{8}$/` |

---

## UserResource Output

```php
[
    'id' => $this->id,
    'name' => $this->name,
    'email' => $this->email,
    'phone' => $this->phone,
    'role' => $this->role->value,
    'is_active' => $this->is_active,
    'email_verified_at' => $this->email_verified_at?->toISOString(),
    'created_at' => $this->created_at?->toISOString(),
]
```

---

## Frontend Design

### Pages

| Route                   | Page Component                       | Layout                  | Middleware |
| ----------------------- | ------------------------------------ | ----------------------- | ---------- |
| `/auth/login`           | `app/pages/auth/login.vue`           | `auth` (minimal layout) | `guest`    |
| `/auth/register`        | `app/pages/auth/register.vue`        | `auth`                  | `guest`    |
| `/auth/forgot-password` | `app/pages/auth/forgot-password.vue` | `auth`                  | `guest`    |
| `/auth/reset-password`  | `app/pages/auth/reset-password.vue`  | `auth`                  | `guest`    |
| `/auth/verify-email`    | `app/pages/auth/verify-email.vue`    | `auth`                  | `auth`     |
| `/dashboard`            | `app/pages/dashboard.vue`            | `default`               | `auth`     |

### Components

| Component      | Purpose                                                   | Location                           |
| -------------- | --------------------------------------------------------- | ---------------------------------- |
| `AuthCard.vue` | Reusable card wrapper for auth forms (logo, title, UCard) | `app/components/auth/AuthCard.vue` |

### State Management (Pinia)

| Store             | State                                      | New Actions       | New Getters |
| ----------------- | ------------------------------------------ | ----------------- | ----------- |
| `auth` (existing) | `token`, `user`, `isAuthenticated`, `role` | `setToken(token)` | `isLoading` |

### Composable Changes

| Composable           | Changes                                                                                                                        |
| -------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| `useAuth` (existing) | Add `login(email, password)`, `register(data)`, `forgotPassword(email)`, `resetPassword(data)`, `resendVerification()` actions |
| `useApi` (existing)  | No changes needed — fully functional                                                                                           |

### Type Changes

| Type       | Changes                                                            |
| ---------- | ------------------------------------------------------------------ |
| `AuthUser` | Add `phone`, `is_active`, `email_verified_at`, `created_at` fields |

### Validation Schemas (Zod)

| Schema                 | File                            | Fields                                                                                                          |
| ---------------------- | ------------------------------- | --------------------------------------------------------------------------------------------------------------- |
| `loginSchema`          | `app/config/validation/auth.ts` | email (email format), password (min 1)                                                                          |
| `registerSchema`       | `app/config/validation/auth.ts` | name (min 1, max 255), email, phone (Saudi regex), password (min 8), password_confirmation (match), role (enum) |
| `forgotPasswordSchema` | `app/config/validation/auth.ts` | email                                                                                                           |
| `resetPasswordSchema`  | `app/config/validation/auth.ts` | email, token, password (min 8), password_confirmation                                                           |

---

## Middleware Chain

### Backend

```
Request → CORS → CorrelationId → RateLimit → Auth (Sanctum) → Controller
```

- Public auth endpoints (register, login, forgot-password, reset-password): No `auth:sanctum`
- Protected endpoints (logout, user, email resend): `auth:sanctum`
- Email verify: `signed` middleware (validates URL signature)

### Frontend

```
Route Navigation → i18n middleware → auth/guest middleware → Page Component
```

- `auth.ts` middleware: Checks `isAuthenticated` in auth store → redirects to `/auth/login` if false
- `guest.ts` middleware: Checks `isAuthenticated` → redirects to `/dashboard` if true

---

## Error Handling

| Scenario                             | Error Code                 | HTTP Status | Message (AR)                                 |
| ------------------------------------ | -------------------------- | ----------- | -------------------------------------------- |
| Invalid registration input           | `VALIDATION_ERROR`         | 422         | بيانات غير صالحة                             |
| Duplicate email at registration      | `CONFLICT_ERROR`           | 409         | البريد الإلكتروني مسجل مسبقاً                |
| Wrong email/password at login        | `AUTH_INVALID_CREDENTIALS` | 401         | بيانات الدخول غير صحيحة                      |
| Inactive account at login            | `AUTH_UNAUTHORIZED`        | 403         | الحساب غير مفعل                              |
| Unauthenticated request              | `AUTH_TOKEN_EXPIRED`       | 401         | انتهت صلاحية الجلسة                          |
| Invalid/expired reset token          | `VALIDATION_ERROR`         | 422         | رمز إعادة التعيين غير صالح أو منتهي الصلاحية |
| Invalid email verification signature | `AUTH_UNAUTHORIZED`        | 403         | رابط التحقق غير صالح                         |
| Rate limit exceeded                  | `RATE_LIMIT_EXCEEDED`      | 429         | تجاوزت الحد المسموح من المحاولات             |

---

## Testing Strategy

| Layer         | Tool       | Coverage Target   | Count                                             |
| ------------- | ---------- | ----------------- | ------------------------------------------------- |
| Unit (PHP)    | PHPUnit    | 80%               | ~15 tests (AuthService)                           |
| Feature (PHP) | PHPUnit    | 100% of endpoints | ~35 tests (all endpoints × success + error cases) |
| Unit (JS)     | Vitest     | 80%               | ~10 tests (auth store, useAuth composable)        |
| E2E           | Playwright | Critical paths    | ~5 tests (register → login → logout flow)         |

### Feature Test Matrix

| Endpoint                             | Test Cases                                                                              |
| ------------------------------------ | --------------------------------------------------------------------------------------- |
| POST `/auth/register`                | Success (201), validation errors (422), duplicate email (409), admin role blocked (422) |
| POST `/auth/login`                   | Success (200), invalid credentials (401), inactive user (403), rate limited (429)       |
| POST `/auth/logout`                  | Success (200), unauthenticated (401)                                                    |
| POST `/auth/forgot-password`         | Existing email (200), non-existing email (200 same response), rate limited (429)        |
| POST `/auth/reset-password`          | Success (200), invalid token (422), expired token (422)                                 |
| GET `/auth/user`                     | Success (200), unauthenticated (401)                                                    |
| PUT `/auth/user`                     | Success (200), validation errors (422), unauthenticated (401)                           |
| GET `/auth/email/verify/{id}/{hash}` | Success (200), invalid signature (403), already verified (200 idempotent)               |
| POST `/auth/email/resend`            | Success (200), already verified (200), rate limited (429)                               |

---

## Rate Limiting Configuration

| Limiter Name           | Max Attempts | Decay (Minutes) | Key                   |
| ---------------------- | ------------ | --------------- | --------------------- |
| `auth-login`           | 5            | 1               | IP address            |
| `auth-register`        | 5            | 1               | IP address            |
| `auth-forgot-password` | 3            | 1               | IP + email            |
| `auth-email-resend`    | 3            | 1               | Authenticated user ID |

Defined in `AppServiceProvider::boot()` using `RateLimiter::for()`.

---

## Security Considerations

- [x] Role excluded from `$fillable` — set explicitly (SEC-FINDING-A)
- [ ] Admin role blocked from registration validation
- [ ] Password hashed via Laravel's `hashed` cast (bcrypt)
- [ ] Tokens revoked on password reset (all sessions invalidated)
- [ ] No email enumeration on forgot-password (always returns success)
- [ ] Rate limiting on login, forgot-password, email resend
- [ ] Signed URLs for email verification (tamper-proof)
- [ ] `password` and `remember_token` hidden from API responses
- [ ] Input sanitization via Form Requests (validated before reaching service)
- [ ] CORS properly configured for frontend domain
- [ ] Token stored in non-httpOnly cookie (accepted constraint for Bearer injection — RBAC is server-side)

---

## i18n / RTL Considerations

- [ ] All auth pages render full RTL layout with Arabic text
- [ ] Form labels, placeholders, validation messages use i18n keys
- [ ] Error messages from API displayed in current locale
- [ ] Role labels translated:
  - `customer` → العميل
  - `contractor` → المقاول
  - `supervising_architect` → المهندس المشرف
  - `field_engineer` → المهندس الميداني
- [ ] Success messages translated (login, register, password reset, etc.)
- [ ] Navigation links (forgot password, register, login) use locale-prefixed routes

### i18n Keys to Add

```json
{
  "auth": {
    "login": {
      "title": "تسجيل الدخول",
      "email": "البريد الإلكتروني",
      "password": "كلمة المرور",
      "submit": "تسجيل الدخول",
      "forgot_password": "نسيت كلمة المرور؟",
      "no_account": "ليس لديك حساب؟",
      "register_link": "إنشاء حساب"
    },
    "register": {
      "title": "إنشاء حساب جديد",
      "name": "الاسم الكامل",
      "email": "البريد الإلكتروني",
      "phone": "رقم الهاتف",
      "password": "كلمة المرور",
      "password_confirmation": "تأكيد كلمة المرور",
      "role": "نوع الحساب",
      "submit": "إنشاء حساب",
      "has_account": "لديك حساب بالفعل؟",
      "login_link": "تسجيل الدخول"
    },
    "forgot_password": {
      "title": "نسيت كلمة المرور",
      "description": "أدخل بريدك الإلكتروني وسنرسل لك رابط إعادة تعيين كلمة المرور",
      "email": "البريد الإلكتروني",
      "submit": "إرسال رابط إعادة التعيين",
      "back_to_login": "العودة لتسجيل الدخول",
      "success": "تم إرسال رابط إعادة تعيين كلمة المرور"
    },
    "reset_password": {
      "title": "إعادة تعيين كلمة المرور",
      "email": "البريد الإلكتروني",
      "password": "كلمة المرور الجديدة",
      "password_confirmation": "تأكيد كلمة المرور الجديدة",
      "submit": "إعادة تعيين كلمة المرور",
      "success": "تم إعادة تعيين كلمة المرور بنجاح"
    },
    "verify_email": {
      "title": "تأكيد البريد الإلكتروني",
      "description": "تم إرسال رابط التحقق إلى بريدك الإلكتروني",
      "resend": "إعادة إرسال رابط التحقق",
      "resend_success": "تم إرسال رابط التحقق"
    },
    "logout": {
      "success": "تم تسجيل الخروج بنجاح"
    },
    "roles": {
      "customer": "العميل",
      "contractor": "المقاول",
      "supervising_architect": "المهندس المشرف",
      "field_engineer": "المهندس الميداني"
    },
    "validation": {
      "email_required": "البريد الإلكتروني مطلوب",
      "email_invalid": "البريد الإلكتروني غير صالح",
      "password_required": "كلمة المرور مطلوبة",
      "password_min": "كلمة المرور يجب أن تكون 8 أحرف على الأقل",
      "password_confirmation": "كلمة المرور غير متطابقة",
      "name_required": "الاسم مطلوب",
      "phone_required": "رقم الهاتف مطلوب",
      "phone_invalid": "رقم الهاتف غير صالح",
      "role_required": "نوع الحساب مطلوب"
    }
  }
}
```

---

## Risk Assessment

| Risk                                | Likelihood | Impact | Mitigation                                                  |
| ----------------------------------- | ---------- | ------ | ----------------------------------------------------------- |
| Token leakage via logging           | Low        | High   | Ensure tokens excluded from request logging middleware      |
| Email enumeration via timing        | Low        | Medium | Constant-time response on forgot-password (Laravel default) |
| Rate limit bypass via IP spoofing   | Low        | Medium | Trust `X-Forwarded-For` only from known proxies             |
| Existing logout uses DELETE method  | Medium     | Low    | Align to POST per spec during implementation                |
| Auth store race condition on SSR    | Low        | Medium | Guard with `callOnce` or `useState` for SSR hydration       |
| Missing `MustVerifyEmail` interface | Certain    | High   | Add to User model as first task                             |

---

## Implementation Order

1. **Backend Model Changes** — Add `MustVerifyEmail` to User model
2. **Backend Service Layer** — Create `AuthService` with all business logic
3. **Backend Form Requests** — Create all 5 form request validators
4. **Backend API Resource** — Create `UserResource`
5. **Backend Controller** — Create `AuthController` (thin, delegates to service)
6. **Backend Routes** — Add auth routes with middleware + rate limiters
7. **Backend Rate Limiting** — Register named rate limiters in `AppServiceProvider`
8. **Backend Tests** — Feature tests for all endpoints
9. **Frontend Types** — Extend `AuthUser` type
10. **Frontend Auth Store** — Add `setToken()`, `isLoading` to existing store
11. **Frontend useAuth Composable** — Add `login()`, `register()`, `forgotPassword()`, `resetPassword()`, `resendVerification()`
12. **Frontend Validation Schemas** — Zod schemas for all forms
13. **Frontend Auth Layout** — Create minimal auth layout
14. **Frontend Middleware** — Create `auth.ts` and `guest.ts` route middleware
15. **Frontend Pages** — Login, register, forgot-password, reset-password, verify-email
16. **Frontend i18n** — Add auth translation keys to `ar.json` and `en.json`
17. **Frontend Tests** — Vitest for auth store and composable
18. **Integration Testing** — End-to-end auth flow validation

---

## Dependencies Graph

```
User Model (MustVerifyEmail)
  └── UserRepository (findByEmail — already exists)
       └── AuthService (all business logic)
            └── AuthController (thin wrapper)
                 └── Routes (api.php)
                      └── Rate Limiters (AppServiceProvider)

UserResource ← AuthController (formats responses)
Form Requests ← AuthController (validates input)

Frontend:
  AuthUser type ← Auth Store ← useAuth Composable ← Auth Pages
  Zod Schemas ← Auth Pages (form validation)
  Auth/Guest Middleware ← Auth Pages (route protection)
  i18n Keys ← Auth Pages (labels/messages)
```
