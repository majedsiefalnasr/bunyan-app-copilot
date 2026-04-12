// frontend/composables/useAuth.ts
import { storeToRefs } from 'pinia';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';
import type { AuthUser } from '../types/index';
import { useApi } from './useApi';

interface AuthResponse {
    success: boolean;
    data: {
        user: AuthUser;
        token: string;
    };
}

interface ApiSuccessResponse {
    success: boolean;
    data: unknown;
}

export function useAuth() {
    const store = useAuthStore();
    const { locale } = useI18n();
    const { apiFetch } = useApi();

    const { user, isAuthenticated, role, isLoading } = storeToRefs(store);

    async function login(email: string, password: string): Promise<AuthUser> {
        store.isLoading = true;
        try {
            const response = await apiFetch<AuthResponse>('/api/v1/auth/login', {
                method: 'POST',
                body: { email, password },
            });
            store.setToken(response.data.token);
            store.setUser(response.data.user);
            return response.data.user;
        } finally {
            store.isLoading = false;
        }
    }

    async function register(data: {
        name: string;
        email: string;
        phone: string;
        password: string;
        password_confirmation: string;
        role: string;
    }): Promise<AuthUser> {
        store.isLoading = true;
        try {
            const response = await apiFetch<AuthResponse>('/api/v1/auth/register', {
                method: 'POST',
                body: data,
            });
            store.setToken(response.data.token);
            store.setUser(response.data.user);
            return response.data.user;
        } finally {
            store.isLoading = false;
        }
    }

    /**
     * Logout: fires POST to API, then ALWAYS clears auth state and redirects.
     * API failure is intentionally ignored — store is cleared in finally block.
     */
    async function logout() {
        store.isLoading = true;
        try {
            await apiFetch('/api/v1/auth/logout', { method: 'POST' });
        } catch {
            // Intentionally ignored — user is logged out locally regardless of API response
        } finally {
            store.isLoading = false;
            store.clearAuth();
            await navigateTo(`/${locale.value}/auth/login`);
        }
    }

    async function forgotPassword(email: string): Promise<void> {
        store.isLoading = true;
        try {
            await apiFetch<ApiSuccessResponse>('/api/v1/auth/forgot-password', {
                method: 'POST',
                body: { email },
            });
        } finally {
            store.isLoading = false;
        }
    }

    async function resetPassword(data: {
        email: string;
        token: string;
        password: string;
        password_confirmation: string;
    }): Promise<void> {
        store.isLoading = true;
        try {
            await apiFetch<ApiSuccessResponse>('/api/v1/auth/reset-password', {
                method: 'POST',
                body: data,
            });
        } finally {
            store.isLoading = false;
        }
    }

    async function resendVerification(): Promise<void> {
        store.isLoading = true;
        try {
            await apiFetch<ApiSuccessResponse>('/api/v1/auth/email/resend', {
                method: 'POST',
            });
        } finally {
            store.isLoading = false;
        }
    }

    /**
     * Fetch current user profile from API. Called on app bootstrap.
     * If token exists but API call fails, store remains empty → guest state.
     */
    async function fetchCurrentUser(): Promise<AuthUser | null> {
        store.isLoading = true;
        try {
            const response = await apiFetch<{ success: boolean; data: AuthUser }>(
                '/api/v1/auth/user'
            );
            if (response.success && response.data) {
                store.setUser(response.data);
                return response.data;
            }
            return null;
        } catch {
            return null;
        } finally {
            store.isLoading = false;
        }
    }

    return {
        user: readonly(user) as typeof user,
        isAuthenticated,
        isLoading,
        role,
        hasRole: store.hasRole.bind(store),
        login,
        register,
        logout,
        forgotPassword,
        resetPassword,
        resendVerification,
        fetchCurrentUser,
    };
}
