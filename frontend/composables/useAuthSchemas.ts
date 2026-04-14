// frontend/composables/useAuthSchemas.ts
import { z } from 'zod';

// Password validation regex: 8+ chars with uppercase, lowercase, number, special char
const passwordRegex = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,128}$/;

export function useAuthSchemas() {
  /**
   * Login form schema
   */
  const loginSchema = z.object({
    email: z.string().min(1, 'البريد الإلكتروني مطلوب').email('البريد الإلكتروني غير صالح'),
    password: z
      .string()
      .min(1, 'كلمة المرور مطلوبة')
      .min(8, 'كلمة المرور يجب أن تكون 8 أحرف على الأقل'),
    rememberMe: z.boolean().default(false),
  });

  /**
   * Register Step 1: Account Type Selection
   */
  const registerStep1Schema = z.object({
    userType: z.enum(['customer', 'contractor'], {
      errorMap: () => ({ message: 'يرجى اختيار نوع الحساب' }),
    }),
  });

  /**
   * Register Step 2: Personal Information
   */
  const registerStep2Schema = z.object({
    firstName: z
      .string()
      .min(1, 'الاسم الأول مطلوب')
      .min(2, 'الاسم الأول يجب أن يكون حرفين على الأقل')
      .max(50, 'الاسم الأول طويل جداً'),
    lastName: z
      .string()
      .min(1, 'اسم العائلة مطلوب')
      .min(2, 'اسم العائلة يجب أن يكون حرفين على الأقل')
      .max(50, 'اسم العائلة طويل جداً'),
    phone: z
      .string()
      .min(1, 'رقم الهاتف مطلوب')
      .regex(/^\d{9,15}$/, 'رقم الهاتف يجب أن يكون بين 9 و 15 رقم'),
    idNumber: z
      .string()
      .min(1, 'رقم الهوية مطلوب')
      .min(10, 'رقم الهوية يجب أن يكون 10 أرقام على الأقل')
      .max(30, 'رقم الهوية طويل جداً'),
  });

  /**
   * Register Step 3: Address Information
   */
  const registerStep3Schema = z.object({
    city: z.string().min(1, 'المدينة مطلوبة'),
    district: z.string().min(1, 'الحي مطلوب'),
    address: z
      .string()
      .min(1, 'العنوان مطلوب')
      .min(5, 'العنوان يجب أن يكون 5 أحرف على الأقل')
      .max(200, 'العنوان طويل جداً'),
  });

  /**
   * Register Step 4: Email & Password
   */
  const registerStep4Schema = z
    .object({
      email: z.string().min(1, 'البريد الإلكتروني مطلوب').email('البريد الإلكتروني غير صالح'),
      password: z
        .string()
        .min(1, 'كلمة المرور مطلوبة')
        .min(8, 'كلمة المرور يجب أن تكون 8 أحرف على الأقل')
        .max(128, 'كلمة المرور طويلة جداً')
        .regex(passwordRegex, 'كلمة المرور يجب أن تحتوي على أحرف كبيرة وصغيرة وأرقام ورموز خاصة'),
      confirmPassword: z.string().min(1, 'تأكيد كلمة المرور مطلوب'),
    })
    .refine((data) => data.password === data.confirmPassword, {
      message: 'كلمات المرور غير متطابقة',
      path: ['confirmPassword'],
    });

  /**
   * Complete register schema (all 4 steps combined)
   */
  const registerSchema = z
    .object({
      userType: z.enum(['customer', 'contractor']),
      firstName: z.string().min(2).max(50),
      lastName: z.string().min(2).max(50),
      phone: z.string().regex(/^\d{9,15}$/),
      idNumber: z.string().min(10).max(30),
      city: z.string().min(1),
      district: z.string().min(1),
      address: z.string().min(5).max(200),
      email: z.string().email(),
      password: z.string().regex(passwordRegex),
      confirmPassword: z.string(),
    })
    .refine((data) => data.password === data.confirmPassword, {
      message: 'كلمات المرور غير متطابقة',
      path: ['confirmPassword'],
    });

  /**
   * Forgot password schema
   */
  const forgotPasswordSchema = z.object({
    email: z.string().min(1, 'البريد الإلكتروني مطلوب').email('البريد الإلكتروني غير صالح'),
  });

  /**
   * Reset password schema
   */
  const resetPasswordSchema = z
    .object({
      password: z
        .string()
        .min(1, 'كلمة المرور مطلوبة')
        .min(8, 'كلمة المرور يجب أن تكون 8 أحرف على الأقل')
        .max(128, 'كلمة المرور طويلة جداً')
        .regex(passwordRegex, 'كلمة المرور يجب أن تحتوي على أحرف كبيرة وصغيرة وأرقام ورموز خاصة'),
      confirmPassword: z.string().min(1, 'تأكيد كلمة المرور مطلوب'),
    })
    .refine((data) => data.password === data.confirmPassword, {
      message: 'كلمات المرور غير متطابقة',
      path: ['confirmPassword'],
    });

  /**
   * Email verification schema
   */
  const verifyEmailSchema = z.object({
    code: z
      .string()
      .length(6, 'الرمز يجب أن يكون 6 أرقام')
      .regex(/^\d+$/, 'الرمز يجب أن يحتوي على أرقام فقط'),
  });

  /**
   * Profile schema
   */
  const profileSchema = z.object({
    firstName: z
      .string()
      .min(1, 'الاسم الأول مطلوب')
      .min(2, 'الاسم الأول يجب أن يكون حرفين على الأقل')
      .max(50, 'الاسم الأول طويل جداً'),
    lastName: z
      .string()
      .min(1, 'اسم العائلة مطلوب')
      .min(2, 'اسم العائلة يجب أن يكون حرفين على الأقل')
      .max(50, 'اسم العائلة طويل جداً'),
    phone: z
      .string()
      .min(1, 'رقم الهاتف مطلوب')
      .regex(/^\d{9,15}$/, 'رقم الهاتف يجب أن يكون بين 9 و 15 رقم'),
    city: z.string().min(1, 'المدينة مطلوبة'),
    district: z.string().min(1, 'الحي مطلوب'),
    address: z
      .string()
      .min(1, 'العنوان مطلوب')
      .min(5, 'العنوان يجب أن يكون 5 أحرف على الأقل')
      .max(200, 'العنوان طويل جداً'),
    languagePreference: z.enum(['ar', 'en']).default('ar'),
  });

  /**
   * Change password schema
   */
  const changePasswordSchema = z
    .object({
      currentPassword: z.string().min(1, 'كلمة المرور الحالية مطلوبة'),
      password: z
        .string()
        .min(1, 'كلمة المرور الجديدة مطلوبة')
        .min(8, 'كلمة المرور يجب أن تكون 8 أحرف على الأقل')
        .max(128, 'كلمة المرور طويلة جداً')
        .regex(passwordRegex, 'كلمة المرور يجب أن تحتوي على أحرف كبيرة وصغيرة وأرقام ورموز خاصة'),
      confirmPassword: z.string().min(1, 'تأكيد كلمة المرور مطلوب'),
    })
    .refine((data) => data.password === data.confirmPassword, {
      message: 'كلمات المرور غير متطابقة',
      path: ['confirmPassword'],
    });

  return {
    loginSchema,
    registerStep1Schema,
    registerStep2Schema,
    registerStep3Schema,
    registerStep4Schema,
    registerSchema,
    forgotPasswordSchema,
    resetPasswordSchema,
    verifyEmailSchema,
    profileSchema,
    changePasswordSchema,
  };
}
