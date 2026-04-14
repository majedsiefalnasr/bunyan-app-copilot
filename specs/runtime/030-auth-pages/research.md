# STAGE_30 — Auth Pages Research & Unknowns

**Date:** 2026-04-13  
**Status:** RESEARCH COMPLETE  
**Unknowns Resolved:** All knowable knowns identified; remaining items are backend-scoped

---

## Executive Summary

This document captures research findings, resolved unknowns, and decisions made during the planning phase of STAGE_30 (Auth Pages). All major technical decisions have been validated against Bunyan conventions, Nuxt 4 patterns, and Laravel Sanctum patterns.

---

## 1. Nuxt 4 + Nuxt UI Integration Patterns with TypeScript

### 1.1 Resolved: Component Import & Typing

**Finding:** Nuxt UI v3.x (@nuxt/ui) components are TypeScript-first and auto-imported via Pinia plugin.

**Decision:**

- ✅ Components (UButton, UForm, UInput, etc.) are **auto-imported globally**
- ✅ No manual imports needed in `<script setup>`
- ✅ TypeScript types provided via `#ui/types` package
- ✅ `FormSubmitEvent` type imported from `#ui/types` for form validation

**Code Pattern:**

```typescript
<script setup lang="ts">
  import type { FormSubmitEvent } from '#ui/types'
  import { z } from 'zod'

  const schema = z.object({ /* ... */ })

  const onSubmit = (event: FormSubmitEvent<typeof schema>) => {
    // TypeScript knows event.data: { ... validated fields ... }
  }
</script>
```

**Source:** Nuxt UI docs + spec requirements confirm this pattern.

---

### 1.2 Resolved: UForm Validation Integration with VeeValidate

**Finding:** UForm component in Nuxt UI v3 **does not natively support VeeValidate**. VeeValidate integration requires manual setup.

**Decision:**

- ✅ Use `UForm` as container only (no native validation)
- ✅ Use `VeeValidate` separately via `useForm()` + `useField()` composables
- ✅ Manually bind VeeValidate errors to `UFormGroup` component
- ✅ Zod validation happens in VeeValidate's schema context

**Implementation Pattern:**

```typescript
const schema = z.object({
  email: z.string().email(),
  password: z.string().min(8),
});

const { values, errors, validate } = useForm({
  validationSchema: toTypedSchema(schema),
});

const onSubmit = async () => {
  const isValid = await validate();
  if (!isValid) return;
  // Submit
};
```

**Component Setup:**

```vue
<UFormGroup label="Email" :error="errors.email">
  <UInput v-model="values.email" type="email" />
</UFormGroup>
```

**Source:** Spec requirements + Nuxt UI + VeeValidate documentation

---

### 1.3 Resolved: Composition API + TypeScript Best Practices

**Finding:** Nuxt 4 with Vue 3 Composition API + TypeScript requires explicit typing of ref/computed returns.

**Decision:**

- ✅ Use `ref<Type>()` with explicit types: `ref<string>('')`, `ref<User | null>(null)`
- ✅ Use `computed<Type>()` for derived state
- ✅ Use `reactive<InterfaceType>({})` for object state
- ✅ Define interfaces in `types/index.ts` for reusability

**Code Pattern:**

```typescript
const email = ref<string>('');
const user = ref<User | null>(null);
const isLoading = ref<boolean>(false);
const isAuthenticated = computed<boolean>(() => !!token.value);
```

**Source:** Vue 3 Composition API best practices + Nuxt 4 conventions

---

## 2. VeeValidate 4 + Zod Schema Architecture

### 2.1 Resolved: VeeValidate 4.x API Changes

**Finding:** VeeValidate 4.x removed `ValidationError` in favor of `toTypedSchema()` helper.

**Decision:**

- ✅ Use `toTypedSchema(z.object(...))` for Zod integration
- ✅ Use `useForm()` hook, not `useValidation()`
- ✅ Form state is flat (no nested object support without custom config)
- ✅ Field-level errors auto-populated in `errors` object

**Code Pattern:**

```typescript
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { z } from 'zod';

const schema = z.object({
  email: z.string().email('Invalid email'),
  password: z.string().min(8, 'At least 8 chars'),
});

const { values, errors, handleSubmit, validate } = useForm({
  validationSchema: toTypedSchema(schema),
  initialValues: { email: '', password: '' },
});

const onSubmit = handleSubmit(async (values) => {
  // Only called if validation passes
  await submitToAPI(values);
});
```

