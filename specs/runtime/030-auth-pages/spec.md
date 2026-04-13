# STAGE_30 — Auth Pages Specification

**Stage:** 07_FRONTEND_APPLICATION  
**Sub-stage:** Frontend Authentication Pages  
**Version:** 1.0  
**Last Updated:** 2026-04-13  
**Status:** DRAFT → SPECIFY

---

## Executive Summary

This specification defines the implementation of six core authentication pages for Bunyan construction marketplace:

1. **Login** (`/auth/login`) — Email + password authentication with remember-me option
2. **Register** (`/auth/register`) — Multi-step registration wizard (4 steps)
3. **Forgot Password** (`/auth/forgot-password`) — Password reset initiation
4. **Reset Password** (`/auth/reset-password?token=...`) — Token-based password change
5. **Email Verification** (`/auth/verify-email`) — Email confirmation page
6. **Profile** (`/profile`) — User profile management and editing

All pages are built with **Nuxt UI** components, **Vue 3 Composition API**, **VeeValidate 4.x + Zod** validation, and **full RTL/Arabic support** following `DESIGN.md` visual language.

---

## 1. Pages & Routes

### 1.1 Login Page

| Property        | Value                                              |
| --------------- | -------------------------------------------------- |
| **Route**       | `/auth/login`                                      |
| **Layout**      | `auth.vue` (minimal, centered)                     |
| **Middleware**  | `guest` (redirect logged-in users to `/dashboard`) |
| **HTTP Method** | POST `/api/v1/auth/login`                          |

#### Page Structure

```vue
<script setup lang="ts">
  // Page-level composition, business logic in useAuthStore + composables
  const state = reactive({
    email: '',
    password: '',
    rememberMe: false,
    loading: false,
  });

  const submitLogin = async () => {
    // Form validation via UForm + VeeValidate
    // Call API via useAuthStore().login(email, password)
    // Redirect to /dashboard on success
  };
</script>
```

#### Components Used

| Element              | Nuxt UI Component       | Props/Configuration                                 |
| -------------------- | ----------------------- | --------------------------------------------------- |
| Card container       | `UCard`                 | `class="rounded-lg w-full max-w-md"`                |
| Page title           | Text (h2)               | "تسجيل الدخول" / "Login"                            |
| Email input          | `UFormGroup` + `UInput` | `type="email"`, label, placeholder (RTL)            |
| Password input       | `UFormGroup` + `UInput` | `type="password"`, show/hide toggle via `UButton`   |
| Remember me checkbox | `UCheckbox`             | Label: "تذكرني" / "Remember me"                     |
| Submit button        | `UButton`               | `color="neutral"`, `loading={state.loading}`, block |
| Error alert          | `UAlert`                | `color="red"`, visible on validation/API error      |
| Forgot password link | `NuxtLink`              | Route: `/auth/forgot-password`, text style          |
| Register link        | `NuxtLink`              | Route: `/auth/register`, "ليس لديك حساب؟ سجل الآن"  |

#### Form Fields

```typescript
// composables/useAuthSchemas.ts
import { z } from 'zod';

export const loginSchema = z.object({
  email: z
    .string()
    .min(1, { message: 'البريد الإلكتروني مطلوب' })
    .email({ message: 'البريد الإلكتروني غير صالح' }),
  password: z
    .string()
    .min(8, { message: 'كلمة المرور يجب أن تكون 8 أحرف على الأقل' })
    .max(128, { message: 'كلمة المرور طويلة جدًا' }),
  rememberMe: z.boolean().default(false),
});
```

#### Validation Rules

| Field       | Type    | Arabic Rule                            | English Rule                    |
| ----------- | ------- | -------------------------------------- | ------------------------------- |
| Email       | string  | مطلوب + يجب أن يكون بريد إلكتروني صالح | Required + must be valid email  |
| Password    | string  | مطلوب + 8-128 أحرف                     | Required + 8-128 characters     |
| Remember Me | boolean | يجب تحديده يدويًا (اختياري)            | Optional (unchecked by default) |

#### Error Handling

| Error Scenario       | Error Code                 | HTTP | User Message (Arabic)                    | User Message (English)      |
| -------------------- | -------------------------- | ---- | ---------------------------------------- | --------------------------- |
| Invalid email format | `VALIDATION_ERROR`         | 422  | صيغة البريد الإلكتروني غير صحيحة         | Invalid email format        |
| Password too short   | `VALIDATION_ERROR`         | 422  | كلمة المرور قصيرة جدًا                   | Password too short          |
| Invalid credentials  | `AUTH_INVALID_CREDENTIALS` | 401  | البريد الإلكتروني أو كلمة المرور خاطئة   | Invalid email or password   |
| Network error        | `SERVER_ERROR`             | 500  | حدث خطأ في الاتصال، يرجى المحاولة لاحقًا | Connection error, try again |

#### User Flow

```plaintext
1. User navigates to /auth/login
2. Sees email + password fields + remember-me checkbox
3. Enters credentials (client-side validation on blur)
4. Clicks "تسجيل الدخول" button
5. API validates credentials
   - Success: Store token in Pinia + localStorage, redirect to /dashboard
   - Failure: Display red UAlert with error message, keep form filled
6. Logout clears token from localStorage + Pinia store
```

#### Accessibility

- Email input: label linked via `for="email-input"`, ARIA role implicit
- Password input: label linked, toggle button has aria-label="إظهار/إخفاء كلمة المرور"
- Form: `role="form"`, error alerts: `role="alert"`, focus trapZ index logical
- Keyboard navigation: Tab through email → password → remember-me → button → links
- Color contrast: Text `#171717` on white `#ffffff` meets WCAG AAA (21:1)

---

### 1.2 Register Page

| Property        | Value                        |
| --------------- | ---------------------------- |
| **Route**       | `/auth/register`             |
| **Layout**      | `auth.vue`                   |
| **Middleware**  | `guest`                      |
| **HTTP Method** | POST `/api/v1/auth/register` |

#### Multi-Step Wizard (4 Steps)

The register flow is a multi-step wizard using **Nuxt UI USteppers** component.

