# STAGE_30 — Auth Pages Technical Design Plan

**Stage:** 07_FRONTEND_APPLICATION  
**Sub-stage:** Frontend Authentication Pages  
**Version:** 1.0  
**Date:** 2026-04-13  
**Status:** PLANNING → Ready for Tasks

---

## 1. Architecture Overview

### 1.1 Frontend-Only Pages with Backend API Integration

The six auth pages are **frontend-only UI implementations** (Nuxt.js + Vue 3) that orchestrate authentication workflows via a Laravel RESTful backend API:

- **Frontend responsibility:** Form rendering, client-side validation (VeeValidate + Zod), UX state management (Pinia), error display
- **Backend responsibility:** API route handlers, token generation, email delivery, persistence, security (CSRF, rate limiting)
- **Communication:** JSON API contract via unified error format (see docs/api/error-codes.md)

### 1.2 Token Storage Strategy

**Mechanism:** Dual-token approach following Laravel Sanctum conventions

1. **Access Token** (short-lived, 15 min)
   - Backend: Generated via `POST /api/v1/auth/login` as HTTP-only cookie OR JSON response
   - Frontend: Stored in **Pinia auth store** (runtime) and **localStorage** (persistence)
   - Usage: Attached to all API requests via `Authorization: Bearer` header (via useApi composable)
   - Auto-refresh: Intercepted on 401 response → call `/api/v1/auth/refresh` → retry original request

2. **Refresh Token** (long-lived, 7 days)
   - Storage: HTTP-only cookie (backend sets, frontend cannot access)
   - Usage: Automatic refresh flow on access token expiry
   - Remember-me: If enabled, refresh token TTL extends to 30 days

3. **Device Token** (optional, for multi-device sessions)
   - Used if "Remember me" checkbox checked
   - Stored in localStorage alongside access token

### 1.3 Error Handling & Unified Contract

All API responses follow the standard Bunyan contract:

```json
{
  "success": true|false,
  "data": {...} | null,
  "error": {
    "code": "ERROR_CODE",
    "message": "User-friendly message (localized)",
    "details": { "field": ["field-level error message"] } | null
  } | null
}
```

**Frontend error mapping strategy:**

- Parse `error.code` from response
- Look up user-facing message in i18n (ar.json, en.json)
- Display via `UAlert` component (red) if HTTP error or `UAlert` (green) if success
- For field-level errors: Populate `form.errors` in VeeValidate context

**Rate limiting:**

- Backend: 10 login attempts per 15 minutes (configurable)
- Frontend: Show `UAlert` with error code `RATE_LIMIT_EXCEEDED`
- UX: Disable login button for 60 seconds on rate limit

---

## 2. Page-by-Page Implementation Plan

### 2.1 Login Page (`/auth/login`)

**Route:** `/auth/login`  
**Middleware:** `guest` (redirect `/dashboard` if authenticated)  
**Layout:** Centered card via `AuthCard` wrapper component

**Form Structure:**

```typescript
interface LoginForm {
  email: string; // email@example.com
  password: string; // 8-128 chars
  rememberMe: boolean; // Optional
}
```

**Nuxt UI Components Used:**

- `UCard` — container with shadow-as-border + 6px radius
- `UFormGroup` — label + error message underneath
- `UInput` — email field (type="email"), password field (type="password")
- `UButton` — show/hide toggle (icon + aria-label), submit button
- `UCheckbox` — remember me option
- `UAlert` — error display (red), success toast (green)

**Component Hierarchy:**

```
AuthCard (centered wrapper)
├── UForm (validation context)
│   ├── UFormGroup "البريد الإلكتروني"
│   │   └── UInput type="email"
│   ├── UFormGroup "كلمة المرور"
│   │   ├── UInput type="password|text"
│   │   └── UButton icon="eye" @click="togglePassword()"
│   ├── UCheckbox "تذكرني"
│   └── [Validation Errors] UAlert color="red"
├── UButton "تسجيل الدخول" :loading="isLoading"
├── NuxtLink "نسيت كلمة المرور؟"
└── NuxtLink "ليس لديك حساب؟ سجل الآن"
```

**Key Features:**

- Client-side validation on blur (email, password length)
- Submit button disabled until form is valid + no loading state
- Password field toggles between `type="password"` and `type="text"` via icon button
- Remember-me checkbox extends refresh token TTL if checked
- On successful login: Store token in Pinia + localStorage → redirect to `/dashboard`
- On failed login: Display red `UAlert` with error message from backend
- Forgotten password link routes to `/auth/forgot-password`
- Register link routes to `/auth/register`

**Accessibility:**