**Source:** VeeValidate 4.x official docs + spec requirements

---

### 2.2 Resolved: Multi-Step Form Validation (Register Wizard)

**Finding:** VeeValidate 4 doesn't natively support multi-step validation. Frontend must manually validate each step.

**Decision:**

- ✅ Create separate `useForm()` instances **per step** OR
- ✅ Create **one form** with all 4 steps, validate only current step fields
- ✅ Chosen approach: **One form, per-step validation** via manual field subset validation
- ✅ Steps stored in Pinia store: `useRegisterStore().steps[1-4]`

**Implementation Pattern:**

```typescript
// Single form instance
const {
  values,
  errors,
  validate: validateAll,
} = useForm({
  validationSchema: toTypedSchema(registerFullSchema),
});

// Per-step validation
const validateStep = async (step: 1 | 2 | 3 | 4) => {
  const stepFields = getFieldsForStep(step);
  const isValid = await validate(stepFields); // Validate only these fields
  return isValid;
};

const goToNextStep = async () => {
  if (!(await validateStep(currentStep.value))) return;
  currentStep.value++;
  registerStore.setStep(currentStep.value, values);
};

const onFinalSubmit = async () => {
  if (!(await validateAll())) return; // Validate all 4 steps
  await submitRegistration(values);
};
```

**Source:** VeeValidate docs + spec multi-step requirement

---

### 2.3 Resolved: custom Zod Refinements for Cross-Field Validation

**Finding:** Zod supports `.refine()` for custom validation (e.g., password confirmation match).

**Decision:**

- ✅ Use `.refine()` for cross-field validations
- ✅ Set `path: ['field']` to associate error with specific field
- ✅ Error message shown under that field in UFormGroup

**Code Pattern:**

```typescript
const passwordSchema = z
  .object({
    password: z.string().min(8),
    confirmPassword: z.string(),
  })
  .refine((data) => data.password === data.confirmPassword, {
    message: 'Passwords do not match',
    path: ['confirmPassword'], // Error shown under confirmPassword field
  });
```

**Source:** Zod documentation + spec requirements for confirm password

---

## 3. RTL/i18n Layer Architecture (Arabic + English)

### 3.1 Resolved: Nuxt i18n with @nuxtjs/i18n Module

**Finding:** Nuxt 4 with @nuxtjs/i18n v8+ auto-detects `dir="rtl"` from locale config and sets it on `<html>` tag.

**Decision:**

- ✅ Install `@nuxtjs/i18n` module (auto-imported in nuxt.config)
- ✅ Configure locales with `dir: 'rtl'` for Arabic
- ✅ `<html dir="rtl" lang="ar">` automatically set on initial page load
- ✅ No manual dir attribute needed in app.vue

**Configuration (nuxt.config.ts):**

```typescript
export default defineNuxtConfig({
  modules: ['@nuxtjs/i18n'],
  i18n: {
    locales: [
      { code: 'ar', dir: 'rtl', name: 'العربية' },
      { code: 'en', dir: 'ltr', name: 'English' },
    ],
    defaultLocale: 'ar',
    vueI18n: './i18n.config.ts',
  },
});
```

**Source:** @nuxtjs/i18n v8 documentation + Nuxt 4 i18n patterns

---

### 3.2 Resolved: Locale File Structure for Auth Pages

**Finding:** VeeValidate error messages must be localized, but there's no standard pattern. Frontend decides on key naming.

**Decision:**

- ✅ Create `frontend/locales/ar.json` + `en.json` with flat key structure
- ✅ Use dot notation for sections: `auth.login.title`, `auth.errors.invalid_credentials`
- ✅ Store **all** error messages (backend errors + validation errors) in locale files
- ✅ Components use `$t('auth.login.title')` for template rendering

**File Structure:**

```
frontend/locales/
├── ar.json
│   {
│     "auth": {
│       "login": {
│         "title": "تسجيل الدخول",
│         "email_label": "البريد الإلكتروني",
│         ...
│       },
│       "errors": {
│         "invalid_credentials": "البريد ... خاطئة",
│         "email_exists": "هذا البريد مسجل",
│         ...
│       }
│     }
│   }
└── en.json
    { "auth": { ... same structure ... } }
```

