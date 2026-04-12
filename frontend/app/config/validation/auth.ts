import { z } from 'zod';

const saudiPhoneRegex = /^(\+9665|05)\d{8}$/;

export const loginSchema = z.object({
    email: z.string().email(),
    password: z.string().min(1),
});

export const registerSchema = z
    .object({
        name: z.string().min(1).max(255),
        email: z.string().email(),
        phone: z.string().regex(saudiPhoneRegex),
        password: z.string().min(8),
        password_confirmation: z.string().min(8),
        role: z.enum(['customer', 'contractor', 'supervising_architect', 'field_engineer']),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: 'Passwords do not match',
        path: ['password_confirmation'],
    });

export const forgotPasswordSchema = z.object({
    email: z.string().email(),
});

export const resetPasswordSchema = z
    .object({
        email: z.string().email(),
        token: z.string().min(1),
        password: z.string().min(8),
        password_confirmation: z.string().min(8),
    })
    .refine((data) => data.password === data.password_confirmation, {
        message: 'Passwords do not match',
        path: ['password_confirmation'],
    });

export type LoginFormData = z.infer<typeof loginSchema>;
export type RegisterFormData = z.infer<typeof registerSchema>;
export type ForgotPasswordFormData = z.infer<typeof forgotPasswordSchema>;
export type ResetPasswordFormData = z.infer<typeof resetPasswordSchema>;