- Form role implicit (UForm)
- Email label: `<label for="email">البريد الإلكتروني</label>`
- Password label: `<label for="password">كلمة المرور</label>`
- Show/hide button: `aria-label="إظهار كلمة المرور"` / `aria-label="إخفاء كلمة المرور"`
- Error alert: `role="alert"` (re-announced on focus)
- Focus ring: 2px solid `hsla(212, 100%, 48%, 1)` on all interactive elements
- Tab order: email → show/hide → password → remember-me → submit → links

---

### 2.2 Register Page (`/auth/register`) — Multi-Step Wizard

**Route:** `/auth/register`  
**Middleware:** `guest`  
**Layout:** Centered card + stepper  
**Wizard Pattern:** 4-step form with client-side validation per step

**Step 1: Account Type Selection**

```
┌─────────────────────────┐
│ Step 1 of 4             │
│ نوع الحساب              │
│ Account Type            │
├─────────────────────────┤
│ ○ 🏠 العميل             │
│ ○ 💼 المقاول            │
├─────────────────────────┤
│ [Back Disabled] [Next]  │
└─────────────────────────┘
```

**Components:**

- `USteppers` — progress indicator (visual + textual)
- `URadioGroup` — 2 options with icons + labels
- `UButton` — Back (disabled on step 1), Next (validates step)

**Validation Schema:**

```typescript
userType: z.enum(['customer', 'contractor']);
```

---

**Step 2: Personal Information**

```
┌─────────────────────────┐
│ Step 2 of 4             │
│ المعلومات الشخصية       │
├─────────────────────────┤
│ [First Name]            │
│ [Last Name]             │
│ [Phone Number]          │
│ [ID Number]             │
├─────────────────────────┤
│ [Back] [Next]           │
└─────────────────────────┘
```

**Components:**

- `UFormGroup` — labels + error display
- `UInput` — 4 text fields (firstName, lastName, phone, idNumber)
- RTL-aware text direction

**Validation Schema:**

```typescript
{
  firstName: "أحمد" (2-50 chars),
  lastName: "محمد" (2-50 chars),
  phone: "966912345678" (9-15 digits),
  idNumber: "1234567890" (10-30 chars)
}
```

---

**Step 3: Address Information**

```
┌─────────────────────────┐
│ Step 3 of 4             │
│ معلومات العنوان         │
├─────────────────────────┤
│ [City Select]           │
│ [District Select]       │
│ [Address Textarea]      │
│ Chars: 45/200           │
├─────────────────────────┤
│ [Back] [Next]           │
└─────────────────────────┘
```

**Components:**

- `USelect` — city dropdown (predefined Saudi cities)
- `USelect` — district dropdown (cascading, depends on city)
- `UFormGroup` — label + error
- `UTextarea` — address input (5-200 chars)
- Character counter below textarea

**Validation Schema:**

```typescript
{
  city: "الرياض|جدة|الدمام|..." (required),
  district: "الخليج|العليا|..." (required, depends on city),
  address: "شارع الملك فهد..." (5-200 chars)
}
```

**Key Feature:**

- City dropdown populated from static array or API
- District dropdown cascades: on city selection, fetch/filter districts for that city
- Both support Arabic and searchable

---

**Step 4: Email & Password**

```
┌─────────────────────────┐
│ Step 4 of 4             │
│ البريد الإلكتروني       │
├─────────────────────────┤
│ [Email]                 │
│ [Password]              │
│ [Strength Bar: ████]    │
│ [Confirm Password]      │
├─────────────────────────┤
│ [Back] [Submit Register]│
└─────────────────────────┘
```

**Components:**

- `UFormGroup` — email, passwords
- `UInput` — email, password (with show/hide toggle), confirmPassword
- `UProgress` — password strength indicator (0-100 → red→yellow→green)
- `PasswordStrength` component — label text (ضعيفة/متوسطة/جيدة/قوية)

**Validation Schema:**

```typescript
{
  email: "تطلب صلاحية + تنسيق بريد صحيح + فحص التكرار (يجب أن يكون فريدًا)",
  password: "8+ chars + [A-Z] + [0-9] + [!@#$%^&*]",
  confirmPassword: "يجب أن يطابق password"
}
```

**Wizard Controls:**

- Back button: Returns to previous step (enabled on steps 2-4)
- Next button: Validates current step field(s) before advancing
- Submit button: Only on step 4; validates entire form (all 4 steps) before API call
- Error alert: Shows field-level validation error for current step

**API Request on Submit:**

```typescript
POST /api/v1/auth/register
{
  "userType": "customer",
  "firstName": "أحمد",
  "lastName": "محمد",
  "phone": "966912345678",
  "idNumber": "1234567890",
  "city": "الرياض",
  "district": "الخليج",
  "address": "شارع الملك فهد",
  "email": "ahmad@example.com",
  "password": "SecurePass123!"
}
```

**Success Flow:**

- Backend: User created, verification email queued
- Frontend: Redirect to `/auth/verify-email?email=ahmad@example.com`
- UX: Show green toast "تم التسجيل بنجاح! تحقق من بريدك الإلكتروني"