**Usage:**

```vue
<h2>{{ $t('auth.login.title') }}</h2>
<UAlert v-if="errors.general">
  {{ $t(`auth.errors.${errorCode}`) }}
</UAlert>
```

**Source:** i18n-governance skill + spec requirements

---

### 3.3 Resolved: RTL-Safe Tailwind Styling

**Finding:** Tailwind v4 with logical properties plugin auto-flips `ml-4` → `mr-4` in RTL.

**Decision:**

- ✅ Use CSS logical properties: `ms-` (margin-inline-start), `me-` (margin-inline-end), `ps-` (padding-inline-start), `pe-` (padding-inline-end)
- ✅ Nuxt UI components natively support RTL (internal use of logical properties)
- ✅ No manual RTL style overrides needed for standard layouts
- ✅ Custom styles use Tailwind logical utilities (auto-flip in RTL)

**Code Pattern:**

```vue
<div class="ms-2 me-4 ps-1 pe-2">
  <!-- In LTR: margin-left, margin-right, padding-left, padding-right -->
  <!-- In RTL: margin-right, margin-left, padding-right, padding-left (auto-flipped) -->
</div>
```

**Source:** Tailwind v4 documentation + Nuxt UI RTL support + DESIGN.md

---

## 4. Pinia Store Structure for Auth State

### 4.1 Resolved: Store Module Organization

**Finding:** Pinia v2 supports both `defineStore('key', () => {...})` and `defineStore('key', { state, actions, getters })` syntaxes.

**Decision:**

- ✅ Use **Composition API syntax** (`defineStore('auth', () => {...})`)
- ✅ Reason: More readable with TypeScript, mirror Vue Composition API pattern
- ✅ File location: `frontend/stores/auth.ts`
- ✅ Return object exposes state + computed + actions for template + other composables

**Code Structure:**

```typescript
export const useAuthStore = defineStore('auth', () => {
  // State
  const token = ref<string | null>(null);
  const user = ref<User | null>(null);
  const isLoading = ref<boolean>(false);

  // Computed
  const isAuthenticated = computed(() => !!token.value);

  // Actions
  const login = async (email: string, password: string) => {
    isLoading.value = true;
    try {
      const response = await $fetch('/api/v1/auth/login', { body: { email, password } });
      token.value = response.data.token;
      user.value = response.data.user;
      return { success: true };
    } catch (error) {
      return { success: false, error: error.message };
    } finally {
      isLoading.value = false;
    }
  };

  return { token, user, isLoading, isAuthenticated, login };
});
```

**Source:** Pinia 2 documentation + Nuxt 4 best practices

---

### 4.2 Resolved: Token Persistence Strategy

**Finding:** Token stored in both Pinia (runtime) + localStorage (persistence). On page refresh, must hydrate from localStorage.

**Decision:**

- ✅ On app.vue mount: Load token from localStorage → restore to Pinia
- ✅ On logout: Clear from both Pinia + localStorage
- ✅ On successful login: Save to both Pinia + localStorage
- ✅ Use Pinia plugin for hydration on app init

**Implementation (Pinia Plugin):**

```typescript
// nuxt.config.ts
export default defineNuxtConfig({
  pinia: {
    plugins: [
      (context) => {
        const auth = context.store.useAuthStore();
        const savedToken =
          typeof window !== 'undefined' ? localStorage.getItem('auth-token') : null;
        if (savedToken) {
          auth.token = savedToken;
        }
      },
    ],
  },
});
```

**Alternative: In app.vue onMounted**

```typescript
onMounted(() => {
  const auth = useAuthStore();
  const savedToken = localStorage.getItem('auth-token');
  if (savedToken) {
    auth.token = savedToken;
  }
});
```

**Source:** Pinia persistence patterns + spec requirements

---

## 5. Composables Organization

### 5.1 Resolved: Composable Naming & Location

**Finding:** Nuxt auto-imports composables from `composables/` folder following Vue naming convention.

**Decision:**

- ✅ **Location:** `frontend/composables/`
- ✅ **Naming:** `use*.ts` (e.g., `useAuth.ts`, `usePasswordToggle.ts`, `useAuthSchemas.ts`)
- ✅ **Auto-import:** No manual imports needed, Nuxt handles it
- ✅ **Shared:** Used across pages + components

**List of Composables:**

