# API Integration Contract: STAGE_29 - Nuxt Shell

> **Phase:** 07_FRONTEND_APPLICATION
> **Generated:** 2026-04-12

---

## Overview

Defines API contracts consumed by the Nuxt Shell stage. All requests use `useApi().apiFetch` which automatically injects `Authorization: Bearer {token}` and `Accept-Language: {locale}` headers.

Base URL: `process.env.NUXT_PUBLIC_API_BASE_URL` (default: `http://localhost:8000`)
All routes under: `/api/v1/`

---

## 1. GET /api/v1/auth/me

**Purpose:** Fetch the currently authenticated user profile. Called by `useAuth().fetchCurrentUser()` on app boot when `auth_token` cookie exists.

### Request

```http
GET /api/v1/auth/me
Authorization: Bearer {token}
Accept: application/json
Accept-Language: ar
```

### Success Response — 200 OK

```json
{
  "success": true,
  "data": {
    "id": 42,
    "name": "Ahmed Al-Amri",
    "email": "ahmed@example.com",
    "role": "contractor",
    "avatar": "https://cdn.example.com/avatars/42.jpg",
    "email_verified_at": "2026-01-15T10:30:00Z",
    "created_at": "2026-01-01T00:00:00Z"
  },
  "error": null
}
```

**Field mapping to `AuthUser` interface:**

| Field               | Type             | Notes                                                                  |
| ------------------- | ---------------- | ---------------------------------------------------------------------- |
| `id`                | `number`         | Unique user ID                                                         |
| `name`              | `string`         | Display name                                                           |
| `email`             | `string`         | Account email                                                          |
| `role`              | `UserRoleType`   | customer / contractor / supervising_architect / field_engineer / admin |
| `avatar`            | `string or null` | Full absolute URL or null                                              |
| `email_verified_at` | `string or null` | ISO 8601, null if unverified                                           |
| `created_at`        | `string`         | ISO 8601 creation timestamp                                            |

### Error Responses

| Status | error.code         | When                                  |
| ------ | ------------------ | ------------------------------------- |
| 401    | AUTH_TOKEN_EXPIRED | Token expired or not found            |
| 403    | AUTH_UNAUTHORIZED  | Token valid but user lacks permission |
| 500    | SERVER_ERROR       | Internal server error                 |

### Frontend Handling

```typescript
// In useAuth().fetchCurrentUser()
const response = await api.apiFetch('/api/v1/auth/me');
if (response.success && response.data) {
  store.setUser(response.data, useCookie('auth_token').value!);
}
```

---

## 2. GET /api/v1/user

**Purpose:** Alternative profile endpoint. Functionally identical to `/api/v1/auth/me`. Prefer `/api/v1/auth/me` for session bootstrapping. `/api/v1/user` may be used for per-page profile refreshes.

**Request and response shape are identical to `/api/v1/auth/me`.**

---

## 3. DELETE /api/v1/auth/logout

**Purpose:** Invalidate the current Sanctum token server-side. Called by `useAuth().logout()`.

### Request

```http
DELETE /api/v1/auth/logout
Authorization: Bearer {token}
Accept: application/json
Accept-Language: ar
```

No request body.

### Success Response — 200 OK

```json
{
  "success": true,
  "data": {
    "message": "Logged out successfully."
  },
  "error": null
}
```

### Error Responses

| Status | error.code         | When                                   |
| ------ | ------------------ | -------------------------------------- |
| 401    | AUTH_TOKEN_EXPIRED | Token already expired or invalid       |
| 500    | SERVER_ERROR       | Internal error during token revocation |

### Frontend Handling — Always Clear Local State

```typescript
async function logout() {
  try {
    await api.apiFetch('/api/v1/auth/logout', { method: 'DELETE' });
  } catch {
    // Swallowed — local cleanup must happen regardless of API result
  } finally {
    store.clearAuth();
    await navigateTo(`/${locale.value}/auth/login`);
  }
}
```

**Rule:** If DELETE fails for any reason, frontend MUST still clear cookie, clear Pinia state, and navigate to login. Users must never be stuck in a broken auth state.

---

## 4. Authentication Header Format

```
Authorization: Bearer {sanctum_token}
```

**Token storage:**

- **Cookie**: `auth_token` (client-accessible, persisted across sessions)
- **Pinia**: `useAuthStore().token` (in-memory reactive)

The `useApi()` composable injects the Authorization header automatically via `onRequest` interceptor (already implemented in `composables/useApi.ts`).

---

## 5. Accept-Language Header

```
Accept-Language: ar
```

Set automatically per request by `useApi()`. Value is the current `useI18n().locale.value`.

---

## 6. Standard Error Response Envelope

All API errors use the unified contract from AGENTS.md:

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "ERROR_CODE",
    "message": "User-friendly localized message",
    "details": { "field": ["error message"] }
  }
}
```

Handled globally by `useErrorHandler.ts` + `useApi.ts onResponseError` interceptor. No shell-specific error handling is required.

---

## 7. TypeScript Response Types

Add to `frontend/types/index.ts` in Phase 0:

```typescript
export interface ApiErrorBody {
  code: string;
  message: string;
  details: Record<string, string[]> | null;
}

export interface ApiResponse<T = null> {
  success: boolean;
  data: T | null;
  error: ApiErrorBody | null;
}

export type GetMeResponse = ApiResponse<AuthUser>;
export type LogoutResponse = ApiResponse<{ message: string }>;
```

---

## 8. Security Notes

- Tokens are Sanctum Personal Access Tokens
- `auth_token` cookie is NOT httpOnly — intentionally client-readable for the SPA
- Production: cookie MUST use `Secure` flag (HTTPS only) and `SameSite=Lax`
- Server-side token revocation happens on logout — client must also independently clear the cookie
- Do NOT store token in `localStorage` — the cookie-based approach is already established in `useApi.ts`