| Step | Label (AR/EN)                              | Fields                               | Component               |
| ---- | ------------------------------------------ | ------------------------------------ | ----------------------- |
| 1    | نوع الحساب / Account Type                  | userType radio (Customer/Contractor) | `URadioGroup`           |
| 2    | المعلومات الشخصية / Personal Info          | firstName, lastName, phone, idNumber | `UFormGroup` + `UInput` |
| 3    | معلومات العنوان / Address Info             | city, district, address              | `UFormGroup` + `UInput` |
| 4    | التحقق من البريد الإلكتروني / Email Verify | email, password, confirmPassword     | `UFormGroup` + `UInput` |

#### Step 1: Account Type Selection

```typescript
// Field
userType: z.enum(['customer', 'contractor'], {
  message: 'يجب اختيار نوع حساب',
});
```

**UI:**

- `URadioGroup` with 2 options:
  - 🏠 Customer (العميل) — "أنا أبحث عن خدمات البناء"
  - 💼 Contractor (المقاول) — "أنا مقاول / مقدم خدمات"

#### Step 2: Personal Information

```typescript
const personalInfoSchema = z.object({
  firstName: z
    .string()
    .min(2, { message: 'الاسم الأول يجب أن يكون حرفين على الأقل' })
    .max(50, { message: 'الاسم الأول طويل جدًا' }),
  lastName: z
    .string()
    .min(2, { message: 'الاسم الأخير يجب أن يكون حرفين على الأقل' })
    .max(50, { message: 'الاسم الأخير طويل جدًا' }),
  phone: z.string().regex(/^\d{9,15}$/, { message: 'رقم الهاتف غير صالح' }),
  idNumber: z
    .string()
    .min(10, { message: 'رقم الهوية/جواز السفر غير صالح' })
    .max(30, { message: 'رقم الهوية/جواز السفر طويل جدًا' }),
});
```

**UI:**

- First Name: `UInput` (text, RTL)
- Last Name: `UInput` (text, RTL)
- Phone: `UInput` (tel pattern, RTL)
- ID Number: `UInput` (text, RTL)

#### Step 3: Address Information

```typescript
const addressSchema = z.object({
  city: z.string().min(2, { message: 'المدينة مطلوبة' }),
  district: z.string().min(2, { message: 'الحي/المنطقة مطلوبة' }),
  address: z
    .string()
    .min(5, { message: 'العنوان يجب أن يكون أطول' })
    .max(200, { message: 'العنوان طويل جدًا' }),
});
```

**UI:**

- City: `USelect` (dropdown with predefined Saudi cities)
- District: `USelect` (depends on selected city, auto-populate)
- Address: `UTextarea` (multi-line, character counter at bottom)

#### Step 4: Email & Password

```typescript
const emailPasswordSchema = z
  .object({
    email: z
      .string()
      .min(1, { message: 'البريد الإلكتروني مطلوب' })
      .email({ message: 'البريد الإلكتروني غير صالح' }),
    password: z
      .string()
      .min(8, { message: 'كلمة المرور يجب أن تكون 8 أحرف على الأقل' })
      .regex(/[A-Z]/, { message: 'كلمة المرور يجب أن تحتوي على حرف كبير' })
      .regex(/[0-9]/, { message: 'كلمة المرور يجب أن تحتوي على رقم' })
      .regex(/[!@#$%^&*]/, { message: 'كلمة المرور يجب أن تحتوي على رمز خاص' }),
    confirmPassword: z.string(),
  })
  .refine((data) => data.password === data.confirmPassword, {
    message: 'كلمات المرور غير متطابقة',
    path: ['confirmPassword'],
  });
```

**UI:**

- Email: `UInput` (type="email")
- Password: `UInput` (type="password") + `UProgress` strength indicator
- Confirm Password: `UInput` (type="password")
- Password strength: Color-coded `UProgress` (Red→Yellow→Green)

#### Wizard Controls

| Element          | Component   | Behavior                                    |
| ---------------- | ----------- | ------------------------------------------- |
| Step indicators  | `USteppers` | Shows all 4 steps, current step highlighted |
| Back button      | `UButton`   | Disabled on step 1; goes to previous step   |
| Next button      | `UButton`   | Validates step, disabled if invalid         |
| Submit button    | `UButton`   | Visible only on step 4; disabled if invalid |
| Step error alert | `UAlert`    | Shows validation errors for current step    |

#### Form Validation (All Steps)

```typescript
export const registerStepSchemas = {
  1: accountTypeSchema,
  2: personalInfoSchema,
  3: addressSchema,
  4: emailPasswordSchema,
};
```

#### API Request (Step 4 Submit)

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
  "address": "شارع الملك فهد، الحي الشمالي",
  "email": "ahmad@example.com",
  "password": "SecurePass123!",
}
```

#### Success Flow

After successful registration:

1. API responds with `{ success: true, data: { verificationToken, redirectTo: "/auth/verify-email" } }`
2. Store verification token in Pinia + send email
3. Redirect to `/auth/verify-email` with email pre-filled (read-only)
4. Show "جاري إرسال البريد الإلكتروني التحقق..." toast

#### Error Handling

| Scenario             | Error Code         | Message (AR)                             | Message (EN)                    |
| -------------------- | ------------------ | ---------------------------------------- | ------------------------------- |
| Email already exists | `CONFLICT_ERROR`   | هذا البريد الإلكتروني مسجل بالفعل        | Email already registered        |
| Invalid step data    | `VALIDATION_ERROR` | [Field-specific error messages]          | [Field-specific error messages] |
| Password weak        | `VALIDATION_ERROR` | كلمة المرور ضعيفة جدًا                   | Password too weak               |
| Network error        | `SERVER_ERROR`     | حدث خطأ في التسجيل، يرجى المحاولة لاحقًا | Registration failed, try again  |

---

### 1.3 Forgot Password Page

| Property        | Value                               |
| --------------- | ----------------------------------- |
| **Route**       | `/auth/forgot-password`             |
| **Layout**      | `auth.vue`                          |
| **Middleware**  | `guest`                             |
| **HTTP Method** | POST `/api/v1/auth/forgot-password` |

#### Form Fields

```typescript
export const forgotPasswordSchema = z.object({
  email: z
    .string()
    .min(1, { message: 'البريد الإلكتروني مطلوب' })
    .email({ message: 'البريد الإلكتروني غير صالح' }),
});
```

#### Page Structure

```
┌─────────────────────────────────────┐
│ نسيت كلمة المرور؟                  │
│ Forgot Your Password?              │
├─────────────────────────────────────┤
│ أدخل بريدك الإلكتروني نحن سنرسل   │
│ لك رابط إعادة تعيين كلمة المرور    │
│ [Enter email input]                 │
│ [Send Reset Link Button]            │
│ [Back to Login Link]                │
└─────────────────────────────────────┘
```

#### Components

| Element       | Component               |
| ------------- | ----------------------- |
| Title         | Text (h2)               |
| Subtitle      | Text (p, muted)         |
| Email field   | `UFormGroup` + `UInput` |
| Submit button | `UButton`               |
| Success alert | `UAlert` green          |
| Error alert   | `UAlert` red            |
| Back to login | `NuxtLink`              |

#### User Flow

```plaintext
1. User enters email
2. Clicks "إرسال رابط إعادة التعيين" button
3. API validates email:
   - Success: Show green UAlert "تحقق من بريدك الإلكتروني"
   - Not found: Show red UAlert "البريد الإلكتروني غير مسجل" (for security, show generic message)
