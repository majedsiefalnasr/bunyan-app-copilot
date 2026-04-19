/* eslint-disable @typescript-eslint/no-explicit-any */
// Ambient declarations to satisfy typecheck until Nuxt auto-generated types are available
declare function defineNuxtConfig<T = unknown>(config: T): T;
declare function useRoute(): any;
declare function useAsyncData<T = unknown>(
  key: string,
  fetcher: () => Promise<T> | T,
  opts?: unknown
): Promise<{ data: T }>;
// Simple fallback for queryCollection used by content pages
declare function queryCollection(name: string): any;
declare function createError(opts: unknown): unknown;
declare function ref<T = unknown>(value?: T): { value: T | undefined };

declare module '*.vue' {
  import type { DefineComponent } from 'vue';
  const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>;
  export default component;
}

// Auth validation schemas
declare module '~/config/validation/auth' {
  import type { z } from 'zod';
  export const loginSchema: z.ZodObject<any>;
  export const registerSchema: z.ZodObject<any>;
  export const resetPasswordSchema: z.ZodObject<any>;
  export const forgotPasswordSchema: z.ZodObject<any>;
  export type LoginFormData = any;
  export type RegisterFormData = any;
  export type ResetPasswordFormData = any;
  export type ForgotPasswordFormData = any;
}

// Category types
declare module '~types' {
  import type { Category, CategoryFormData } from '~/types';
  export type { Category, CategoryFormData };
}

declare function useAuth(): any;
declare function useAuthStore(): any;
declare function definePageMeta(meta?: any): any;
declare function useLocalePath(...args: any[]): any;
declare function useI18n(): any;

declare const route: any;

declare global {
  interface PageMeta {
    middleware?: any;
  }
}

// Provide minimal '#imports' module types for Nuxt auto-imported symbols
declare module '#imports' {
  export function useAuth(...args: any[]): any;
  export function useAuthStore(...args: any[]): any;
  export function useRoute(...args: any[]): any;
}
