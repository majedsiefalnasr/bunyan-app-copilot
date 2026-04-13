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

// Additional minimal ambient declarations for auth composables and validation
declare module '~app/config/validation/auth' {
    export const loginSchema: any;
    export const registerSchema: any;
    export const resetPasswordSchema: any;
    export const forgotPasswordSchema: any;
    export type LoginFormData = any;
    export type RegisterFormData = any;
    export type ResetPasswordFormData = any;
}

declare function useAuth(): any;
declare function useAuthStore(): any;
declare function definePageMeta(meta?: any): any;
declare function useLocalePath(...args: any[]): any;

declare const route: any;

declare global {
    interface PageMeta {
        middleware?: any;
    }
}

// Wildcard module to satisfy imports like '~/app/...' or similar aliases
declare module '~/*' {
    const value: any;
    export default value;
}

// Provide minimal '#imports' module types for Nuxt auto-imported symbols
declare module '#imports' {
    export function useAuth(...args: any[]): any;
    export function useAuthStore(...args: any[]): any;
    export function useRoute(...args: any[]): any;
}
