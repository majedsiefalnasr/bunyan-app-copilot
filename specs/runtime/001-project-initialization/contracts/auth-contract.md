# API Contract: Authentication

> **Phase:** 01_PLATFORM_FOUNDATION  
> **Purpose:** Define API contract for authentication endpoints  
> **Created:** 2026-04-10T00:00:00Z

---

## Overview

This document specifies the authentication API contract for STAGE_01. All endpoints follow the standard error response format (see `error-contract.md`).

**Base URL:** `http://localhost:8000/api/v1` (development)  
**Protocol:** HTTP/HTTPS with JSON bodies  
**Authentication:** Bearer token (Sanctum) for protected endpoints

---

## POST /api/v1/auth/register

**Purpose:** Create a new user account

**RBAC:** Public (no authentication required)

### Request

```http
POST /api/v1/auth/register HTTP/1.1
Host: localhost:8000
Content-Type: application/json

{
  "name": "محمد علي",
  "email": "user@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}
```

**Request Fields:**

| Field                   | Type   | Required | Constraints                         | Example            |
| ----------------------- | ------ | -------- | ----------------------------------- | ------------------ |
| `name`                  | string | Yes      | Max 255 characters, supports Arabic | "محمد علي"         |
| `email`                 | string | Yes      | Valid email, must be unique         | "user@example.com" |
| `password`              | string | Yes      | Min 8 chars, uppercase + digit      | "SecurePass123!"   |
| `password_confirmation` | string | Yes      | Must match `password`               | "SecurePass123!"   |

### Response (Success)

```http
HTTP/1.1 201 Created
Content-Type: application/json

{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "محمد علي",
      "email": "user@example.com",
      "role": "customer",
      "created_at": "2026-04-10T10:30:00Z",
      "updated_at": "2026-04-10T10:30:00Z"
    },
    "token": "1|UgF6K7Jx8nL3Qz9pM2vB5aS8tR4yW1xH"
  },
  "message": "User registered successfully",
  "errors": {}
}
```

**Response Fields:**

| Field        | Type        | Description                      |
| ------------ | ----------- | -------------------------------- |
| `success`    | boolean     | Always `true` on success         |
| `data.user`  | User object | Newly created user               |
| `data.token` | string      | Bearer token for future requests |
| `message`    | string      | Human-readable success message   |
| `errors`     | object      | Empty on success                 |

### Response (Validation Error)

```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/json

{
  "success": false,
  "data": null,
  "message": "Validation failed",
  "errors": {
    "email": [
      "The email has already been taken."
    ],
    "password": [
      "The password must contain at least one uppercase letter.",
      "The password must contain at least one digit."
    ]
  }
}
```

### Response (Server Error)

```http
HTTP/1.1 500 Internal Server Error
Content-Type: application/json

{
  "success": false,
  "data": null,
  "message": "An unexpected error occurred. Please try again later.",
  "errors": {
    "server": ["Database connection failed"]
  }
}
```

---

## POST /api/v1/auth/login

**Purpose:** Authenticate user and obtain access token

**RBAC:** Public (no authentication required)

### Request

```http
POST /api/v1/auth/login HTTP/1.1
Host: localhost:8000
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "SecurePass123!"
}
```

**Request Fields:**

| Field      | Type   | Required | Description                              |
| ---------- | ------ | -------- | ---------------------------------------- |
| `email`    | string | Yes      | Registered user email                    |
| `password` | string | Yes      | Account password (plaintext, HTTPS only) |

### Response (Success)

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "محمد علي",
      "email": "user@example.com",
      "role": "customer",
      "created_at": "2026-04-10T10:30:00Z",
      "updated_at": "2026-04-10T10:30:00Z"
    },
    "token": "2|UgF6K7Jx8nL3Qz9pM2vB5aS8tR4yW1xH"
  },
  "message": "Login successful",
  "errors": {}
}
```

### Response (Invalid Credentials)

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json

{
  "success": false,
  "data": null,
  "message": "Invalid credentials",
  "errors": {
    "auth": ["Email or password is incorrect."]
  }
}
```

### Response (Validation Error)

```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/json

{
  "success": false,
  "data": null,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

---

## POST /api/v1/auth/logout

**Purpose:** Revoke access token and end session

**RBAC:** Authenticated (requires valid Bearer token)

### Request

```http
POST /api/v1/auth/logout HTTP/1.1
Host: localhost:8000
Authorization: Bearer 2|UgF6K7Jx8nL3Qz9pM2vB5aS8tR4yW1xH
Content-Type: application/json

{}
```

**Headers Required:**

| Header          | Value              | Required |
| --------------- | ------------------ | -------- |
| `Authorization` | `Bearer {token}`   | Yes      |
| `Content-Type`  | `application/json` | Yes      |

### Response (Success)

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "data": null,
  "message": "Logged out successfully",
  "errors": {}
}
```

### Response (Unauthorized)

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json

{
  "success": false,
  "data": null,
  "message": "Unauthenticated",
  "errors": {
    "auth": ["Token invalid or expired."]
  }
}
```

---

## GET /api/v1/me

**Purpose:** Retrieve current authenticated user

**RBAC:** Authenticated (requires valid Bearer token)

### Request

```http
GET /api/v1/me HTTP/1.1
Host: localhost:8000
Authorization: Bearer 2|UgF6K7Jx8nL3Qz9pM2vB5aS8tR4yW1xH
Accept: application/json
```

### Response (Success)

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "success": true,
  "data": {
    "id": 1,
    "name": "محمد علي",
    "email": "user@example.com",
    "role": "customer",
    "created_at": "2026-04-10T10:30:00Z",
    "updated_at": "2026-04-10T10:30:00Z"
  },
  "message": "Current user retrieved",
  "errors": {}
}
```