**Error Handling:**

- `CONFLICT_ERROR` (email exists) → display in step 4
- `VALIDATION_ERROR` → display field-level errors
- `SERVER_ERROR` → retry button

---

### 2.3 Forgot Password Page (`/auth/forgot-password`)

**Route:** `/auth/forgot-password`  
**Middleware:** `guest`

**Form Structure:**

```
┌─────────────────────────┐
│ نسيت كلمة المرور؟       │
│ Forgot Your Password?   │
├─────────────────────────┤
│ أدخل بريدك الإلكتروني   │
│ [Email Input]           │
│ [Send Reset Link]       │
│ [Back to Login]         │
└─────────────────────────┘
```

**Components:**

- `UFormGroup` + `UInput` — email field
- `UButton` — submit (disabled if email invalid)
- `UAlert` — success/error messages

**Validation:**

- Client-side: email format check (VeeValidate + Zod)
- Disable submit button until valid

**User Flow:**

1. Enter email → click "إرسال رابط إعادة التعيين"
2. API hits `/api/v1/auth/forgot-password` with email
3. **Success:** Show green `UAlert` "تحقق من بريدك الإلكتروني" (generic, no email confirmation)
4. **Not found:** Show same message (for security, don't leak email existence)
5. Backend: Queues email with reset token (valid 1 hour)
6. User receives email → clicks link → redirects to `/auth/reset-password?token=xyz`

---

### 2.4 Reset Password Page (`/auth/reset-password?token=...`)

**Route:** `/auth/reset-password`  
**Query Param:** `token` (required)  
**Middleware:** `guest`

**Page Load Sequence:**

1. Extract `token` from query string
2. **Via `useAsyncData`:** Validate token with backend (optional, or handle on form submit)
3. If invalid/expired: Show red `UAlert` "الرابط منتهي الصلاحية" → hide form
4. If valid: Render form

**Form Structure:**

```
┌─────────────────────────┐
│ إعادة تعيين كلمة المرور  │
│ Reset Password          │
├─────────────────────────┤
│ [New Password]          │
│ [Strength Bar]          │
│ [Confirm Password]      │
│ [Submit]                │
│ [Back to Login]         │
└─────────────────────────┘
```

**Components:** Same as register step 4 (password + strength)

**Validation Schema:**

```typescript
{
  password: "8+ chars + [A-Z] + [0-9] + [!@#$%^&*]",
  confirmPassword: "match check"
}
```

**API Request:**

```typescript
POST /api/v1/auth/reset-password
{
  "token": "xyz...",
  "password": "NewPass123!"
}
```

**Success Flow:**

- Show green toast "تم تعيين كلمة المرور بنجاح"
- Redirect to `/auth/login`
- Clear all active sessions on backend (security best practice)

**Error Handling:**

- `WORKFLOW_PREREQUISITES_UNMET` — token expired
- `RESOURCE_NOT_FOUND` — token invalid
- `VALIDATION_ERROR` — password too weak

---

### 2.5 Email Verification Page (`/auth/verify-email`)

**Route:** `/auth/verify-email`  
**Middleware:** `guest` (or allow unverified logged-in users)  
**Query Param:** `email` (optional, pre-filled from registration)

**Page Structure:**

```
┌──────────────────────────┐
│ تحقق من بريدك الإلكتروني │
│ Verify Your Email        │
├──────────────────────────┤
│ أرسلنا رمز تحقق إلى:     │
│ ahmad****@example.com   │
│                          │
│ [ ][ ][ ][ ][ ][ ]       │
│ (UPinInput: 6 digits)    │
│ [Verify Button]          │
│ [Resend Code]            │
│ [Change Email]           │
│ Resend in 60 seconds     │
└──────────────────────────┘
```

**Components:**

- `UPinInput` — 6-digit OTP input fields
- `UButton` — verify button (disabled if code length < 6)
- `NuxtLink`/button — resend code (with 60s cooldown)
- `NuxtLink` — change email (back to register)
- Text + timer display — countdown to resend

**Auto-submit behavior:**

- On entry of 6th digit: auto-focus verify button (or auto-submit)

**Validation Schema:**

```typescript
{
  code: 'length === 6 && only digits';
}
```

**API Endpoints:**

1. **Verify code:** `POST /api/v1/auth/verify-email` with `{ code }`
2. **Resend code:** `POST /api/v1/auth/resend-verification-code` with `{ email }`

**User Flow:**

1. Page loads with email pre-filled (read-only or masked)
2. User receives email with 6-digit code
3. User enters 6 digits in OTP input (auto-focus between boxes)
4. Click "تحقق" or auto-submit on 6th digit
5. **Success:** Redirect to `/dashboard` + show green toast "تم التحقق من البريد الإلكتروني"
6. **Invalid code:** Red `UAlert` "الكود غير صالح" → allow retry
7. **Expired code:** Red `UAlert` "انتهت صلاحية الكود" → show resend button
8. **Resend cooldown:** Disable resend button for 60 seconds + show countdown timer

**Error Handling:**

- `VALIDATION_ERROR` — code format/length invalid
- `WORKFLOW_PREREQUISITES_UNMET` — code expired
- `RATE_LIMIT_EXCEEDED` — resend too many times (show cooldown)

---

### 2.6 Profile Page (`/profile`)

**Route:** `/profile`  
**Middleware:** `auth` (logged-in only)  
**Layout:** Dashboard with sidebar

**Page Structure:**

```
┌─────────────────┬──────────────────┐
│ Sidebar         │ Main Content     │
│                 │ الملف الشخصي      │
│                 │────────────────  │
│                 │ [Avatar Upload]  │
│                 │ [Edit Form]      │
│                 │ - First Name     │
│                 │ - Last Name      │
│                 │ - Phone          │
│                 │ - City/District  │
│                 │ - Address        │
│                 │ - Language       │
│                 │ [Save] [Cancel]  │
│                 │ [Change Password]│
└─────────────────┴──────────────────┘
```

**Form Fields (Pinia Pre-fill):**

```typescript
{
  firstName: "أحمد",
  lastName: "محمد",
  phone: "966912345678",
  city: "الرياض",
  district: "الخليج",
  address: "شارع الملك فهد",
  languagePreference: "ar" | "en",
}
```

**Components:**

- `UAvatar` — current profile picture + click/drag-drop to upload
- `UForm` — 2-column grid on desktop
- `UFormGroup` + `UInput` — text fields (firstName, lastName, phone)
- `UFormGroup` + `USelect` — city/district dropdowns
- `UFormGroup` + `UTextarea` — address
- `UFormGroup` + `USelect` — language preference
- `UButton primary` — Save (visible only if form is dirty)
- `UButton outline` — Cancel
- `NuxtLink` — "تغيير كلمة المرور" (opens Change Password modal)

**Page Load:**

```typescript
const { data: user } = await useAsyncData('profile', () =>
  $fetch('/api/v1/user/profile', { headers: { Authorization: `Bearer ${token}` } })
);
// Populate form with user data
```

**Form Behavior:**

- Dirty check: Track form state vs. initial state
- Save button only visible/enabled if dirty + valid
- Cancel button reverts to initial state
- Successful save: Update Pinia store + show green toast
- Failed save: Show red alert with field-level errors

**API Request (Save):**

```typescript
PATCH /api/v1/user/profile
{
  "firstName": "أحمد",
  "lastName": "محمد",
  "phone": "966912345678",
  "city": "الرياض",
  "district": "الخليج",
  "address": "شارع الملك فهد",
  "languagePreference": "ar"
}
```

**Avatar Upload:**

- Drag-drop + click to upload
- Validate file type (JPEG, PNG, WebP) + size (5MB max)
- Show loading spinner during upload
- API: `POST /api/v1/user/avatar` with multipart/form-data
- On success: Update avatar in Pinia + refresh display
- On failure: Show red alert

**Change Password Modal (Sub-feature):**

```
┌──────────────────┐
│ تغيير كلمة المرور │
├──────────────────┤
│ [Current Password]
│ [New Password]
│ [Strength Bar]
│ [Confirm Password]
│ [Submit] [Cancel] │
└──────────────────┘
```

- **Components:** Same as register step 4
- **New password validation:** 8+ chars + [A-Z] + [0-9] + [!@#$%^&*]
- **API:** `POST /api/v1/user/change-password` with `{ currentPassword, newPassword }`
- **Success:** Close modal + show green toast
- **Error:** Show red alert (wrong current password, etc.)

---

## 3. Component Specifications

### 3.1 Nuxt UI Component Usage

| Page Component   | Nuxt UI Component       | Config Notes                               |
| ---------------- | ----------------------- | ------------------------------------------ | -------------------------------- |
| Card wrapper     | `UCard`                 | shadow-as-border, 6px radius, width: md    |
| Form container   | `UForm`                 | validation context, grid layout on desktop |
| Form group label | `UFormGroup`            | label, description, error message, help    |
| Text input       | `UInput`                | type, placeholder, size, disabled state    |
| Email input      | `UInput type="email"`   | Keyboard type on mobile                    |
| Tel input        | `UInput type="tel"`     | Pattern validation                         |
| Textarea         | `UTextarea`             | rows, resize, placeholder                  |
| Password field   | `UInput type="password  | text"`                                     | Toggle show/hide via icon button |
| Select dropdown  | `USelect`               | options, searchable (cities), cascading    |
| Checkbox         | `UCheckbox`             | label, disabled state (remember-me)        |
| Radio group      | `URadioGroup`           | options with icons (account type)          |
| Button           | `UButton`               | variant (solid/outline), loading, disabled |
| Alert            | `UAlert`                | color (red/green), icon, dismissible       |
| Toast            | `useToast()` / `UToast` | Success/error messages                     |
| Modal            | `UModal`                | Change password modal, confirmation        |
| Progress bar     | `UProgress`             | Password strength indicator (0-100)        |
| Stepper          | `USteppers`             | 4 steps, current step, clickable steps     |
| Avatar           | `UAvatar`               | image src, fallback, upload trigger        |
| Pin input        | `UPinInput`             | 6 digits, auto-focus, keyboard nav         |

### 3.2 RTL-Aware Styling

All components must support RTL layout via Nuxt config:

```typescript
// nuxt.config.ts
export default defineNuxtConfig({
  app: {
    head: {
      htmlAttrs: { dir: 'rtl', lang: 'ar' },
    },
  },
});
```

**CSS Logical Properties (Tailwind):**

- `margin-inline-start` (right margin in RTL)
- `margin-inline-end` (left margin in RTL)
- `padding-inline-start` / `-end`
- `border-inline-start` / `-end`
- `text-align: end` (right-align in RTL)

**Nuxt UI native support:** All Nuxt UI components auto-detect `dir="rtl"` and flip layout accordingly.

### 3.3 Error/Success State Handling

**Error Alert Pattern:**

```vue
<UAlert v-if="errors.general" color="red" icon="i-heroicons-exclamation-circle">
  {{ errors.general }}
</UAlert>
```

**Field-Level Errors:**

```vue
<UFormGroup label="البريد الإلكتروني" :error="form.errors.email">
  <UInput v-model="form.email" type="email" />
</UFormGroup>
```

**Success Toast:**

```typescript
const toast = useToast();
toast.add({
  title: 'نجح',
  description: 'تم حفظ الملف الشخصي بنجاح',
  color: 'green',
});
```

### 3.4 Loading States

- **Form submit button:** Show loading spinner, disable button
- **API calls:** Show `isLoading` state in button
- **Password strength:** Real-time calculation (no async)

---

## 4. Validation Layer

### 4.1 Zod Schema Definitions

**Total Schemas:** 10+

1. **loginSchema** — email + password + rememberMe
2. **registerStepSchemas (4)** — account type, personal, address, email+password
3. **forgotPasswordSchema** — email only
4. **resetPasswordSchema** — token + password + confirmPassword
5. **verifyEmailSchema** — code (6 digits)
6. **profileSchema** — firstName, lastName, phone, city, district, address, languagePreference
7. **changePasswordSchema** — currentPassword + newPassword + confirmPassword

**Location:** `frontend/composables/useAuthSchemas.ts`

### 4.2 Client-Side Validation Messages (Arabic + English)

All error messages in `useAuthSchemas` composable + i18n locale files:

**Messages Categories:**

- **Required:** "حقل [name] مطلوب"
- **Length:** "[field] يجب أن يكون بين [min]-[max] أحرف"
- **Email:** "البريد الإلكتروني غير صحيح"
- **Regex:** "كلمة المرور يجب أن تحتوي على [criteria]"
- **Match:** "كلمات المرور غير متطابقة"
- **Unique:** "[field] مسجل بالفعل" (from backend error)

### 4.3 Server-Side Error Code Mapping

Frontend maps backend error codes to user messages:

| Error Code                     | HTTP | Default Message (AR)               | Override Location  |
| ------------------------------ | ---- | ---------------------------------- | ------------------ |
| `VALIDATION_ERROR`             | 422  | Field-level messages from data     | form.errors object |
| `AUTH_INVALID_CREDENTIALS`     | 401  | "البريد أو كلمة المرور خاطئة"      | useAuth composable |
| `CONFLICT_ERROR`               | 409  | "هذا البريد مسجل بالفعل"           | form-level error   |
| `WORKFLOW_PREREQUISITES_UNMET` | 422  | "الرابط منتهي الصلاحية"            | page-level alert   |
| `RESOURCE_NOT_FOUND`           | 404  | "المورد غير موجود"                 | page-level alert   |
| `RATE_LIMIT_EXCEEDED`          | 429  | "حاول بعد 60 ثانية"                | page-level alert   |
| `SERVER_ERROR`                 | 500  | "حدث خطأ ما، يرجى المحاولة لاحقًا" | page-level alert   |

### 4.4 Field-Level + Form-Level Validation

**VeeValidate + Zod Integration:**

```typescript
const form = reactive({ email: '', password: '' });
const { errors, validate } = useForm({
  schema: loginSchema,
});

const onSubmit = async () => {
  const isValid = await validate();
  if (!isValid) return;
  // Submit to API
};
```

---

## 5. State Management (Pinia)

### 5.1 useAuthStore() Structure

**File:** `frontend/stores/auth.ts`

```typescript
export const useAuthStore = defineStore('auth', () => {
  // === State ===
  const user = ref<User | null>(null);
  const token = ref<string | null>(null);
  const refreshToken = ref<string | null>(null);
  const isAuthenticated = computed(() => !!token.value);
  const isLoading = ref(false);
  const error = ref<ApiError | null>(null);

  // === Actions ===
  const login = async (email: string, password: string, rememberMe: boolean) => {
    /* ... */
  };
  const register = async (payload: RegisterPayload) => {
    /* ... */
  };
  const logout = () => {
    /* ... */
  };
  const refreshAccessToken = async () => {
    /* ... */
  };
  const setUser = (u: User) => {
    user.value = u;
  };
  const setToken = (t: string, refreshT?: string) => {
    /* ... */
  };
  const clearAuth = () => {
    /* ... */
  };

  // === Return (expose to pages) ===
  return {
    user,
    token,
    isAuthenticated,
    isLoading,
    error,
    login,
    register,
    logout,
    refreshAccessToken,
  };
});
```

### 5.2 Token Refresh Logic

**Auto-refresh on 401:**

```typescript
// composables/useApi.ts
apiFetch.onResponseError((context) => {
  if (context.response.status === 401) {
    const auth = useAuthStore();
    if (auth.refreshToken) {
      await auth.refreshAccessToken(); // Calls /api/v1/auth/refresh
      // Retry original request with new token
    } else {
      auth.logout(); // No refresh token, clear auth
      navigateTo('/auth/login');
    }
  }
});
```

### 5.3 Middleware Chain for Route Protection

**Middleware Order:**

1. `auth.ts` — Redirect `/auth/login` if not authenticated
2. `guest.ts` — Redirect `/dashboard` if authenticated
3. `role.ts` — Enforce RBAC (if needed for auth pages)

**Usage in pages:**

```typescript
definePageMeta({
  middleware: ['auth'], // for /profile
});
```

---

## 6. Integration Points

### 6.1 Backend API Endpoints (Summary)

| Method | Endpoint                                | Input                        | Output                         |
| ------ | --------------------------------------- | ---------------------------- | ------------------------------ |
| POST   | `/api/v1/auth/login`                    | email, password, rememberMe  | token, refreshToken, user      |
| POST   | `/api/v1/auth/register`                 | Full registration payload    | verificationToken, redirectTo  |
| POST   | `/api/v1/auth/forgot-password`          | email                        | {success, no data}             |
| POST   | `/api/v1/auth/reset-password`           | token, password              | {success, no data}             |
| POST   | `/api/v1/auth/verify-email`             | code                         | {success, redirectTo}          |
| POST   | `/api/v1/auth/resend-verification-code` | email                        | {success, cooldownSeconds}     |
| POST   | `/api/v1/auth/refresh`                  | refreshToken (HTTP-only)     | token, refreshToken, expiresAt |
| GET    | `/api/v1/user/profile`                  | —                            | user object (firstName, etc.)  |
| PATCH  | `/api/v1/user/profile`                  | Updated fields               | user object                    |
| POST   | `/api/v1/user/avatar`                   | multipart: file              | { avatarUrl }                  |
| POST   | `/api/v1/user/change-password`          | currentPassword, newPassword | {success, no data}             |

### 6.2 Error Response Mapping

**Example: Invalid credentials on login**

Backend response:

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "AUTH_INVALID_CREDENTIALS",
    "message": "البريد الإلكتروني أو كلمة المرور خاطئة"
  }
}
```

Frontend mapping:

```typescript
const submitLogin = async () => {
  try {
    await auth.login(email, password);
  } catch (err) {
    console.error(err.response?.data?.error?.code);
    showError(err.response?.data?.error?.message); // Use backend message
  }
};
```

### 6.3 Success State Updates in Pinia

**Login flow:**

```
1. User submits form
2. API returns { success: true, data: { token, user } }
3. Store token in Pinia auth.store + localStorage
4. Update useAuthStore().user with returned user object
5. Redirect to /dashboard (via middleware/auth.ts)
```

**Register flow:**

```
1. User completes 4-step wizard
2. API returns { success: true, data: { verificationToken } }
3. Store verificationToken in Pinia (temporary)
4. Redirect to /auth/verify-email
5. Pre-fill email field (read-only)
```

---

## 7. Testing Strategy

### 7.1 Unit Tests (Vitest)

**Test files:** `frontend/tests/unit/auth/`

- **useAuthSchemas.ts:** Zod schema validation + edge cases
- **useAuth.ts:** login, logout, register methods (mock API)
- **usePasswordToggle.ts:** Toggle logic
- **Pinia store:** Actions, computed properties, state mutations

**Coverage target:** >85% per file

### 7.2 E2E Tests (Playwright)

**Test files:** `frontend/tests/e2e/auth/`

**Scenarios (8+ tests):**

1. **Login - Success**
   - Navigate to `/auth/login`
   - Enter valid email + password
   - Click submit → Redirect to `/dashboard`
   - Verify token in localStorage

2. **Login - Invalid Credentials**
   - Enter wrong email/password
   - Submit → Show red alert "البريد أو كلمة المرور خاطئة"
   - Form remains filled

3. **Register - Complete 4-Step Wizard**
   - Select account type → next
   - Fill personal info → next
   - Fill address → next
   - Fill email + password (check strength) → submit
   - Redirect to `/auth/verify-email`

4. **Register - Duplicate Email**
   - Fill entire wizard
   - Submit with existing email
   - Show error "البريد مسجل بالفعل" on step 4
   - Don't advance steps

5. **Forgot Password - Email Sent**
   - Navigate to `/auth/forgot-password`
   - Enter email → submit
   - Show success message "تحقق من بريدك"
   - Verify no email-not-found disclosure (generic message for security)

6. **Reset Password - Token Valid**
   - Click reset link from email (with valid token)
   - Navigate to `/auth/reset-password?token=abc123`
   - Fill new password (check strength) + confirm
   - Submit → Redirect to `/auth/login`
   - Login with new password succeeds

7. **Reset Password - Token Expired**
   - Navigate to `/auth/reset-password?token=expired123`
   - Show red alert "الرابط منتهي الصلاحية"
   - Form hidden
   - Link to forgot password page

8. **Email Verification - OTP Flow**
   - Navigate to `/auth/verify-email` (after registration)
   - View masked email
   - Enter 6-digit code
   - Submit → Redirect to `/dashboard`
   - Account marked verified on backend

9. **Email Verification - Resend Cooldown**
   - Click resend code
   - Button disabled for 60 seconds
   - Show countdown timer

10. **Profile - Update Info**
    - Login + navigate to `/profile`
    - Edit firstName + phone
    - Click save → Show green toast "تم الحفظ"
    - Data persisted in Pinia store

11. **Profile - Change Password Modal**
    - Click "تغيير كلمة المرور"
    - Modal opens
    - Fill current password (wrong) → Submit → Show error
    - Fill correct current + new password → Submit → Modal closes
    - Logout + login with new password succeeds

12. **Profile - Avatar Upload**
    - Click avatar / "Change Photo"
    - Select JPEG file (5MB)
    - Show loading spinner
    - On success: Avatar updates on page
    - On failure: Show red alert

### 7.3 Accessibility Testing (WCAG AA)

- Keyboard navigation (Tab through all fields)
- ARIA labels on buttons + alerts
- Color contrast (at least 4.5:1 for text)
- Focus ring visible (2px blue outline)
- Screen reader announces form errors
- RTL layout preserved

---

## 8. i18n & RTL Architecture

### 8.1 Locales Structure

**Files:** `frontend/locales/ar.json` + `frontend/locales/en.json`

**Auth section keys:**

```json
{
  "auth": {
    "login": {
      "title": "تسجيل الدخول",
      "email_label": "البريد الإلكتروني",
      "password_label": "كلمة المرور",
      "remember_me": "تذكرني",
      "forgot_password": "نسيت كلمة المرور؟",
      "no_account": "ليس لديك حساب؟ سجل الآن",
      "submit": "تسجيل الدخول"
    },
    "register": {
      "step_1_title": "نوع الحساب",
      "step_1_customer": "أنا أبحث عن خدمات البناء",
      "step_1_contractor": "أنا مقاول / مقدم خدمات",
      "step_2_title": "المعلومات الشخصية",
      "step_2_first_name": "الاسم الأول"
      // ... more keys
    },
    "password_strength": {
      "weak": "ضعيفة",
      "fair": "متوسطة",
      "good": "جيدة",
      "strong": "قوية"
    },
    "errors": {
      "invalid_credentials": "البريد الإلكتروني أو كلمة المرور خاطئة",
      "email_exists": "هذا البريد الإلكتروني مسجل بالفعل",
      "password_mismatch": "كلمات المرور غير متطابقة"
      // ... more error keys
    }
  }
}
```

### 8.2 RTL-Safe Tailwind Properties

All auth pages use logical CSS properties (auto-flipped by Tailwind in RTL):

```css
/* ✅ Use these (auto-flip in RTL) */
margin-inline-start: 1rem; /* right in RTL */
margin-inline-end: 1rem; /* left in RTL */
padding-inline-start: 0.5rem;
padding-inline-end: 0.5rem;
border-inline-start: 1px solid;
text-align: end; /* right in RTL */

