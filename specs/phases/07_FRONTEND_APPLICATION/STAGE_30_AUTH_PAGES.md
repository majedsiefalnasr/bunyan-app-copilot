# STAGE_30 — Auth Pages

> **Phase:** 07_FRONTEND_APPLICATION
> **Status:** NOT STARTED
> **Scope:** Login, register, forgot password, email verification pages
> **Risk Level:** LOW

## Stage Status

Status: IN PROGRESS
Step: analyze
Risk Level: CRITICAL
Last Updated: 2026-04-13T00:00:00Z

Drift Analysis: ❌ BLOCKED

Blockers:

- 🚫 Security Auditor: 10 critical gaps (rate limiting, avatar upload, password reset, account lockout, session management, OTP)
- 🚫 Performance Optimizer: 3 critical gaps (auto-refresh queue, password strength debounce, districts caching)

Implementation: FORBIDDEN — Gates require remediation

Scope:

- 58 tasks defined across 10 phases
- Specification complete, clarifications locked
- Technical plan created (plan.md, research.md, data-model.md)

Architecture Governance Compliance:

- Drift audit: PASS
- Security review: BLOCKED (10 gaps)
- Performance review: BLOCKED (3 gaps)

Remediation Effort:

- Security fixes: 4-6 hours
- Performance fixes: 2 hours
- Re-audit: 30 mins
- Total: 6-8 hours

Notes:
Analysis gate BLOCKED due to security and performance vulnerabilities. See audits/ANALYZE_REPORT.md for detailed remediation checklist. DO NOT PROCEED to implementation until all blockers resolved and gates re-validated.

## Objective

Implement all authentication-related frontend pages with form validation and RTL support using **Nuxt UI** components.

## Scope

### Frontend Pages

| Page               | Route                 | Description                                                 |
| ------------------ | --------------------- | ----------------------------------------------------------- |
| Login              | /auth/login           | Email + password, remember me, social login buttons         |
| Register           | /auth/register        | Multi-step: account type → personal info → contact → verify |
| Forgot Password    | /auth/forgot-password | Email input, send reset link                                |
| Reset Password     | /auth/reset-password  | New password form (from email link)                         |
| Email Verification | /auth/verify-email    | Verification confirmation page                              |
| Profile            | /profile              | User profile edit page                                      |

### Nuxt UI Component Map

| Form Element         | Nuxt UI Component                      |
| -------------------- | -------------------------------------- |
| Login form container | `UCard` inside auth layout             |
| Email input          | `UFormField` + `UInput` (type="email") |
| Password input       | `UInput` (type="password") + show/hide |
| Submit button        | `UButton` (block, loading state)       |
| Multi-step wizard    | `USteppers` + `UCard` per step         |
| Role selector        | `URadioGroup`                          |
| OTP input            | `UPinInput`                            |
| Password strength    | `UProgress`                            |
| Alerts / errors      | `UAlert` (color="error")               |
| Success states       | `UAlert` (color="success")             |
| "Remember me" toggle | `UCheckbox`                            |

### Form Validation

All forms use **VeeValidate** + **Zod** schemas:

```typescript
// schemas/auth.ts
import { z } from 'zod';
export const loginSchema = z.object({
  email: z.string().email({ message: 'البريد الإلكتروني غير صالح' }),
  password: z.string().min(8, { message: 'كلمة المرور قصيرة جدًا' }),
});
```

### Components

- `AuthCard` — Shared card wrapper for all auth pages
- `PasswordStrength` — `UProgress`-based strength indicator
- `RoleSelector` — `URadioGroup` for account type selection
- `OtpInput` — `UPinInput` wrapper (optional 2FA)

## Testing

### Unit Tests (Vitest)

- Zod schemas — email format, password min-length, confirm-password match
- `useAuthStore` — login action, token storage, logout cleanup

### E2E Tests (Playwright)

| Test Case                         | Scenario                                                |
| --------------------------------- | ------------------------------------------------------- |
| Login success (valid credentials) | Fill form → submit → redirect to dashboard              |
| Login failure (wrong password)    | Fill wrong password → error `UAlert` visible            |
| Register multi-step flow          | Complete all 4 steps → verify email redirect            |
| Forgot password email sent        | Enter email → success toast → check email message       |
| Reset password token expired      | Use expired token link → error page shown               |
| RTL form layout                   | All inputs right-aligned, error messages in Arabic      |
| Profile update                    | Edit name/phone → save → success toast → data persisted |

```typescript
// tests/e2e/auth.spec.ts
import { test, expect } from '@playwright/test';

test('login with valid credentials redirects to dashboard', async ({ page }) => {
  await page.goto('/auth/login');
  await page.fill('[data-testid="email-input"]', 'user@example.com');
  await page.fill('[data-testid="password-input"]', 'password123');
  await page.click('[data-testid="login-button"]');
  await expect(page).toHaveURL('/dashboard');
});

test('login shows Arabic error on bad credentials', async ({ page }) => {
  await page.goto('/auth/login');
  await page.fill('[data-testid="email-input"]', 'wrong@example.com');
  await page.fill('[data-testid="password-input"]', 'wrongpassword');
  await page.click('[data-testid="login-button"]');
  await expect(page.locator('[role="alert"]')).toBeVisible();
});
```

## Dependencies

- **Upstream:** STAGE_03_AUTHENTICATION, STAGE_29_NUXT_SHELL
- **Downstream:** All authenticated pages
