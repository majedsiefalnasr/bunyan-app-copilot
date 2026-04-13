# STAGE_30 — Auth Pages Data Model

**Date:** 2026-04-13  
**Scope:** Frontend state, store structure, validation schemas, component data flow  
**Language:** TypeScript

---

## 1. Frontend State Structure

### 1.1 Pinia useAuthStore()

**File:** `frontend/stores/auth.ts`

Complete type definitions and store implementation:

```typescript
// frontend/types/index.ts
export interface User {
  id: string;
  email: string;
  firstName: string;
  lastName: string;
  phone: string;
  idNumber: string;
  userType: 'customer' | 'contractor';
  city: string;
  district: string;
  address: string;
  avatarUrl: string | null;
  emailVerified: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface AuthToken {
  accessToken: string;
  refreshToken: string;
  expiresAt: number;
  tokenType: 'Bearer';
}

export interface ApiError {
  code: string;
  message: string;
  details?: Record<string, string[]> | null;
}

export interface ApiResponse<T> {
  success: boolean;
  data: T | null;
  error: ApiError | null;
}

// frontend/stores/auth.ts
import { defineStore } from 'pinia';
import type { User, AuthToken, ApiError, ApiResponse } from '~/types';

export const useAuthStore = defineStore('auth', () => {
  // ========== STATE ==========

  const user = ref<User | null>(null);
  const token = ref<string | null>(null);
  const refreshToken = ref<string | null>(null);
  const tokenExpiresAt = ref<number | null>(null);
  const isLoading = ref<boolean>(false);
  const error = ref<ApiError | null>(null);

  // ========== COMPUTED ==========

  const isAuthenticated = computed<boolean>(() => !!token.value && !!user.value);

  const isTokenExpired = computed<boolean>(() => {
    if (!tokenExpiresAt.value) return false;
    return Date.now() >= tokenExpiresAt.value;
  });

  // ========== ACTIONS ==========

  const setUser = (u: User | null): void => {
    user.value = u;
  };

  const setToken = (accessToken: string, refreshT?: string, expiresIn?: number): void => {
    token.value = accessToken;
    if (refreshT) {
      refreshToken.value = refreshT;
    }
    if (expiresIn) {
      tokenExpiresAt.value = Date.now() + expiresIn * 1000;
    }
    // Also save to localStorage for persistence
    if (typeof window !== 'undefined') {
      localStorage.setItem('auth-token', accessToken);
      if (refreshT) localStorage.setItem('auth-refresh-token', refreshT);
    }
  };

  const clearAuth = (): void => {
    user.value = null;
    token.value = null;
    refreshToken.value = null;
    tokenExpiresAt.value = null;
    error.value = null;
    if (typeof window !== 'undefined') {
      localStorage.removeItem('auth-token');
      localStorage.removeItem('auth-refresh-token');
    }
  };

  const setError = (err: ApiError | null): void => {
    error.value = err;
  };

  const login = async (
    email: string,
    password: string,
    rememberMe: boolean = false
  ): Promise<{ success: boolean; error?: ApiError }> => {
    isLoading.value = true;
    error.value = null;
    try {
      const { apiFetch } = useApi();
      const response: ApiResponse<{
        token: string;
        refreshToken: string;
        expiresIn: number;
        user: User;
      }> = await apiFetch('/auth/login', {
        method: 'POST',
        body: { email, password, rememberMe },
      });

      if (!response.success) {
        error.value = response.error;
        return { success: false, error: response.error || undefined };
      }

      // Update store with response data
      setToken(response.data!.token, response.data!.refreshToken, response.data!.expiresIn);
      setUser(response.data!.user);

      return { success: true };
    } catch (err: any) {
      const apiError: ApiError = err.data?.error || {
        code: 'SERVER_ERROR',
        message: 'حدث خطأ ما، يرجى المحاولة لاحقًا',
      };
      error.value = apiError;
      return { success: false, error: apiError };
    } finally {
      isLoading.value = false;
    }
  };

  const register = async (payload: {
    userType: 'customer' | 'contractor';
    firstName: string;
    lastName: string;
    phone: string;
    idNumber: string;
    city: string;
    district: string;
    address: string;
    email: string;
    password: string;
  }): Promise<{ success: boolean; error?: ApiError }> => {
    isLoading.value = true;
    error.value = null;
    try {
      const { apiFetch } = useApi();
      const response: ApiResponse<{ verificationToken: string }> = await apiFetch(
        '/auth/register',
        {
          method: 'POST',
          body: payload,
        }
      );

      if (!response.success) {
        error.value = response.error;
        return { success: false, error: response.error || undefined };
      }

      // Store verification token temporarily for email verification page
      if (typeof window !== 'undefined') {
        sessionStorage.setItem('verification-token', response.data!.verificationToken);
        sessionStorage.setItem('verification-email', payload.email);
      }

      return { success: true };
    } catch (err: any) {
      const apiError: ApiError = err.data?.error || {
        code: 'SERVER_ERROR',
        message: 'حدث خطأ في التسجيل، يرجى المحاولة لاحقًا',
      };
      error.value = apiError;
      return { success: false, error: apiError };
    } finally {
      isLoading.value = false;
    }
  };

  const logout = (): void => {
    clearAuth();
  };

  const refreshAccessToken = async (): Promise<{ success: boolean }> => {
    try {
      const { apiFetch } = useApi();
      const response: ApiResponse<{ token: string; expiresIn: number }> = await apiFetch(
        '/auth/refresh',
        {
          method: 'POST',
        }
      );

      if (!response.success) {
        clearAuth();
        return { success: false };
      }

      setToken(response.data!.token, undefined, response.data!.expiresIn);
      return { success: true };
    } catch (err) {
      clearAuth();
      return { success: false };
    }
  };

  // ========== INITIALIZE (Hydrate from localStorage) ==========

  const initialize = (): void => {
    if (typeof window === 'undefined') return;
    const savedToken = localStorage.getItem('auth-token');
    if (savedToken) {
      token.value = savedToken;
      // In real app, also fetch user from /api/v1/user/profile to verify token validity
    }
  };

  onMounted(() => {
    initialize();
  });

  // ========== RETURN ==========

  return {
    user,
    token,
    refreshToken,
    isAuthenticated,
    isTokenExpired,
    isLoading,
    error,
    setUser,
    setToken,
    clearAuth,
    setError,
    login,
    register,
    logout,
    refreshAccessToken,
    initialize,
  };
});
```

