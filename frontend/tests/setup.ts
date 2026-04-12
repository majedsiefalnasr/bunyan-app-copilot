import { config } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { vi } from 'vitest';
import {
    reactive,
    ref,
    computed,
    watch,
    onMounted,
    onUnmounted,
    nextTick,
    toRef,
    toRefs,
} from 'vue';

// Stub global navigateTo function (Nuxt auto-import)
vi.stubGlobal('navigateTo', vi.fn());

// Stub Vue auto-imports (Nuxt auto-imports these globally)
vi.stubGlobal('reactive', reactive);
vi.stubGlobal('ref', ref);
vi.stubGlobal('computed', computed);
vi.stubGlobal('watch', watch);
vi.stubGlobal('onMounted', onMounted);
vi.stubGlobal('onUnmounted', onUnmounted);
vi.stubGlobal('nextTick', nextTick);
vi.stubGlobal('toRef', toRef);
vi.stubGlobal('toRefs', toRefs);

// Stub Nuxt composables
vi.stubGlobal('definePageMeta', vi.fn());
vi.stubGlobal('useLocalePath', () => (path: string) => path);

// Mock i18n messages
const messages = {
    en: {
        errors: {
            AUTH_INVALID_CREDENTIALS: 'Invalid credentials',
            AUTH_UNAUTHORIZED: 'Unauthorized',
            AUTH_TOKEN_EXPIRED: 'Token expired',
            VALIDATION_ERROR: 'Validation error',
            RBAC_ROLE_DENIED: 'Role denied',
            RESOURCE_NOT_FOUND: 'Resource not found',
            WORKFLOW_INVALID_TRANSITION: 'Invalid workflow transition',
            WORKFLOW_PREREQUISITES_UNMET: 'Prerequisites not met',
            PAYMENT_FAILED: 'Payment failed',
            CONFLICT_ERROR: 'Conflict error',
            RATE_LIMIT_EXCEEDED: 'Rate limit exceeded',
            SERVER_ERROR: 'Server error',
        },
    },
    ar: {
        errors: {
            AUTH_INVALID_CREDENTIALS: 'بيانات اعتماد غير صحيحة',
            AUTH_UNAUTHORIZED: 'غير مصرح',
            AUTH_TOKEN_EXPIRED: 'انتهت صلاحية الرمز',
            VALIDATION_ERROR: 'خطأ في التحقق',
            RBAC_ROLE_DENIED: 'الدور مرفوض',
            RESOURCE_NOT_FOUND: 'المورد غير موجود',
            WORKFLOW_INVALID_TRANSITION: 'انتقال سير عمل غير صالح',
            WORKFLOW_PREREQUISITES_UNMET: 'المتطلبات غير مستوفاة',
            PAYMENT_FAILED: 'فشل الدفع',
            CONFLICT_ERROR: 'خطأ في التضارب',
            RATE_LIMIT_EXCEEDED: 'تم تجاوز حد المعدل',
            SERVER_ERROR: 'خطأ في الخادم',
        },
    },
};

export const i18n = createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages,
    missingWarn: false,
    fallbackWarn: false,
});

// Mock router
export const mockRouter = {
    push: vi.fn(),
    replace: vi.fn(),
    go: vi.fn(),
    back: vi.fn(),
    forward: vi.fn(),
};

export const createTestRouter = () => ({
    ...mockRouter,
    currentRoute: {
        value: {
            path: '/',
            name: 'home',
            params: {},
            query: {},
        },
    },
});

// Configure Vue Test Utils global mocks
config.global.mocks = {
    $t: (key: string, fallback: string = key): string => {
        const parts = key.split('.');
        let value: Record<string, unknown> | string | undefined = messages.en;
        for (const part of parts) {
            if (typeof value === 'object' && value !== null) {
                value = (value as Record<string, unknown>)[part] as
                    | Record<string, unknown>
                    | string
                    | undefined;
            } else {
                return fallback;
            }
            if (!value) return fallback;
        }
        return typeof value === 'string' ? value : fallback;
    },
};