```
frontend/composables/
├── useApi.ts              # HTTP client with interceptors
├── useAuth.ts             # Login, logout, register logic
├── useAuthSchemas.ts      # All Zod validation schemas
├── usePasswordToggle.ts   # Show/hide password state
├── useAuthGuards.ts       # Middleware logic (optional, can be in middleware/)
└── useRbac.ts             # Role-based access (for future use)
```

**Source:** Nuxt 4 composables auto-import feature + convention

---

### 5.2 Resolved: Composable Dependency Management

**Finding:** Composables can call other composables + Pinia stores.

**Decision:**

- ✅ `useAuth()` composable internally calls `useApi()` + `useAuthStore()`
- ✅ No circular dependencies (DAG structure)
- ✅ Composables are **stateless** (pure functions, exception: usePasswordToggle stores visibility state)
- ✅ Pinia store is **single source of truth** for auth state

**Dependency Graph:**

```
Pages/Components
  ↓
useAuth() → useApi() + useAuthStore()
usePasswordToggle() (isolated)
useAuthSchemas() (isolated, just Zod schemas)
```

**Source:** Vue 3 Composition API best practices

---

## 6. API Integration Pattern (Frontend ↔ Backend)

### 6.1 Resolved: useApi Composable for Request/Response Handling

**Finding:** Nuxt 4 provides `$fetch` (built-in HTTP client). Frontend can extend it with interceptors for auth.

**Decision:**

- ✅ Use native `$fetch` or create wrapper via `$fetch.create()`
- ✅ Wrapper includes: base URL, default headers, token injection, error handling
- ✅ Use `useApi()` composable to expose the configured fetch instance
- ✅ All API calls in auth composables go through `useApi()`

**useApi Composable Pattern:**

```typescript
export const useApi = () => {
  const auth = useAuthStore()
  const config = useRuntimeConfig()

  const apiFetch = $fetch.create({
    baseURL: config.public.apiBaseUrl + '/api/v1',
    headers: {
      'Accept': 'application/json',
      'Accept-Language': useI18n().locale.value,
    },
  })

  // Request interceptor: Add authorization header
  apiFetch.onRequest({ options }) {
    if (auth.token) {
      options.headers = options.headers || {}
      options.headers.Authorization = `Bearer ${auth.token}`
    }
  }

  // Response interceptor: Handle 401 → refresh token
  apiFetch.onResponseError({ response }) {
    if (response.status === 401 && auth.token) {
      // Attempt token refresh (backend handles via HTTP-only cookie)
      // Retry original request or redirect to login
    }
  }

  return { apiFetch }
}
```

**Source:** Nuxt $fetch documentation + spec requirements

---

### 6.2 Resolved: Error Response Mapping

**Finding:** Backend returns unified error contract; frontend must parse and display.

**Decision:**

- ✅ All errors follow contract: `{ success, data, error: { code, message, details } }`
- ✅ Frontend catches `error.response?.data?.error` from $fetch
- ✅ Map `error.code` to i18n key or use backend `message` directly
- ✅ Field-level errors in `error.details` → populate form field errors

**Error Handling Pattern:**

```typescript
const login = async (email: string, password: string) => {
  try {
    const response = await apiFetch('/auth/login', {
      method: 'POST',
      body: { email, password },
    });
    return { success: true, data: response.data };
  } catch (error) {
    const errorCode = error.response?.data?.error?.code;
    const errorMessage = error.response?.data?.error?.message;
    const errorDetails = error.response?.data?.error?.details;
    return { success: false, code: errorCode, message: errorMessage, details: errorDetails };
  }
};
```

**Source:** AGENTS.md + error-codes.md + spec requirements

---

## 7. Authentication Middleware & Route Protection

### 7.1 Resolved: Middleware Hierarchy & Execution Order

**Finding:** Nuxt 4 middleware runs in order defined in `definePageMeta` or layout.

**Decision:**

- ✅ Create three middleware files:
  - `middleware/auth.ts` — Requires authentication
  - `middleware/guest.ts` — Requires guest status
  - `middleware/role.ts` — Requires specific role (for future RBAC on auth pages)
- ✅ Auth pages use `middleware: ['guest']` (redirect to dashboard if already logged in)
- ✅ Profile page uses `middleware: ['auth']` (redirect to login if not authenticated)

**Middleware Examples:**