4. Email backend service sends reset link with token
5. User checks email, clicks link → `/auth/reset-password?token=xyz`
```

---

### 1.4 Reset Password Page

| Property        | Value                              |
| --------------- | ---------------------------------- |
| **Route**       | `/auth/reset-password?token=...`   |
| **Layout**      | `auth.vue`                         |
| **Middleware**  | `guest`                            |
| **HTTP Method** | POST `/api/v1/auth/reset-password` |

#### Form Fields

```typescript
export const resetPasswordSchema = z
  .object({
    token: z.string().min(10, { message: 'رابط غير صالح' }),
    password: z
      .string()
      .min(8, { message: 'كلمة المرور يجب أن تكون 8 أحرف على الأقل' })
      .regex(/[A-Z]/, { message: 'كلمة المرور يجب أن تحتوي على حرف كبير' })
      .regex(/[0-9]/, { message: 'كلمة المرور يجب أن تحتوي على رقم' })
      .regex(/[!@#$%^&*]/, { message: 'كلمة المرور يجب أن تحتوي على رمز خاص' }),
    confirmPassword: z.string(),
  })
  .refine((data) => data.password === data.confirmPassword, {
    message: 'كلمات المرور غير متطابقة',
    path: ['confirmPassword'],
  });
```

#### Components

| Element           | Component               |
| ----------------- | ----------------------- |
| Title             | Text (h2)               |
| Password input    | `UFormGroup` + `UInput` |
| Confirm password  | `UFormGroup` + `UInput` |
| Password strength | `UProgress`             |
| Submit button     | `UButton`               |
| Token error alert | `UAlert` red            |
| Success alert     | `UAlert` green          |

#### Token Validation

On page load (in `useAsyncData`):

- Extract `token` query param
- Call `validateResetToken(token)` API
- If invalid/expired: Show red alert "الرابط منتهي الصلاحية"
- If valid: Show form

#### Error Handling

| Scenario          | Error Code                     | Message (AR)             | Message (EN)          |
| ----------------- | ------------------------------ | ------------------------ | --------------------- |
| Token expired     | `WORKFLOW_PREREQUISITES_UNMET` | الرابط منتهي الصلاحية    | Link expired          |
| Token invalid     | `RESOURCE_NOT_FOUND`           | الرابط غير صالح          | Invalid link          |
| Password too weak | `VALIDATION_ERROR`             | كلمة المرور ضعيفة جدًا   | Password too weak     |
| Mismatch password | `VALIDATION_ERROR`             | كلمات المرور غير متطابقة | Passwords don't match |

---

### 1.5 Email Verification Page

| Property        | Value                                   |
| --------------- | --------------------------------------- |
| **Route**       | `/auth/verify-email`                    |
| **Layout**      | `auth.vue`                              |
| **Middleware**  | `guest` (or logged-in unverified users) |
| **HTTP Method** | POST `/api/v1/auth/verify-email`        |

#### Form Fields

```typescript
export const verifyEmailSchema = z.object({
  email: z.string().email({ message: 'البريد الإلكتروني غير صالح' }),
  code: z
    .string()
    .length(6, { message: 'الكود يجب أن يكون 6 أرقام' })
    .regex(/^[0-9]+$/, { message: 'الكود يجب أن يحتوي على أرقام فقط' }),
});
```

#### Page Structure

```
┌──────────────────────────────────┐
│ تحقق من بريدك الإلكتروني        │
│ Verify Your Email                │
├──────────────────────────────────┤
│ أرسلنا رمز تحقق إلى:             │
│ ahmad@example.com               │
│                                  │
│ [Input OTP: 6 boxes] (UPinInput) │
│ [Verify Button]                  │
│ [Resend Code Link]               │
│ [Change Email Link]              │
└──────────────────────────────────┘
```

#### Components

| Element           | Component         | Config                             |
| ----------------- | ----------------- | ---------------------------------- |
| Title             | Text (h2)         |                                    |
| Email display     | Text (p, muted)   | Masked email (a\*\*\*@example.com) |
| OTP input         | `UPinInput`       | 6 digits, auto-focus first box     |
| Verify button     | `UButton`         | Disabled if code length < 6        |
| Resend link       | `NuxtLink`/button | POST to resend-code API            |
| Change email link | `NuxtLink`        | Back to register page              |
| Timer             | Text + UProgress  | Countdown to resend (60 seconds)   |
| Error alert       | `UAlert` red      |                                    |
| Success alert     | `UAlert` green    |                                    |

#### User Flow

```plaintext
1. User arrives at /auth/verify-email (after registration or manual redirect)
2. Email is pre-filled and read-only (or shown as masked)
3. User receives email with 6-digit code
4. User clicks input boxes and enters code (auto-focus)
5. Click "تحقق" button
6. API validates code:
   - Success: Mark email as verified, redirect to /dashboard
   - Failure: Show red alert "الكود غير صالح" + allow retry
   - Expired: Show red alert "انتهت صلاحية الكود" + show resend link
7. Resend link: POST /api/v1/auth/resend-verification-code (60s cooldown)
```

#### Error Handling

| Scenario        | Error Code                     | Message (AR)                | Message (EN)          |
| --------------- | ------------------------------ | --------------------------- | --------------------- |
| Code too short  | `VALIDATION_ERROR`             | الكود يجب أن يكون 6 أرقام   | Code must be 6 digits |
| Code invalid    | `VALIDATION_ERROR`             | الكود غير صالح              | Invalid code          |
| Code expired    | `WORKFLOW_PREREQUISITES_UNMET` | انتهت صلاحية الكود          | Code expired          |
| Resend cooldown | `RATE_LIMIT_EXCEEDED`          | حاول مرة أخرى خلال 60 ثانية | Try again in 60s      |

---

### 1.6 Profile Page

| Property        | Value                          |
| --------------- | ------------------------------ |
| **Route**       | `/profile`                     |
| **Layout**      | `dashboard.vue` (with sidebar) |
| **Middleware**  | `auth` (logged-in only)        |
| **HTTP Method** | PATCH `/api/v1/user/profile`   |

#### Form Fields

```typescript
export const profileSchema = z.object({
  firstName: z.string().min(2, { message: 'الاسم الأول يجب أن يكون حرفين على الأقل' }),
  lastName: z.string().min(2, { message: 'الاسم الأخير يجب أن يكون حرفين على الأقل' }),
  phone: z.string().regex(/^\d{9,15}$/, { message: 'رقم الهاتف غير صالح' }),
  city: z.string().optional(),
  district: z.string().optional(),
  address: z.string().optional(),
  languagePreference: z.enum(['ar', 'en']).optional(),
});
```

#### Page Structure

```
┌─────────────────────────────────────────┐
│ الملف الشخصي                             │
│ My Profile                              │
├─────────────────────────────────────────┤
│ [Avatar Upload]      | [Edit Form]      │
│ [Change Photo]       | First Name       │
│                      | Last Name        │
│                      | Phone            │
│                      | City (Select)    │
│                      | District (Select)│
│                      | Address          │
│                      | Language Pref    │
│                      | [Save] [Cancel]  │
│                      | [Change Password]│
└─────────────────────────────────────────┘
```

#### Components

| Element            | Component                  | Config                             |
| ------------------ | -------------------------- | ---------------------------------- |
| Page header        | Text (h1)                  | "الملف الشخصي"                     |
| Avatar             | `UAvatar` + upload         | Drag-drop or click to upload       |
| Avatar change link | Text link                  | Triggers file input (hidden)       |
| Form container     | `UForm`                    | Grid layout (2 columns on desktop) |
| First name field   | `UFormGroup` + `UInput`    | Text input, RTL                    |
| Last name field    | `UFormGroup` + `UInput`    | Text input, RTL                    |
| Phone field        | `UFormGroup` + `UInput`    | Tel pattern, RTL                   |
| City field         | `UFormGroup` + `USelect`   | Dropdown of Saudi cities           |
| District field     | `UFormGroup` + `USelect`   | Cascading (depends on city)        |
| Address field      | `UFormGroup` + `UTextarea` | Multi-line, 5-200 chars            |
| Language select    | `UFormGroup` + `USelect`   | "العربية" / "English"              |
| Save button        | `UButton` primary          | Disabled if no changes             |
| Cancel button      | `UButton` outline          | Reverts form to original           |
| Change password    | `NuxtLink`/modal button    | Opens Change Password Modal (+1.7) |
| Delete account     | `UButton` outline red      | Opens confirmation dialog          |
| Success toast      | `UToast`                   | "تم حفظ الملف الشخصي بنجاح"        |
| Error alert        | `UAlert` red               |                                    |

#### Change Password Modal (1.7: Hidden requirement)

Triggered by "تغيير كلمة المرور" button:

```typescript
export const changePasswordSchema = z
  .object({
    currentPassword: z.string().min(1, { message: 'كلمة المرور الحالية مطلوبة' }),
    newPassword: z
      .string()
      .min(8, { message: 'كلمة المرور الجديدة يجب أن تكون 8 أحرف على الأقل' })
      .regex(/[A-Z]/, { message: 'يجب أن تحتوي على حرف كبير' })
      .regex(/[0-9]/, { message: 'يجب أن تحتوي على رقم' })
      .regex(/[!@#$%^&*]/, { message: 'يجب أن تحتوي على رمز خاص' }),
    confirmPassword: z.string(),
  })
  .refine((data) => data.newPassword === data.confirmPassword, {
    message: 'كلمات المرور الجديدة غير متطابقة',
    path: ['confirmPassword'],
  });
```

Modal components: `UModal` + form fields (same pattern as login/register).

#### User Flow

```plaintext
1. User logs in and navigates to /profile
2. Page loads with existing user data (from Pinia store)
3. User edits any field
4. Click "حفظ" button
5. Form validates (ZOD + VeeValidate)
6. API PATCH /api/v1/user/profile with updated fields
7. Success: Show green toast "تم الحفظ" + update Pinia store
8. Error: Show red alert with field-level errors
```

---

## 2. Shared UI Components & Composables

### 2.1 AuthCard Component

Reusable wrapper for all auth pages:

```vue
<script setup lang="ts">
  defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
  });
</script>

<template>
  <div class="min-h-screen flex items-center justify-center px-4">
    <UCard class="w-full max-w-md rounded-lg shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08)]">
      <template #header>
        <div>
          <h2 class="text-[#171717] font-semibold text-2xl tracking-[-0.96px]">
            {{ title }}
          </h2>
          <p v-if="subtitle" class="text-gray-600 text-sm mt-1">
            {{ subtitle }}
          </p>
        </div>
      </template>
      <slot />
    </UCard>
  </div>
</template>

<style scoped>
  /* RTL support via Tailwind logical properties */
  /* dir="rtl" applied on <html> via nuxt.config.ts */
</style>
```

### 2.2 PasswordStrength Component

Indicator for password strength during registration:

```vue
<script setup lang="ts">
  const props = defineProps<{
    password: string;
  }>();

  const strength = computed(() => {
    if (!props.password) return 0;
    let score = 0;
    if (props.password.length >= 8) score += 25;
    if (/[a-z]/.test(props.password)) score += 25;
    if (/[A-Z]/.test(props.password)) score += 25;
    if (/[0-9]/.test(props.password)) score += 25;
    return Math.min(score, 100);
  });

  const strengthLabel = computed(() => {
    const s = strength.value;
    if (s === 0) return { ar: '', en: '' };
    if (s <= 25) return { ar: 'ضعيفة', en: 'Weak' };
    if (s <= 50) return { ar: 'متوسطة', en: 'Fair' };
    if (s <= 75) return { ar: 'جيدة', en: 'Good' };
    return { ar: 'قوية', en: 'Strong' };
  });

  const strengthColor = computed(() => {
    const s = strength.value;
    if (s === 0) return 'gray';
    if (s <= 25) return 'red';
    if (s <= 50) return 'yellow';
    if (s <= 75) return 'green';
    return 'green';
  });
</script>

<template>
  <div v-if="password" class="space-y-2">
    <UProgress :value="strength" :color="strengthColor" />
    <p class="text-xs text-gray-600">
      {{ $t(`auth.password_strength_${strengthLabel}`) }}
    </p>
  </div>
</template>
```

### 2.3 useAuthSchemas Composable

Centralized Zod schemas for all auth forms:

```typescript
// composables/useAuthSchemas.ts
import { z } from 'zod';

export const useAuthSchemas = () => {
  const emailSchema = z
    .string()
    .min(1, { message: 'البريد الإلكتروني مطلوب' })
    .email({ message: 'البريد الإلكتروني غير صالح' });

  const passwordSchema = z
    .string()
    .min(8, { message: 'كلمة المرور يجب أن تكون 8 أحرف على الأقل' })
    .regex(/[A-Z]/, { message: 'يجب أن تحتوي على حرف كبير' })
    .regex(/[0-9]/, { message: 'يجب أن تحتوي على رقم' })
    .regex(/[!@#$%^&*]/, { message: 'يجب أن تحتوي على رمز خاص' });

  const loginSchema = z.object({
    email: emailSchema,
    password: z.string().min(8, { message: 'كلمة المرور يجب أن تكون 8 أحرف على الأقل' }),
    rememberMe: z.boolean().default(false),
  });

  // ... more schemas

  return {
    emailSchema,
    passwordSchema,
    loginSchema,
    registerStepSchemas,
    // ... etc
  };
};
```

### 2.4 useAuth Composable

Authentication logic and state management:

```typescript
// composables/useAuth.ts
export const useAuth = () => {
  const auth = useAuthStore();
  const { apiFetch } = useApi();

  const login = async (email: string, password: string, rememberMe = false) => {
    try {
      const response = await apiFetch('/auth/login', {
        method: 'POST',
        body: { email, password, rememberMe },
      });

      if (response.success) {
        auth.setToken(response.data.token);
        auth.setUser(response.data.user);
        if (rememberMe) {
          localStorage.setItem('rememberMe', 'true');
        }
        return { success: true };
      }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.error?.message,
      };
    }
  };

  // ... register, logout, etc.

  return { login, register, logout /* ... */ };
};
```

### 2.5 usePasswordToggle Composable

Show/hide password toggle logic:

```typescript
export const usePasswordToggle = () => {
  const isVisible = ref(false);
  const type = computed(() => (isVisible.value ? 'text' : 'password'));
  const toggle = () => (isVisible.value = !isVisible.value);

  return { isVisible, type, toggle };
};
```

---

## 3. Pinia Authentication Store

### 3.1 Auth Store

```typescript
// stores/auth.ts
import { defineStore } from 'pinia';
import type { User } from '~/types';

export const useAuthStore = defineStore('auth', () => {
  // State
  const token = ref<string | null>(null);
  const user = ref<User | null>(null);
  const isLoading = ref(false);
  const error = ref<string | null>(null);

  // Getters
  const isAuthenticated = computed(() => !!token.value);
  const userRole = computed(() => user.value?.role || null);

  // Actions
  const setToken = (newToken: string) => {
    token.value = newToken;
    localStorage.setItem('auth_token', newToken);
  };

  const setUser = (newUser: User) => {
    user.value = newUser;
    localStorage.setItem('user', JSON.stringify(newUser));
  };

  const logout = () => {
    token.value = null;
    user.value = null;
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
    navigateTo('/auth/login');
  };

  const initFromStorage = () => {
    if (process.client) {
      const storedToken = localStorage.getItem('auth_token');
      const storedUser = localStorage.getItem('user');
      if (storedToken) token.value = storedToken;
      if (storedUser) user.value = JSON.parse(storedUser);
    }
  };

  return {
    // State
    token: readonly(token),
    user: readonly(user),
    isLoading: readonly(isLoading),
    error: readonly(error),
    // Getters
    isAuthenticated,
    userRole,
    // Actions
    setToken,
    setUser,
    logout,
    initFromStorage,
  };
});
```

---

## 4. i18n & RTL Configuration

### 4.1 Translation Keys

```json
// frontend/locales/ar.json
{
  "auth": {
    "login": "تسجيل الدخول",
    "register": "إنشاء حساب",
    "forgot_password": "نسيت كلمة المرور؟",
    "reset_password": "إعادة تعيين كلمة المرور",
    "verify_email": "تحقق من بريدك الإلكتروني",
    "email_placeholder": "البريد الإلكتروني",
    "password_placeholder": "كلمة المرور",
    "password_confirm": "تأكيد كلمة المرور",
    "password_strength_weak": "ضعيفة",
    "password_strength_fair": "متوسطة",
    "password_strength_good": "جيدة",
    "password_strength_strong": "قوية",
    "remember_me": "تذكرني",
    "account_type": "نوع الحساب",
    "customer": "عميل",
    "contractor": "مقاول",
    "first_name": "الاسم الأول",
    "last_name": "الاسم الأخير",
    "phone": "رقم الهاتف",
    "city": "المدينة",
    "district": "الحي",
    "address": "العنوان",
    "language_preference": "اللغة المفضلة",
    "send_reset_link": "إرسال رابط إعادة التعيين",
    "verify_code": "التحقق من الكود",
    "resend_code": "إعادة إرسال الكود",
    "back_to_login": "العودة إلى تسجيل الدخول",
    "dont_have_account": "ليس لديك حساب؟",
    "register_now": "سجل الآن",
    "error_invalid_credentials": "البريد الإلكتروني أو كلمة المرور خاطئة",
    "error_email_not_found": "البريد الإلكتروني غير مسجل",
    "error_email_exists": "البريد الإلكتروني مسجل بالفعل",
    "error_token_expired": "انتهت صلاحية الرابط",
    "error_code_expired": "انتهت صلاحية الكود"
  }
}

// frontend/locales/en.json
{
  "auth": {
    "login": "Login",
    "register": "Create Account",
    "forgot_password": "Forgot Password?",
    "reset_password": "Reset Password",
    "verify_email": "Verify Your Email",
    "email_placeholder": "Email Address",
    "password_placeholder": "Password",
    "password_confirm": "Confirm Password",
    "password_strength_weak": "Weak",
    "password_strength_fair": "Fair",
    "password_strength_good": "Good",
    "password_strength_strong": "Strong",
    "remember_me": "Remember Me",
    "account_type": "Account Type",
    "customer": "Customer",
    "contractor": "Contractor",
    "first_name": "First Name",
    "last_name": "Last Name",
    "phone": "Phone Number",
    "city": "City",
    "district": "District",
    "address": "Address",
    "language_preference": "Language",
    "send_reset_link": "Send Reset Link",
    "verify_code": "Verify Code",
    "resend_code": "Resend Code",
    "back_to_login": "Back to Login",
    "dont_have_account": "Don't have an account?",
    "register_now": "Register now",
    "error_invalid_credentials": "Invalid email or password",
    "error_email_not_found": "Email not registered",
    "error_email_exists": "Email already registered",
    "error_token_expired": "Link expired",
    "error_code_expired": "Code expired"
  }
}
```

### 4.2 RTL Configuration

```typescript
// nuxt.config.ts
export default defineNuxtConfig({
  app: {
    head: {
      htmlAttrs: {
        dir: 'rtl',
        lang: 'ar',
      },
    },
  },
  i18n: {
    locales: [
      { code: 'ar', iso: 'ar-SA', dir: 'rtl' },
      { code: 'en', iso: 'en-US', dir: 'ltr' },
    ],
    defaultLocale: 'ar',
  },
});
```

---

## 5. Validation & Error Handling

### 5.1 Form Validation Pattern

All forms use the VeeValidate + Zod pattern:

```vue
<script setup lang="ts">
  import { useForm } from 'vee-validate';
  import { toTypedSchema } from '@vee-validate/zod';
  import { loginSchema } from '~/composables/useAuthSchemas';

  const { values, errors, isSubmitting, handleSubmit } = useForm({
    validationSchema: toTypedSchema(loginSchema),
  });

  const onSubmit = handleSubmit(async (values) => {
    const { success, error } = await useAuth().login(values.email, values.password);
    if (!success) {
      // Show error alert
    }
  });
</script>

<template>
  <form @submit="onSubmit">
    <UFormGroup label="البريد الإلكتروني" :error="errors.email">
      <UInput
        v-model="values.email"
        type="email"
        placeholder="your@email.com"
        data-testid="email-input"
      />
    </UFormGroup>

    <UFormGroup label="كلمة المرور" :error="errors.password">
      <UInput
        v-model="values.password"
        type="password"
        placeholder="••••••••"
        data-testid="password-input"
      />
    </UFormGroup>

    <UButton
      type="submit"
      label="تسجيل الدخول"
      :loading="isSubmitting"
      block
      data-testid="login-button"
    />
  </form>
</template>
```

### 5.2 Error Code Registry

All error responses follow the standard error contract:

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "AUTH_INVALID_CREDENTIALS",
    "message": "البريد الإلكتروني أو كلمة المرور خاطئة",
    "details": null
  }
}
```

Error codes:

- `VALIDATION_ERROR` (422)
- `AUTH_INVALID_CREDENTIALS` (401)
- `AUTH_UNAUTHORIZED` (403)
- `CONFLICT_ERROR` (409)
- `RESOURCE_NOT_FOUND` (404)
- `WORKFLOW_PREREQUISITES_UNMET` (422)
- `RATE_LIMIT_EXCEEDED` (429)
- `SERVER_ERROR` (500)

---

## 6. Accessibility Requirements

### 6.1 WCAG 2.1 Level AA Compliance

| Requirement    | Implementation                                                  |
| -------------- | --------------------------------------------------------------- |
| Color contrast | Text `#171717` on `#ffffff` = 21:1 WCAG AAA                     |
| Focus ring     | 2px solid `hsla(212, 100%, 48%, 1)` on all interactive elements |
| Keyboard nav   | Full Tab navigation, no keyboard traps                          |
| Form labels    | All inputs have associated `<label>` with `for` attr            |
| Error msgs     | `role="alert"` on error containers                              |
| ARIA roles     | Semantic HTML + ARIA roles (form, button, textbox)              |
| Placeholders   | Not substituted for labels; labels always present               |
| Language       | `lang="ar"` on `<html>`, `lang="en"` when switching             |

### 6.2 Testing Accessibility

```typescript
// tests/a11y/auth.spec.ts
test('login page meets WCAG AA contrast', async ({ page }) => {
  await page.goto('/auth/login');
  const textColor = await page.evaluate(() => {
    const el = document.querySelector('h2');
    return window.getComputedStyle(el).color;
  });
  // Verify contrast >= 4.5:1
});

test('form has proper label associations', async ({ page }) => {
  await page.goto('/auth/login');
  const emailLabel = await page.getAttribute('label[for="email"]', 'for');
  expect(emailLabel).toBe('email');
});

test('keyboard navigation works', async ({ page }) => {
  await page.goto('/auth/login');
  await page.keyboard.press('Tab');
  await expect(page.locator('[data-testid="email-input"]')).toBeFocused();
});
```

---

## 7. Testing Requirements

### 7.1 Unit Tests (Vitest)

**Validation schemas:**

```typescript
// tests/unit/schemas.test.ts
describe('Auth Schemas', () => {
  test('loginSchema validates valid credentials', () => {
    const result = loginSchema.safeParse({
      email: 'user@example.com',
      password: 'SecurePass123!',
    });
    expect(result.success).toBe(true);
  });

  test('loginSchema rejects invalid email', () => {
    const result = loginSchema.safeParse({
      email: 'not-an-email',
      password: 'SecurePass123!',
    });
    expect(result.success).toBe(false);
  });

  test('password regex enforces strength', () => {
    const result = passwordSchema.safeParse('weak');
    expect(result.success).toBe(false);
  });
});
```

**Store tests:**

```typescript
// tests/unit/stores/auth.test.ts
describe('Auth Store', () => {
  test('setToken stores token in state and localStorage', () => {
    const auth = useAuthStore();
    auth.setToken('test-token');
    expect(auth.token).toBe('test-token');
    expect(localStorage.getItem('auth_token')).toBe('test-token');
  });

  test('logout clears state and localStorage', () => {
    const auth = useAuthStore();
    auth.setToken('test-token');
    auth.logout();
    expect(auth.token).toBe(null);
    expect(localStorage.getItem('auth_token')).toBe(null);
  });
});
```

### 7.2 E2E Tests (Playwright)

```typescript
// tests/e2e/auth-login.spec.ts
test('login success redirects to dashboard', async ({ page }) => {
  await page.goto('/auth/login');
  await page.fill('[data-testid="email-input"]', 'user@example.com');
  await page.fill('[data-testid="password-input"]', 'SecurePass123!');
  await page.click('[data-testid="login-button"]');
  await expect(page).toHaveURL('/dashboard');
});

test('login error shows red alert', async ({ page }) => {
  await page.goto('/auth/login');
  await page.fill('[data-testid="email-input"]', 'wrong@example.com');
  await page.fill('[data-testid="password-input"]', 'wrong');
  await page.click('[data-testid="login-button"]');
  const alert = page.locator('[role="alert"]');
  await expect(alert).toHaveClass('text-red-500');
});

test('register multi-step flow completes', async ({ page }) => {
  // Step 1: Select account type
  await page.goto('/auth/register');
  await page.click('[name="userType"][value="customer"]');
  await page.click('[data-testid="next-button"]');

  // Step 2: Fill personal info
  await page.fill('[data-testid="first-name"]', 'أحمد');
  await page.fill('[data-testid="last-name"]', 'محمد');
  await page.fill('[data-testid="phone"]', '966912345678');
  await page.fill('[data-testid="id-number"]', '1234567890');
  await page.click('[data-testid="next-button"]');

  // ... continue through all steps
});

test('forgot password sends reset email', async ({ page }) => {
  await page.goto('/auth/forgot-password');
  await page.fill('[data-testid="email-input"]', 'user@example.com');
  await page.click('[data-testid="send-button"]');
  const alert = await page.locator('[role="alert"]').textContent();
  expect(alert).toContain('تحقق من بريدك');
});

test('RTL layout is applied', async ({ page }) => {
  await page.goto('/auth/login');
  const html = page.locator('html');
  const dir = await html.getAttribute('dir');
  expect(dir).toBe('rtl');
});
```

---

## 8. Component Architecture

### 8.1 File Structure

```
frontend/
├── components/
│   ├── auth/
│   │   ├── AuthCard.vue
│   │   ├── PasswordStrength.vue
│   │   ├── OtpInput.vue (wrapper around UPinInput)
│   │   └── PasswordToggle.vue
│   └── ui/
│       └── FormField.vue (wrapper helper)
├── pages/
│   └── auth/
│       ├── login.vue
│       ├── register.vue
│       ├── forgot-password.vue
│       ├── reset-password.vue
│       └── verify-email.vue
├── profile.vue
├── composables/
│   ├── useAuth.ts
│   ├── useAuthSchemas.ts
│   ├── usePasswordToggle.ts
│   ├── useApi.ts
│   └── useRbac.ts
├── stores/
│   └── auth.ts
├── types/
│   └── auth.ts
├── locales/
│   ├── ar.json
│   └── en.json
└── tests/
    ├── unit/
    │   ├── schemas.test.ts
    │   └── stores/auth.test.ts
    └── e2e/
        ├── auth-login.spec.ts
        ├── auth-register.spec.ts
        ├── auth-forgot-password.spec.ts
        └── auth-profile.spec.ts
```

### 8.2 Component Dependencies

```
UForm (VeeValidate wrapper)
  ├── UFormGroup (label + error)
  │   ├── UInput (text/email/password)
  │   ├── USelect (dropdown)
  │   ├── UTextarea
  │   └── UCheckbox
  ├── USteppers (multi-step wizard)
  │   └── UCard (per-step container)
  ├── URadioGroup (account type selection)
  ├── UPinInput (OTP)
  └── UButton (submit)

Shared:
  ├── UCard (page wrapper)
  ├── UAlert (error/success)
  ├── UProgress (password strength)
  └── AuthCard (reusable auth page container)
```

---

## 9. Design System Alignment

### 9.1 DESIGN.md Compliance

All components follow DESIGN.md specifications:

| Aspect           | Rule                                  | Implementation                      |
| ---------------- | ------------------------------------- | ----------------------------------- |
| Font Family      | Geist Sans (body) / Geist Mono (code) | Applied globally in `app.vue`       |
| Text Color       | `#171717` (NOT `#000000`)             | Tailwind class `text-[#171717]`     |
| Shadow-as-border | `0px 0px 0px 1px rgba(0,0,0,0.08)`    | Applied to UCard, UInput borders    |
| Border radius    | 6px (buttons), 8px (cards)            | `rounded-lg` / `rounded-[6px]`      |
| Letter spacing   | -2.4px (display), normal (body)       | `tracking-[-0.96px]` on headings    |
| Weights          | 400 (body), 500 (UI), 600 (headings)  | Font weight utilities applied       |
| Focus ring       | 2px solid `hsla(212, 100%, 48%, 1)`   | Nuxt UI default focus styling       |
| Palette          | Achromatic + workflow accents         | Neutral colors only (no reds/blues) |

### 9.2 Tailwind v4 Utilities

All styling uses Tailwind v4 + Nuxt UI:

```vue
<!-- Example: Login button styled per DESIGN.md -->
<UButton
  label="تسجيل الدخول"
  color="neutral"
  class="
    rounded-[6px]
    text-sm
    font-medium
    shadow-[0px_0px_0px_1px_rgba(0,0,0,0.08)]
    hover:bg-[#171717]
    hover:text-white
    transition-colors
  "
/>
```

---

## 10. API Contract

### 10.1 Request/Response Formats

All API endpoints follow the standard error contract from `AGENTS.md`:

**Success Response:**

```json
{
  "success": true,
  "data": {
    "token": "...",
    "user": { "id": 1, "email": "...", "role": "customer" }
  },
  "error": null
}
```

**Error Response:**

```json
{
  "success": false,
  "data": null,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "البريد الإلكتروني غير صالح",
    "details": {
      "email": ["البريد الإلكتروني يجب أن يكون صيغة صحيحة"]
    }
  }
}
```

### 10.2 Endpoints Summary

| Endpoint                           | Method | Request Body                 | Response                      |
| ---------------------------------- | ------ | ---------------------------- | ----------------------------- |
| `/api/v1/auth/login`               | POST   | email, password              | token, user                   |
| `/api/v1/auth/register`            | POST   | email, password, ...         | verificationToken, redirectTo |
| `/api/v1/auth/forgot-password`     | POST   | email                        | message                       |
| `/api/v1/auth/reset-password`      | POST   | token, password              | message                       |
| `/api/v1/auth/verify-email`        | POST   | code                         | verified, redirectTo          |
| `/api/v1/auth/resend-verification` | POST   | email (optional)             | message                       |
| `/api/v1/user/profile`             | GET    | (no body)                    | user (complete profile)       |
| `/api/v1/user/profile`             | PATCH  | firstName, lastName...       | user (updated)                |
| `/api/v1/user/change-password`     | POST   | currentPassword, newPassword | message                       |

---

## 11. Ambiguities Identified & Resolutions

### 11.1 Open Questions

| Item                     | Question                                    | Resolution                                         |
| ------------------------ | ------------------------------------------- | -------------------------------------------------- |
| Remember Me persistence  | How long should remember-me tokens persist? | 30 days via extended JWT lifespan + localStorage   |
| Avatar upload size       | Max file size for profile avatar?           | 5MB, JPEG/PNG only                                 |
| Phone number format      | Country code required (966 for SA)?         | Accept 9-15 digits, backend validates format       |
| Refresh token rotation   | Should refresh tokens be rotated?           | Yes, post-login refresh returned via secure cookie |
| Change password redirect | Where should user go after password change? | Stay on /profile, show success toast               |
| Email case sensitivity   | Are emails case-sensitive?                  | No, normalize to lowercase on backend              |
| 2FA support              | Is 2FA required or optional?                | Optional, can be added in future stage             |

### 11.2 Dependencies on Backend

| Frontend Requirement         | Backend Dependency                     | Status   |
| ---------------------------- | -------------------------------------- | -------- |
| Login API response structure | STAGE_03_AUTHENTICATION (token format) | Complete |
| Email verification flow      | Email service configured               | Pending  |
| Reset password tokens        | Token generation + expiry logic        | Pending  |
| User profile endpoint        | User model + profile schema            | Pending  |
| Avatar upload                | File storage + image processing        | Pending  |
| Phone validation             | International phone format support     | Pending  |

---

## 12. Success Criteria

### 12.1 Functional Acceptance

- [ ] Login page accepts credentials, calls API, redirects on success
- [ ] Register page completes all 4 steps, creates user account
- [ ] Forgot password initiates reset flow, sends email
- [ ] Reset password accepts token, changes password
- [ ] Email verification accepts OTP code, marks email verified
- [ ] Profile page loads user data, allows edits, saves changes
- [ ] All validation errors shown in Arabic/English per user locale
- [ ] RTL layout applied correctly (dir="rtl" on html)
- [ ] All Nuxt UI components styled per DESIGN.md

### 12.2 Quality Gates

- [ ] All 6 pages covered by E2E tests (Playwright)
- [ ] Validation schemas tested unit tests (Vitest)
- [ ] Auth store tested (Pinia store tests)
- [ ] WCAG AA accessibility compliance verified
- [ ] All translation keys present in ar.json + en.json
- [ ] Form errors display in correct language based on locale
- [ ] Mobile responsive (tested at 375px width)
- [ ] Dark mode NOT required (per DESIGN.md achromatic palette)

### 12.3 Performance Targets

- [ ] Page load time < 2s (DOMContentLoaded)
- [ ] Form validation < 100ms (client-side)
- [ ] API response < 1s (login/register)
- [ ] Bundle size increase < 50KB (auth pages only)

---

## 13. Out of Scope

The following are explicitly OUT OF SCOPE for this stage:

- Social login (Google, Apple) — future stage
- Biometric authentication (fingerprint, face) — future stage
- Two-factor authentication (SMS/TOTP) — future stage
- Dark mode — not in design system
- Password reset via SMS — email-only for now
- Account recovery codes — future stage
- Single sign-on (SSO) — future stage
- Passwordless authentication — future stage

---

## Specification End

**Next Steps:** This spec is now ready for:

1. **Clarify Phase** – Ask 5 targeted clarification questions
2. **Plan Phase** – Break down into implementation tasks and design artifacts
3. **Implementation** – Team develops per spec + testing requirements