### Response (Unauthorized)

```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json

{
  "success": false,
  "data": null,
  "message": "Unauthenticated",
  "errors": {
    "auth": ["Token invalid or expired."]
  }
}
```

---

## Authentication Flow Diagram

```
┌──────────────────┐
│    Client        │
│   (Browser)      │
└────────┬─────────┘
         │
         │ 1. POST /auth/register
         │ {name, email, password}
         ▼
┌──────────────────────────────────────┐
│     Laravel Backend                  │
│  (FormRequest validation)             │
│  (Hash password, create User)         │
│  (Generate Sanctum token)             │
└────────┬─────────────────────────────┘
         │
         │ 2. Return {user, token}
         ▼
┌──────────────────┐
│    Client        │
│ (localStorage)   │
│  stores token    │
└────────┬─────────┘
         │
         │ 3. All future requests:
         │    Authorization: Bearer {token}
         ▼
┌──────────────────────────────────────┐
│     Laravel Backend                  │
│  (Sanctum middleware: verify token)  │
│  (RBAC middleware: check user role)  │
│  (execute business logic)            │
└────────┬─────────────────────────────┘
         │
         │ 4. Return response
         ▼
┌──────────────────┐
│    Client        │
│ (Process data)   │
└──────────────────┘
```

---

## Token Management

### Token Storage (Frontend)

**Recommended (localStorage):**

```javascript
// After login
localStorage.setItem('token', response.data.token);

// Before making API requests
const token = localStorage.getItem('token');
fetch('/api/v1/me', {
  headers: {
    Authorization: `Bearer ${token}`,
    Accept: 'application/json',
  },
});

// On logout
localStorage.removeItem('token');
```

**Note:** For production, consider:

- `sessionStorage` for shorter-lived tokens
- Secure cookies (HttpOnly, SameSite) for token storage
- Refresh token rotation

### Token Expiration

In STAGE_01, tokens do not expire by default (Sanctum allows indefinite tokens).

**Future enhancement (STAGE_03+):**

```php
$token = $user->createToken('auth', ['*'], now()->addHours(24));
```

### Token Format

Sanctum tokens are formatted as: `{ID}|{HASH}`

Example: `1|UgF6K7Jx8nL3Qz9pM2vB5aS8tR4yW1xH`

- Part 1 (ID): Database record ID from `personal_access_tokens` table
- Part 2 (HASH): Hashed value stored in token column

---

## Error Codes

**Authentication-specific errors:**

| HTTP Status | Error Code          | Message             | Example                                  |
| ----------- | ------------------- | ------------------- | ---------------------------------------- |
| 401         | `AUTH_INVALID`      | Invalid credentials | "Email or password is incorrect"         |
| 401         | `AUTH_EXPIRED`      | Token expired       | "Token has expired"                      |
| 401         | `AUTH_MISSING`      | Missing token       | "Authorization header missing"           |
| 403         | `RBAC_DENIED`       | Insufficient role   | "This action requires admin role"        |
| 422         | `VALIDATION_FAILED` | Validation error    | Field-specific errors in `errors` object |

**See `error-contract.md` for full error handling contract.**

---

## Rate Limiting

**Not implemented in STAGE_01** — deferred to STAGE_05+

**Future configuration (STAGE_05):**

```php
// Limit login attempts to 5 per minute per IP
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

// Limit registration to 3 per hour per IP
Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:3,60');
```

---

## CORS Configuration

**CORS Headers (required for frontend at `http://localhost:3000`):**

```php
// config/cors.php
'allowed_origins' => ['http://localhost:3000'],
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'exposed_headers' => ['Authorization'],
'max_age' => 0,
'supports_credentials' => true,
```

**Client must include credentials:**

```javascript
fetch('/api/v1/auth/login', {
  method: 'POST',
  credentials: 'include', // Include cookies if using cookie-based auth
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  body: JSON.stringify({ email, password }),
});
```

---

## Testing Examples

### cURL

```bash
# Register
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "TestPass123",
    "password_confirmation": "TestPass123"
  }'

# Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "TestPass123"
  }'

# Get current user (replace TOKEN with actual token)
curl -X GET http://localhost:8000/api/v1/me \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json"

# Logout
curl -X POST http://localhost:8000/api/v1/auth/logout \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json"
```

### Postman

**Environment Variables:**

```
base_url = http://localhost:8000
token = (auto-set after login)
```

**Collection:**

```json
{
  "info": { "name": "Authentication" },
  "item": [
    {
      "name": "Register",
      "request": {
        "method": "POST",
        "url": "{{base_url}}/api/v1/auth/register",
        "body": {
          "name": "Test User",
          "email": "test@example.com",
          "password": "TestPass123",
          "password_confirmation": "TestPass123"
        }
      },
      "event": [
        {
          "listen": "test",
          "script": "pm.environment.set('token', pm.response.json().data.token);"
        }
      ]
    }
  ]
}
```

---

## Versioning

**API Version:** v1 (in URL: `/api/v1/`)

**Breaking Changes Policy:**

- New endpoints → new version or flag parameter
- Removing endpoints → major version bump
- Adding optional fields → no version bump
- Changing response structure → major version bump

**Future versions:** `/api/v2/`, `/api/v3/`, etc.