```typescript
// middleware/auth.ts
export default defineNuxtRouteMiddleware((to, from) => {
  const auth = useAuthStore();
  if (!auth.isAuthenticated) {
    return navigateTo('/auth/login');
  }
});

// middleware/guest.ts
export default defineNuxtRouteMiddleware((to, from) => {
  const auth = useAuthStore();
  if (auth.isAuthenticated) {
    return navigateTo('/dashboard');
  }
});
```

**Page Usage:**

```typescript
// pages/auth/login.vue
definePageMeta({ middleware: ['guest'] });

// pages/profile/index.vue
definePageMeta({ middleware: ['auth'] });
```

**Source:** Nuxt 4 middleware documentation + spec requirements

---

## 8. Password Strength Calculation

### 8.1 Resolved: Client-Side Strength Algorithm

**Finding:** No standard "password strength" library exists. Frontend must implement or use service.

**Decision:**

- ✅ Implement client-side algorithm in `PasswordStrength` component
- ✅ Score based on: length (8+), lowercase, uppercase, numbers, symbols
- ✅ Result: 0-100 scale → Red (0-25), Yellow (26-50), Green (51-100)
- ✅ No server validation of strength (server validates regex, client shows UX)

**Algorithm:**

```typescript
const calculateStrength = (password: string): number => {
  if (!password) return 0;
  let score = 0;
  if (password.length >= 8) score += 25;
  if (/[a-z]/.test(password)) score += 25;
  if (/[A-Z]/.test(password)) score += 25;
  if (/[0-9]/.test(password)) score += 25;
  if (/[!@#$%^&*]/.test(password)) score += 25; // Can exceed 100, cap at 100

  return Math.min(score, 100);
};
```

**Source:** Common password strength patterns + spec requirements

---

## 9. Unknowns & Backend-Scoped Items

### 9.1 Backend Decisions (Out of Scope for Frontend Plan)

These items are **backend-scoped** and documented here for reference:

1. **Token expiry times**
   - Backend decides: Access token 15min? Refresh token 7 days?
   - Frontend: Receives `expiresAt` from `/auth/login` response
   - Implementation: Frontend calculates refresh time = expiresAt - 60 seconds

2. **Email delivery mechanism**
   - Backend choice: SMTP, service (SendGrid), queue job
   - Frontend: Assumes email is sent async; shows toast "جاري إرسال..."
   - Timeout: Frontend assumes 5-10 seconds before user can proceed

3. **OTP code generation**
   - Backend choice: 6-digit or 8-digit? Random or time-based?
   - Frontend: Accepts any code from backend validation
   - Assumption: 6-digit (per spec)

4. **Rate limiting strategy**
   - Backend choice: Per IP? Per email? Per user?
   - Frontend: Shows error, disables button for 60 seconds on `RATE_LIMIT_EXCEEDED`
   - Implementation: Backend returns `retryAfter` header (optional)

5. **Session management**
   - Backend choice: Single session per user or multi-device?
   - Frontend: Assumes multi-device by default (refresh token per device)
   - Clear all sessions on password change (backend enforces)

### 9.2 Unknown → Resolved During Planning

| Unknown                              | Resolution                                             |
| ------------------------------------ | ------------------------------------------------------ |
| How to integrate VeeValidate 4 + Zod | toTypedSchema() pattern confirmed + documented         |
| Multi-step form validation approach  | Per-step validation + full validation on submit        |
| RTL button alignment in forms        | Nuxt UI handles auto-flip; use logical properties      |
| Token refresh on 401                 | useApi interceptor auto-retries (transparent to pages) |
| Storage of multi-step form state     | Pinia store holds steps 1-4 data until final submit    |
| Password strength UX                 | Real-time calculation + color-coded progress bar       |

---

## 10. Final Verification Against Bunyan Standards

### 10.1 DESIGN.md Compliance

✅ **Typography:**

- Geist Sans (400/500/600 weights only)
- Negative letter-spacing for headings
- No weight 700 on body text

✅ **Shadows & Borders:**

- All containers use shadow-as-border: `rgba(0,0,0,0.08) 0px 0px 0px 1px`
- No traditional CSS borders
- Card shadows: multi-layer stack (border + elevation + ambient)

✅ **Color Palette:**