---

### 1.2 Register Form State Store (Temporary)

**File:** `frontend/stores/register.ts`

Holds multi-step form data until final submission:

```typescript
import { defineStore } from 'pinia';

export interface RegisterFormData {
  step: 1 | 2 | 3 | 4;
  userType: 'customer' | 'contractor' | null;
  firstName: string;
  lastName: string;
  phone: string;
  idNumber: string;
  city: string;
  district: string;
  address: string;
  email: string;
  password: string;
  confirmPassword: string;
}

export const useRegisterStore = defineStore('register', () => {
  const form = ref<RegisterFormData>({
    step: 1,
    userType: null,
    firstName: '',
    lastName: '',
    phone: '',
    idNumber: '',
    city: '',
    district: '',
    address: '',
    email: '',
    password: '',
    confirmPassword: '',
  });

  const setStep = (stepNumber: 1 | 2 | 3 | 4): void => {
    form.value.step = stepNumber;
  };

  const updateField = (fieldName: keyof RegisterFormData, value: any): void => {
    form.value[fieldName] = value;
  };

  const updateFields = (fields: Partial<RegisterFormData>): void => {
    Object.assign(form.value, fields);
  };

  const reset = (): void => {
    form.value = {
      step: 1,
      userType: null,
      firstName: '',
      lastName: '',
      phone: '',
      idNumber: '',
      city: '',
      district: '',
      address: '',
      email: '',
      password: '',
      confirmPassword: '',
    };
  };

  const getPayload = (): Omit<RegisterFormData, 'step' | 'confirmPassword'> => ({
    userType: form.value.userType!,
    firstName: form.value.firstName,
    lastName: form.value.lastName,
    phone: form.value.phone,
    idNumber: form.value.idNumber,
    city: form.value.city,
    district: form.value.district,
    address: form.value.address,
    email: form.value.email,
    password: form.value.password,
  });

  return { form, setStep, updateField, updateFields, reset, getPayload };
});
```

