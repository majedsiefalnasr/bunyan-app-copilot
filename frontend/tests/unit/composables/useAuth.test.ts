import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { readonly as vueReadonly, ref } from 'vue';
import { createPinia, setActivePinia } from 'pinia';

import { useAuth } from '../../../composables/useAuth';
import { useAuthStore } from '../../../stores/auth';
vi.stubGlobal('readonly', vueReadonly);

// Mock vue-i18n — explicitly imported by useAuth.ts
vi.mock('vue-i18n', () => ({
    useI18n: () => ({ locale: ref('ar') }),
}));

// useApi spy — captured here so we can configure per-test response
const mockApiFetch = vi.fn();
vi.mock('../../../composables/useApi', () => ({
    useApi: () => ({ apiFetch: mockApiFetch }),
}));

describe('useAuth composable', () => {
    let cookieRef: ReturnType<typeof ref<string | null>>;

    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();

        // Stub useCookie BEFORE useAuthStore() is called — store factory runs on first use
        cookieRef = ref<string | null>(null);
        vi.stubGlobal('useCookie', vi.fn().mockReturnValue(cookieRef));
    });

    afterEach(() => {
        vi.clearAllMocks();
        // Re-stub globals that may have been cleared by clearAllMocks
        vi.stubGlobal('useCookie', vi.fn().mockReturnValue(cookieRef));
    });

    // ── Authentication state ────────────────────────────────────────────

    it('isAuthenticated is false when no user and no token', () => {
        const { isAuthenticated } = useAuth();
        expect(isAuthenticated.value).toBe(false);
    });

    it('isAuthenticated is false when token exists but user is null', () => {
        cookieRef.value = 'some-token';
        const { isAuthenticated } = useAuth();
        expect(isAuthenticated.value).toBe(false);
    });

    it('isAuthenticated is true when token and user are both set', () => {
        cookieRef.value = 'some-token';
        const { isAuthenticated } = useAuth();
        const store = useAuthStore();
        store.setUser({ id: 1, name: 'Alice', email: 'a@b.com', role: 'customer' } as never);
        expect(isAuthenticated.value).toBe(true);
    });

    // ── hasRole ─────────────────────────────────────────────────────────

    it('hasRole returns false when no user', () => {
        const { hasRole } = useAuth();
        expect(hasRole('customer')).toBe(false);
    });

    it('hasRole returns true when user role matches', () => {
        const { hasRole } = useAuth();
        const store = useAuthStore();
        store.setUser({ id: 1, name: 'Bob', email: 'b@c.com', role: 'contractor' } as never);
        expect(hasRole('contractor')).toBe(true);
        expect(hasRole('customer')).toBe(false);
    });

    it('hasRole accepts an array of roles', () => {
        const { hasRole } = useAuth();
        const store = useAuthStore();
        store.setUser({ id: 2, name: 'Carol', email: 'c@d.com', role: 'admin' } as never);
        expect(hasRole(['admin', 'customer'])).toBe(true);
        expect(hasRole(['contractor', 'field_engineer'])).toBe(false);
    });

    // ── logout ──────────────────────────────────────────────────────────

    it('logout calls DELETE /api/v1/auth/logout', async () => {
        mockApiFetch.mockResolvedValueOnce({});
        const { logout } = useAuth();
        await logout();
        expect(mockApiFetch).toHaveBeenCalledWith('/api/v1/auth/logout', { method: 'DELETE' });
    });

    it('logout clears auth state even when API call fails', async () => {
        mockApiFetch.mockRejectedValueOnce(new Error('Network error'));
        cookieRef.value = 'some-token';
        const store = useAuthStore();
        store.setUser({ id: 1, name: 'Dan', email: 'd@e.com', role: 'customer' } as never);

        const { logout } = useAuth();
        await logout();

        // Cookie ref and user should be cleared regardless of API failure
        expect(cookieRef.value).toBeNull();
        expect(store.user).toBeNull();
    });

    it('logout navigates to login page with locale prefix', async () => {
        mockApiFetch.mockResolvedValueOnce({});
        const { logout } = useAuth();
        await logout();
        expect(navigateTo).toHaveBeenCalledWith('/ar/auth/login');
    });

    // ── fetchCurrentUser ─────────────────────────────────────────────────

    it('fetchCurrentUser sets user in store on success', async () => {
        const userData = { id: 5, name: 'Eve', email: 'e@f.com', role: 'customer' };
        mockApiFetch.mockResolvedValueOnce({ success: true, data: userData });

        const { fetchCurrentUser } = useAuth();
        const result = await fetchCurrentUser();

        expect(result).toEqual(userData);
        const store = useAuthStore();
        expect(store.user).toEqual(userData);
    });

    it('fetchCurrentUser returns null on API failure', async () => {
        mockApiFetch.mockRejectedValueOnce(new Error('Unauthorized'));
        const { fetchCurrentUser } = useAuth();
        const result = await fetchCurrentUser();
        expect(result).toBeNull();
    });

    it('fetchCurrentUser returns null when success is false', async () => {
        mockApiFetch.mockResolvedValueOnce({ success: false, data: null });
        const { fetchCurrentUser } = useAuth();
        const result = await fetchCurrentUser();
        expect(result).toBeNull();
    });
});