- Primary text #171717 (not #000000)
- Backgrounds white (#ffffff)
- Error red (#ef4444)
- No workflow accents needed on auth pages

✅ **Component Radius:**

- Buttons: 6px
- Cards: 8px
- Inputs: 6px

### 10.2 i18n-Governance Compliance

✅ **Languages:**

- Primary: Arabic (RTL)
- Secondary: English (LTR)
- All text keys in locale files

✅ **RTL Support:**

- CSS logical properties throughout
- Nuxt i18n auto-detects `dir="rtl"`
- No manual direction overrides

✅ **Validation Error Messages:**

- All in Arabic + English
- Field-level + form-level
- Specific error codes from backend

### 10.3 Error Handling Patterns Compliance

✅ **Response Contract:**

- All responses follow unified format
- Error codes from standard registry
- Field-level details for validation errors

✅ **Auth-Specific Codes:**

- `AUTH_INVALID_CREDENTIALS` — Wrong email/password
- `AUTH_TOKEN_EXPIRED` — Token refresh failed
- `AUTH_UNAUTHORIZED` — Permission denied
- `VALIDATION_ERROR` — Form validation failed
- `CONFLICT_ERROR` — Email conflict
- `RATE_LIMIT_EXCEEDED` — Too many attempts
- `WORKFLOW_PREREQUISITES_UNMET` — Token/code expired
- `RESOURCE_NOT_FOUND` — User/token not found

### 10.4 Nuxt Frontend Patterns Compliance

✅ **Directory Structure:**

- Pages: `/auth/login`, `/profile`
- Components: AuthCard, PasswordStrength
- Composables: useAuth, useApi, useAuthSchemas
- Stores: auth.ts (Pinia)
- Middleware: auth.ts, guest.ts
- Layouts: auth.vue, dashboard.vue

✅ **Conventions:**

- Vue 3 Composition API with `<script setup>`
- TypeScript types for all ref/computed
- Nuxt UI components auto-imported
- i18n @nuxtjs/i18n plugin

---

## Summary Table: Unknowns → Resolutions

| Research Area                | Unknown                                | Resolution                                         | Link/Reference |
| ---------------------------- | -------------------------------------- | -------------------------------------------------- | -------------- |
| Nuxt 4 + Nuxt UI             | UForm + VeeValidate integration        | Manual binding (UForm container only)              | 1.2            |
| TypeScript + Composition API | ref/computed typing                    | Explicit types: `ref<Type>()`                      | 1.3            |
| VeeValidate 4                | API changes (ValidationError removed)  | Use toTypedSchema() + useForm()                    | 2.1            |
| Multi-step forms             | Validation strategy for 4-step wizard  | Per-step validation + full validation on submit    | 2.2            |
| Cross-field validation       | Confirm password match checking        | Zod .refine() with path assignment                 | 2.3            |
| i18n + Nuxt 4                | RTL locale auto-detection              | @nuxtjs/i18n v8 auto-sets dir="rtl"                | 3.1            |
| Locale file structure        | Organization of error messages         | Flat dot notation: auth.errors.invalid_credentials | 3.2            |
| RTL Tailwind                 | CSS properties for RTL                 | Logical properties (ms, me, ps, pe)                | 3.3            |
| Pinia store organization     | Composition vs Options syntax          | Composition: defineStore('auth', () => {...})      | 4.1            |
| Token persistence            | localStorage + Pinia hydration         | Pinia plugin on app init                           | 4.2            |
| Composables structure        | Dependency management + naming         | use\*.ts auto-imported; DAG dependency structure   | 5.1-5.2        |
| HTTP client interceptors     | Token injection + 401 refresh          | useApi() wrapper with onRequest/onResponseError    | 6.1            |
| Error response mapping       | Backend error contract → UI display    | Parse error.code → i18n lookup or use message      | 6.2            |
| Route middleware             | Protection for auth pages              | guest.ts, auth.ts, role.ts (future)                | 7.1            |
| Password strength UX         | Algorithm for real-time display        | Client-side score calculation (0-100)              | 8.1            |
| Backend scoped               | Token times, email service, OTP design | Documented as assumptions; implementation flexible | 9.1            |

---

## Conclusion

✅ **All knowable unknowns have been resolved and documented.** The remaining items (token expiry times, email service choice, OTP format) are backend implementation decisions that frontend will adapt to via standard API contracts.

**Frontend is ready for Step 4: Tasks Generation** ✓