---

## 2. Validation Schemas (Zod)

### 2.1 Auth Schemas Composable

**File:** `frontend/composables/useAuthSchemas.ts`

All validation schemas centralized in one composable:

```typescript
import { z } from 'zod';
import { toTypedSchema } from '@vee-validate/zod';

export const useAuthSchemas = () => {
  // ========== BASE SCHEMAS ==========

  const emailSchema = z
    .string()
    .min(1, { message: 'البريد الإلكتروني مطلوب' })
    .email({ message: 'البريد الإلكتروني غير صالح' });

  const passwordSchema = z
    .string()
    .min(8, { message: 'كلمة المرور يجب أن تكون 8 أحرف على الأقل' })
    .regex(/[A-Z]/, { message: 'يجب أن تحتوي على حرف كبير واحد على الأقل (A-Z)' })
    .regex(/[0-9]/, { message: 'يجب أن تحتوي على رقم واحد على الأقل (0-9)' })
    .regex(/[!@#$%^&*()_+=\[\]{};:'",.<>?/]/, {
      message: 'يجب أن تحتوي على رمز خاص واحد على الأقل (!@#$%^&*)',
    });

  const phoneSchema = z.string().regex(/^\d{9,15}$/, { message: 'رقم الهاتف غير صالح' });

  // ========== LOGIN SCHEMA ==========

  const loginSchema = z.object({
    email: emailSchema,
    password: z.string().min(8, { message: 'كلمة المرور مطلوبة' }),
    rememberMe: z.boolean().default(false),
  });
  const loginValidationSchema = toTypedSchema(loginSchema);

  // ========== REGISTER SCHEMAS (4 STEPS) ==========

  const registerStepSchemas = {
    1: toTypedSchema(
      z.object({
        userType: z.enum(['customer', 'contractor'], {
          message: 'يجب اختيار نوع حساب',
        }),
      })
    ),

    2: toTypedSchema(
      z.object({
        firstName: z
          .string()
          .min(2, { message: 'الاسم الأول يجب أن يكون حرفين على الأقل' })
          .max(50, { message: 'الاسم الأول طويل جدًا' }),
        lastName: z
          .string()
          .min(2, { message: 'الاسم الأخير يجب أن يكون حرفين على الأقل' })
          .max(50, { message: 'الاسم الأخير طويل جدًا' }),
        phone: phoneSchema,
        idNumber: z
          .string()
          .min(10, { message: 'رقم الهوية/جواز السفر غير صالح' })
          .max(30, { message: 'رقم الهوية/جواز السفر طويل جدًا' }),
      })
    ),

    3: toTypedSchema(
      z.object({
        city: z.string().min(2, { message: 'المدينة مطلوبة' }),
        district: z.string().min(2, { message: 'الحي/المنطقة مطلوبة' }),
        address: z
          .string()
          .min(5, { message: 'العنوان يجب أن يكون أطول' })
          .max(200, { message: 'العنوان طويل جدًا' }),
      })
    ),

    4: toTypedSchema(
      z
        .object({
          email: emailSchema,
          password: passwordSchema,
          confirmPassword: z.string(),
        })
        .refine((data) => data.password === data.confirmPassword, {
          message: 'كلمات المرور غير متطابقة',
          path: ['confirmPassword'],
        })
    ),
  };

  const registerFullSchema = toTypedSchema(
    z
      .object({
        userType: z.enum(['customer', 'contractor']),
        firstName: z.string().min(2).max(50),
        lastName: z.string().min(2).max(50),
        phone: phoneSchema,
        idNumber: z.string().min(10).max(30),
        city: z.string().min(2),
        district: z.string().min(2),
        address: z.string().min(5).max(200),
        email: emailSchema,
        password: passwordSchema,
        confirmPassword: z.string(),
      })
      .refine((data) => data.password === data.confirmPassword, {
        message: 'كلمات المرور غير متطابقة',
        path: ['confirmPassword'],
      })
  );

  // ========== FORGOT PASSWORD SCHEMA ==========

  const forgotPasswordSchema = toTypedSchema(
    z.object({
      email: emailSchema,
    })
  );

  // ========== RESET PASSWORD SCHEMA ==========

  const resetPasswordSchema = toTypedSchema(
    z
      .object({
        password: passwordSchema,
        confirmPassword: z.string(),
      })
      .refine((data) => data.password === data.confirmPassword, {
        message: 'كلمات المرور غير متطابقة',
        path: ['confirmPassword'],
      })
  );

  // ========== EMAIL VERIFICATION SCHEMA ==========

  const verifyEmailSchema = toTypedSchema(
    z.object({
      code: z
        .string()
        .length(6, { message: 'الكود يجب أن يكون 6 أرقام' })
        .regex(/^[0-9]+$/, { message: 'الكود يجب أن يحتوي على أرقام فقط' }),
    })
  );

  // ========== PROFILE SCHEMA ==========

  const profileSchema = toTypedSchema(
    z.object({
      firstName: z.string().min(2, { message: 'الاسم الأول يجب أن يكون حرفين على الأقل' }).max(50),
      lastName: z.string().min(2).max(50),
      phone: phoneSchema,
      city: z.string().optional(),
      district: z.string().optional(),
      address: z.string().min(5).max(200).optional(),
      languagePreference: z.enum(['ar', 'en']).optional(),
    })
  );

  // ========== CHANGE PASSWORD SCHEMA ==========

  const changePasswordSchema = toTypedSchema(
    z
      .object({
        currentPassword: z.string().min(1, { message: 'كلمة المرور الحالية مطلوبة' }),
        newPassword: passwordSchema,
        confirmPassword: z.string(),
      })
      .refine((data) => data.newPassword === data.confirmPassword, {
        message: 'كلمات المرور الجديدة غير متطابقة',
        path: ['confirmPassword'],
      })
  );

  // ========== RETURN ALL SCHEMAS ==========

  return {
    loginSchema,
    loginValidationSchema,
    registerStepSchemas,
    registerFullSchema,
    forgotPasswordSchema,
    resetPasswordSchema,
    verifyEmailSchema,
    profileSchema,
    changePasswordSchema,
  };
};
```