/* ❌ Avoid these (don't auto-flip) */
margin-left: 1rem;
margin-right: 1rem;
padding-left: 0.5rem;
border-left: 1px solid;
text-align: right;
```

### 8.3 Font Handling

**Geist families (per DESIGN.md):**

- `Geist Sans` — body + UI (400, 500, 600 weights)
- `Geist Mono` — code labels + captions
- Both families already support Arabic ligatures + OpenType features

**Configured in Tailwind:**

```typescript
// tailwind.config.ts
theme: {
  fontFamily: {
    sans: ['Geist', 'sans-serif'],
    mono: ['Geist Mono', 'monospace'],
  }
}
```

---

## 9. Performance Targets

### 9.1 Performance Budgets

| Metric                                 | Target          | Why             |
| -------------------------------------- | --------------- | --------------- |
| Login form Time to Interactive (TTI)   | <500ms          | Perceived speed |
| Register wizard navigation (step→step) | <300ms per step | No jank         |
| API call timeout                       | 10s             | UX feedback     |
| Auth pages bundle size (gzipped)       | <50KB           | Fast DL         |
| Token refresh latency                  | <500ms          | Seamless UX     |
| Verify email OTP submission latency    | <2s             | Real-time       |

### 9.2 Optimization Strategies

- **Code splitting:** Auth pages in separate chunk (`auth.*.js`)
- **Lazy components:** `useAuth`, `PasswordStrength` lazy-loaded
- **Image optimization:** Avatar upload resized on backend
- **Caching:** Store user + token in localStorage (reuse on page reload)
- **Debouncing:** Password strength calculation (real-time, no async)
- **Prefetch:** Link to `/dashboard` on successful login

---

## 10. Security Checklist

### 10.1 CSRF Token Handling (Sanctum)

- Backend: CSRF middleware enabled (default in Laravel)
- Frontend: `useApi` composable automatically includes CSRF token from cookie
- No manual CSRF header injection needed (Sanctum + Cookie-to-Header pattern)

### 10.2 XSS Prevention

- **Nuxt auto-escapes:** All template bindings auto-escaped ({{ email }})
- **Sanitize links:** Use `NuxtLink` for internal routes (prevents javascript: URLs)
- **No v-html:** Never use `v-html` with user input
- **Error messages:** Display backend error messages directly (backend must sanitize)

### 10.3 Token Storage

✅ **Access Token:**

- Store in Pinia auth store (runtime, cleared on refresh)
- Also in localStorage for session persistence
- Attached via `Authorization: Bearer` in useApi interceptor

✅ **Refresh Token:**

- HTTP-only cookie (backend sets, frontend cannot access)
- Automatic on 401 response via `useApi` interceptor
- No manual refresh logic needed (transparent to pages)

❌ **Never:**

- Store access token in URL query params
- Store tokens in sessionStorage (better than localStorage but still risky)
- Log tokens in console.log or error messages

### 10.4 NO Credentials in URL Params

✅ **allowed:**

- `/auth/reset-password?token=abc123xyz` (one-time reset token, safe)
- `/auth/verify-email?email=user@example.com` (read-only, masked in display)

❌ **forbidden:**

- `/auth/login?email=...&password=...`
- `/auth/register?token=user.access.token`

### 10.5 Rate Limiting

- Backend: 10 login attempts per 15 minutes (configurable)
- Frontend: On `RATE_LIMIT_EXCEEDED` error, disable login button for 60 seconds
- UX: Show countdown timer or message "حاول بعد [X] ثانية"

### 10.6 Password Handling

- **Client:** Never log passwords (all logs exclude password field)
- **Frontend form:** Zod schema includes password regex (8+ chars, uppercase, digit, symbol)
- **Backend validation:** Repeat same regex validation server-side
- **Storage:** Never store plaintext passwords; use bcrypt hashing (Laravel default)

### 10.7 Email Verification

- Backend: Generate 6-digit code (not email-based)
- Frontend: User enters 6 digits via OTP input
- Expiry: Code valid for 10 minutes (backend enforces)
- Resend: 60-second cooldown between resends (backend enforces)

### 10.8 Account Takeover Prevention

- **Password reset:** Token valid for 1 hour only, one-time use
- **Email verification:** 6-digit code, not email confirmation link
- **Session management:** Logout clears all active sessions on backend
- **Remember-me:** Extends refresh token TTL but doesn't bypass email verification

---

## Checklist Verification

- [x] All 10 sections included in plan.md
- [x] 6 pages fully specified (login, register, forgot, reset, verify, profile)
- [x] Nuxt UI components verified + RTL support confirmed
- [x] VeeValidate 4 + Zod patterns correct
- [x] Pinia store structure outlined + token refresh logic
- [x] i18n layer fully scoped (Arabic/English routes)
- [x] Security checklist complete (CSRF, XSS, token storage, rate limiting, password handling)
- [x] E2E test scenarios outlined (12 scenarios)
- [x] Performance targets specified
- [x] Accessibility requirements defined (WCAG AA)
- [x] Error handling mapped from backend error codes
- [x] All decisions align with DESIGN.md (Geist, shadow-as-border, RTL)

---

**Ready for Step 4: Tasks Generation** ✓
