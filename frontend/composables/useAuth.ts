// frontend/composables/useAuth.ts
import { storeToRefs } from 'pinia';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '../stores/auth';
import type { AuthUser } from '../types/index';
import { useApi } from './useApi';

export function useAuth() {
    const store = useAuthStore();
    const { locale } = useI18n();
    const { apiFetch } = useApi();

    const { user, isAuthenticated, role } = storeToRefs(store);

    /**
     * Logout: fires DELETE to API, then ALWAYS clears auth state and redirects.
     * API failure is intentionally ignored — store is cleared in finally block.
     */
    async function logout() {
        try {
            await apiFetch('/api/v1/auth/logout', { method: 'DELETE' });
        } catch {
            // Intentionally ignored — user is logged out locally regardless of API response
        } finally {
            store.clearAuth();
            await navigateTo(`/${locale.value}/auth/login`);
        }
    }

    /**
     * Fetch current user profile from API. Called on app bootstrap.
     * If token exists but API call fails, store remains empty → guest state.
     */
    async function fetchCurrentUser(): Promise<AuthUser | null> {
        try {
            const response = await apiFetch<{ success: boolean; data: AuthUser }>(
                '/api/v1/auth/me'
            );
            if (response.success && response.data) {
                store.setUser(response.data);
                return response.data;
            }
            return null;
        } catch {
            return null;
        }
    }

    return {
        user: readonly(user) as typeof user,
        isAuthenticated,
        role,
        hasRole: store.hasRole.bind(store),
        logout,
        fetchCurrentUser,
    };
}