---

## 3. Component Data Flow

### 3.1 Login Page Data Flow

```
┌─────────────────────────────────────────────────────────────────┐
│ pages/auth/login.vue                                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  <script setup>                                                   │
│    const { loginValidationSchema } = useAuthSchemas()            │
│    const { values, errors, validate } = useForm({               │
│      validationSchema: loginValidationSchema,                    │
│    })                                                             │
│    const auth = useAuthStore()                                   │
│                                                                   │
│    const onSubmit = async () => {                                │
│      const isValid = await validate()                            │
│      if (!isValid) return                                         │
│                                                                   │
│      const { success, error } = await auth.login(                │
│        values.email,                                              │
│        values.password,                                           │
│        values.rememberMe                                          │
│      )                                                            │
│      if (success) {                                               │
│        await navigateTo('/dashboard')                            │
│      } else {                                                     │
│        showAlert(error?.message)  // Backend error message       │
│      }                                                            │
│    }                                                              │
│                                                                   │
│  <template>                                                       │
│    <UFormGroup :error="errors.email">                           │
│      <UInput v-model="values.email" type="email" />              │
│    </UFormGroup>                                                  │
│    <!-- ... more fields ... -->                                  │
│    <UButton @click="onSubmit">تسجيل الدخول</UButton>              │
│  </template>                                                      │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

**Data Flow:** User input → VeeValidate form state → Zod validation → useAuth.login() → Pinia store → localStorage + redirect

---

### 3.2 Register Wizard Data Flow

```
┌──────────────────────────────────────────────────────────┐
│ pages/auth/register.vue                                   │
├──────────────────────────────────────────────────────────┤
│                                                            │
│  <script setup>                                            │
│    const { registerStepSchemas } = useAuthSchemas()      │
│    const register = useRegisterStore()                    │
│    const auth = useAuthStore()                            │
│    const currentStep = ref(1)                             │
│                                                            │
│    const { values, errors, validate } = useForm({        │
│      validationSchema: registerStepSchemas[currentStep], │
│    })                                                      │
│                                                            │
│    const goToNextStep = async () => {                     │
│      const isValid = await validate()                     │
│      if (!isValid) return                                 │
│      register.updateFields(                               │
│        Object.fromEntries(                                │
│          Object.entries(values).filter(([k]) =>          │
│            isStepField(currentStep, k)                    │
│          )                                                 │
│        )                                                   │
│      )                                                     │
│      currentStep.value++                                  │
│    }                                                       │
│                                                            │
│    const onFinalSubmit = async () => {                    │
│      const { success } = await auth.register(             │
│        register.getPayload()                              │
│      )                                                     │
│      if (success) {                                        │
│        await navigateTo('/auth/verify-email')            │
│      }                                                     │
│    }                                                       │
│                                                            │
│  <template>                                               │
│    <USteppers :items="[...]" :ui-step="currentStep" />   │
│    <!-- Step 1: userType radio -->                        │
│    <!-- Step 2: personal info -->                         │
│    <!-- Step 3: address -->                               │
│    <!-- Step 4: email+password -->                        │
│  </template>                                              │
│                                                            │
└──────────────────────────────────────────────────────────┘
```

**Data Flow:** Per-step input → temp Pinia store → validate per step → advance → final submit → auth.register() → verification token → redirect

---

## 4. Component Hierarchy & Props/Emits

### 4.1 AuthCard Component

```typescript
// components/Auth/AuthCard.vue
<script setup lang="ts">
  interface Props {
    title: string
    subtitle?: string
  }

  defineProps<Props>()
</script>

<template>
  <div class="min-h-screen flex items-center justify-center px-4">
    <UCard class="w-full max-w-md">
      <template #header>
        <div>
          <h2 class="text-[#171717] font-semibold text-2xl tracking-[-0.96px]">
            {{ title }}
          </h2>
          <p v-if="subtitle" class="text-gray-600 text-sm mt-2">
            {{ subtitle }}
          </p>
        </div>
      </template>
      <slot />
    </UCard>
  </div>
</template>
```

**Props:**

- `title: string` — Page title (e.g., "تسجيل الدخول")
- `subtitle?: string` — Optional subtitle/description

**Slots:**

- default — Form content

---

### 4.2 PasswordStrength Component

```typescript
// components/Auth/PasswordStrength.vue
<script setup lang="ts">
  interface Props {
    password: string
    showLabel?: boolean
  }

  const props = withDefaults(defineProps<Props>(), {
    showLabel: true,
  })

  const strength = computed(() => {
    if (!props.password) return 0
    let score = 0
    if (props.password.length >= 8) score += 25
    if (/[a-z]/.test(props.password)) score += 25
    if (/[A-Z]/.test(props.password)) score += 25
    if (/[0-9]/.test(props.password)) score += 25
    if (/[!@#$%^&*()_+=]/.test(props.password)) score += 25
    return Math.min(score, 100)
  })

  const strengthLabel = computed(() => {
    const s = strength.value
    if (s === 0) return { ar: '', en: '' }
    if (s <= 25) return { ar: 'ضعيفة', en: 'Weak' }
    if (s <= 50) return { ar: 'متوسطة', en: 'Fair' }
    if (s <= 75) return { ar: 'جيدة', en: 'Good' }
    return { ar: 'قوية', en: 'Strong' }
  })

  const strengthColor = computed(() => {
    const s = strength.value
    if (s === 0) return 'gray'
    if (s <= 25) return 'red'
    if (s <= 50) return 'yellow'
    if (s <= 75) return undefined // default gray
    return 'green'
  })
</script>

<template>
  <div v-if="password" class="space-y-2">
    <UProgress :value="strength" :color="strengthColor" />
    <p v-if="showLabel" class="text-xs text-gray-500">
      {{ $t('auth.password_strength_' + strengthLabel.ar.toLowerCase()) }}
    </p>
  </div>
</template>
```

**Props:**

- `password: string` — Current password input
- `showLabel?: boolean` — Show strength label (default: true)

**Computed:**

- `strength: 0-100` — Strength score
- `strengthLabel: { ar, en }` — Localized label
- `strengthColor: string` — Color for progress bar

---

### 4.3 OTP Input Component (UPinInput)

Nuxt UI provides native `UPinInput` component:

```vue
<UPinInput v-model="code" size="6" @complete="onCodeComplete" />
```

**Props:**

- `v-model: string` — 6-digit code
- `size: number` — Number of input boxes
- `disabled?: boolean` — Disable input

**Events:**

- `@complete` — Fired when all 6 digits entered

---

## 5. Composable Data Structures

### 5.1 useAuth Composable

```typescript
// frontend/composables/useAuth.ts
export interface UseAuthReturn {
  isLoading: Ref<boolean>;
  error: Ref<ApiError | null>;

  login: (
    email: string,
    password: string,
    rememberMe?: boolean
  ) => Promise<{ success: boolean; error?: ApiError }>;
  register: (payload: RegisterPayload) => Promise<{ success: boolean; error?: ApiError }>;
  logout: () => void;
  verifyEmail: (code: string) => Promise<{ success: boolean; error?: ApiError }>;
  resendVerificationCode: (email: string) => Promise<{ success: boolean; error?: ApiError }>;
  requestPasswordReset: (email: string) => Promise<{ success: boolean; error?: ApiError }>;
  resetPassword: (
    token: string,
    password: string
  ) => Promise<{ success: boolean; error?: ApiError }>;
  updateProfile: (data: ProfileUpdate) => Promise<{ success: boolean; error?: ApiError }>;
  changePassword: (
    current: string,
    newPassword: string
  ) => Promise<{ success: boolean; error?: ApiError }>;
  uploadAvatar: (file: File) => Promise<{ success: boolean; avatarUrl?: string; error?: ApiError }>;
}

export const useAuth = (): UseAuthReturn => {
  const auth = useAuthStore();
  const { apiFetch } = useApi();

  const isLoading = computed(() => auth.isLoading);
  const error = computed(() => auth.error);

  const login = async (email: string, password: string, rememberMe = false) => {
    // ... implementation
  };

  // ... other methods

  return {
    isLoading,
    error,
    login,
    register,
    logout,
    verifyEmail,
    resendVerificationCode,
    requestPasswordReset,
    resetPassword,
    updateProfile,
    changePassword,
    uploadAvatar,
  };
};
```

---

### 5.2 usePasswordToggle Composable

```typescript
// frontend/composables/usePasswordToggle.ts
export interface UsePasswordToggleReturn {
  isVisible: Ref<boolean>;
  type: ComputedRef<'password' | 'text'>;
  toggle: () => void;
}

export const usePasswordToggle = (): UsePasswordToggleReturn => {
  const isVisible = ref(false);

  const type = computed(() => (isVisible.value ? 'text' : 'password'));

  const toggle = () => {
    isVisible.value = !isVisible.value;
  };

  return { isVisible, type, toggle };
};
```

---

## 6. API Response Types

### 6.1 Login Response

```typescript
interface LoginResponse {
  success: true;
  data: {
    token: string; // Access token (JWT or Sanctum)
    refreshToken: string; // Refresh token (for token renewal)
    expiresIn: number; // Token expiry in seconds (e.g., 900 for 15 min)
    tokenType: 'Bearer';
    user: User;
  };
  error: null;
}

// Or error response:
interface LoginErrorResponse {
  success: false;
  data: null;
  error: {
    code: 'AUTH_INVALID_CREDENTIALS' | 'RATE_LIMIT_EXCEEDED' | 'SERVER_ERROR';
    message: string;
  };
}
```

### 6.2 Register Response

```typescript
interface RegisterResponse {
  success: true;
  data: {
    verificationToken: string; // For email verification step
    redirectTo: '/auth/verify-email';
  };
  error: null;
}
```

### 6.3 Verify Email Response

```typescript
interface VerifyEmailResponse {
  success: true;
  data: {
    redirectTo: '/dashboard';
    message: 'تم التحقق من بريدك الإلكتروني';
  };
  error: null;
}
```

### 6.4 Profile Response

```typescript
interface ProfileResponse {
  success: true;
  data: User;
  error: null;
}
```

---

## 7. Locale File Structure

**File:** `frontend/locales/ar.json`

```json
{
  "auth": {
    "login": {
      "title": "تسجيل الدخول",
      "subtitle": "أدخل بيانات حسابك للمتابعة",
      "email_label": "البريد الإلكتروني",
      "email_placeholder": "example@email.com",
      "password_label": "كلمة المرور",
      "password_placeholder": "••••••••",
      "show_password": "إظهار",
      "hide_password": "إخفاء",
      "remember_me": "تذكرني",
      "submit": "تسجيل الدخول",
      "forgot_password_link": "نسيت كلمة المرور؟",
      "no_account_link": "ليس لديك حساب؟ سجل الآن"
    },
    "register": {
      "title": "إنشاء حساب جديد",
      "step_1": {
        "title": "نوع الحساب",
        "subtitle": "اختر نوع الحساب الذي يناسبك",
        "customer": "أنا أبحث عن خدمات البناء",
        "contractor": "أنا مقاول / مقدم خدمات"
      },
      "step_2": {
        "title": "المعلومات الشخصية",
        "first_name": "الاسم الأول",
        "last_name": "الاسم الأخير",
        "phone": "رقم الهاتف",
        "id_number": "رقم الهوية / جواز السفر"
      },
      "step_3": {
        "title": "معلومات العنوان",
        "city": "المدينة",
        "district": "الحي / المنطقة",
        "address": "العنوان التفصيلي"
      },
      "step_4": {
        "title": "البريد الإلكتروني وكلمة المرور",
        "email": "البريد الإلكتروني",
        "password": "كلمة المرور",
        "confirm_password": "تأكيد كلمة المرور",
        "submit": "إنشاء الحساب"
      }
    },
    "password_strength": {
      "weak": "ضعيفة",
      "fair": "متوسطة",
      "good": "جيدة",
      "strong": "قوية"
    },
    "errors": {
      "invalid_credentials": "البريد الإلكتروني أو كلمة المرور خاطئة",
      "email_required": "البريد الإلكتروني مطلوب",
      "email_invalid": "البريد الإلكتروني غير صالح",
      "password_required": "كلمة المرور مطلوبة",
      "password_too_short": "كلمة المرور يجب أن تكون 8 أحرف على الأقل",
      "password_uppercase": "يجب أن تحتوي على حرف كبير (A-Z)",
      "password_number": "يجب أن تحتوي على رقم (0-9)",
      "password_symbol": "يجب أن تحتوي على رمز خاص (!@#$%^&*)",
      "password_mismatch": "كلمات المرور غير متطابقة",
      "email_exists": "هذا البريد الإلكتروني مسجل بالفعل",
      "token_expired": "الرابط منتهي الصلاحية",
      "code_expired": "انتهت صلاحية الكود",
      "code_invalid": "الكود غير صالح",
      "rate_limit": "تم تجاوز الحد المسموح. حاول بعد 60 ثانية",
      "server_error": "حدث خطأ ما. يرجى المحاولة لاحقًا"
    }
  },
  "profile": {
    "title": "الملف الشخصي",
    "edit_profile": "تعديل الملف الشخصي",
    "change_password": "تغيير كلمة المرور",
    "upload_avatar": "تحديث الصورة الشخصية",
    "save": "حفظ",
    "cancel": "إلغاء",
    "success": "تم الحفظ بنجاح",
    "avatar_upload_success": "تم تحديث الصورة الشخصية",
    "avatar_upload_error": "فشل تحميل الصورة"
  }
}
```

---

## 8. TypeScript Type Definitions

**File:** `frontend/types/auth.ts`

```typescript
export type UserRole = 'customer' | 'contractor' | 'supervisor' | 'engineer' | 'admin';

export interface User {
  id: string;
  email: string;
  firstName: string;
  lastName: string;
  phone: string;
  idNumber: string;
  userType: UserRole;
  city: string;
  district: string;
  address: string;
  avatarUrl: string | null;
  emailVerified: boolean;
  createdAt: ISO8601String;
  updatedAt: ISO8601String;
}

export interface AuthToken {
  accessToken: string;
  refreshToken: string;
  expiresAt: number;
  tokenType: 'Bearer';
}

export interface ApiError {
  code: ErrorCode;
  message: string;
  details?: Record<string, string[]> | null;
}

export type ErrorCode =
  | 'AUTH_INVALID_CREDENTIALS'
  | 'AUTH_TOKEN_EXPIRED'
  | 'AUTH_UNAUTHORIZED'
  | 'VALIDATION_ERROR'
  | 'CONFLICT_ERROR'
  | 'RESOURCE_NOT_FOUND'
  | 'RATE_LIMIT_EXCEEDED'
  | 'WORKFLOW_PREREQUISITES_UNMET'
  | 'SERVER_ERROR';

export interface ApiResponse<T> {
  success: boolean;
  data: T | null;
  error: ApiError | null;
}

export interface LoginPayload {
  email: string;
  password: string;
  rememberMe?: boolean;
}

export interface RegisterPayload {
  userType: 'customer' | 'contractor';
  firstName: string;
  lastName: string;
  phone: string;
  idNumber: string;
  city: string;
  district: string;
  address: string;
  email: string;
  password: string;
}

export interface ProfileUpdate {
  firstName?: string;
  lastName?: string;
  phone?: string;
  city?: string;
  district?: string;
  address?: string;
  languagePreference?: 'ar' | 'en';
}

export interface ChangePasswordPayload {
  currentPassword: string;
  newPassword: string;
}

export type ISO8601String = string & { readonly __brand: 'ISO8601String' };
```

---

## Summary

**Total Zod Schemas:** 10  
**Total Pinia Stores:** 2 (auth + register wizard)  
**Total Composables:** 4+ (useAuth, useApi, useAuthSchemas, usePasswordToggle)  
**Total Component Props Types:** 3+ (AuthCard, PasswordStrength, etc.)  
**Locale Keys:** 60+  
**TypeScript interfaces:** 12+

**Data model fully specified and ready for implementation** ✓
