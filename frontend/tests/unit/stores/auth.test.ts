import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { createPinia, setActivePinia } from 'pinia';

import { useAuthStore } from '../../../stores/auth';

describe('auth store', () => {
    let cookieRef: ReturnType<typeof ref<string | null>>;

    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();

        cookieRef = ref<string | null>(null);
        vi.stubGlobal('useCookie', vi.fn().mockReturnValue(cookieRef));
    });

    // ── token ───────────────────────────────────────────────────────────

    it('token is null by default', () => {
        const store = useAuthStore();
        expect(store.token).toBeNull();
    });

    it('setToken writes to cookie ref', () => {
        const store = useAuthStore();
        store.setToken('abc-123');
        expect(cookieRef.value).toBe('abc-123');
        expect(store.token).toBe('abc-123');
    });

    // ── user ────────────────────────────────────────────────────────────

    it('user is null by default', () => {
        const store = useAuthStore();
        expect(store.user).toBeNull();
    });

    it('setUser stores user data', () => {
        const store = useAuthStore();
        const mockUser = {
            id: 1,
            name: 'Test',
            email: 't@t.com',
            role: 'customer' as const,
            phone: '0512345678',
            is_active: true,
            email_verified_at: null,
            created_at: '2025-01-01',
        };
        store.setUser(mockUser as never);
        expect(store.user).toEqual(mockUser);
    });

    // ── clearAuth ───────────────────────────────────────────────────────

    it('clearAuth clears user and token', () => {
        const store = useAuthStore();
        cookieRef.value = 'token-xyz';
        store.setUser({ id: 1, name: 'A', email: 'a@b.com', role: 'customer' } as never);

        store.clearAuth();

        expect(store.user).toBeNull();
        expect(cookieRef.value).toBeNull();
        expect(store.token).toBeNull();
    });

    // ── isAuthenticated ─────────────────────────────────────────────────

    it('isAuthenticated is false when no user and no token', () => {
        const store = useAuthStore();
        expect(store.isAuthenticated).toBe(false);
    });

    it('isAuthenticated is false when token exists but user is null', () => {
        cookieRef.value = 'some-token';
        const store = useAuthStore();
        expect(store.isAuthenticated).toBe(false);
    });

    it('isAuthenticated is false when user exists but token is null', () => {
        const store = useAuthStore();
        store.setUser({ id: 1, name: 'B', email: 'b@c.com', role: 'contractor' } as never);
        expect(store.isAuthenticated).toBe(false);
    });

    it('isAuthenticated is true when both token and user are set', () => {
        cookieRef.value = 'valid-token';
        const store = useAuthStore();
        store.setUser({ id: 1, name: 'C', email: 'c@d.com', role: 'customer' } as never);
        expect(store.isAuthenticated).toBe(true);
    });

    // ── role ────────────────────────────────────────────────────────────

    it('role is null when no user', () => {
        const store = useAuthStore();
        expect(store.role).toBeNull();
    });

    it('role returns user role when user is set', () => {
        const store = useAuthStore();
        store.setUser({
            id: 1,
            name: 'D',
            email: 'd@e.com',
            role: 'supervising_architect',
        } as never);
        expect(store.role).toBe('supervising_architect');
    });

    // ── isLoading ───────────────────────────────────────────────────────

    it('isLoading is false by default', () => {
        const store = useAuthStore();
        expect(store.isLoading).toBe(false);
    });

    it('isLoading can be toggled', () => {
        const store = useAuthStore();
        store.isLoading = true;
        expect(store.isLoading).toBe(true);
        store.isLoading = false;
        expect(store.isLoading).toBe(false);
    });

    // ── hasRole ─────────────────────────────────────────────────────────

    it('hasRole returns false when no user', () => {
        const store = useAuthStore();
        expect(store.hasRole('customer')).toBe(false);
    });

    it('hasRole returns true when user role matches', () => {
        const store = useAuthStore();
        store.setUser({ id: 1, name: 'E', email: 'e@f.com', role: 'contractor' } as never);
        expect(store.hasRole('contractor')).toBe(true);
        expect(store.hasRole('customer')).toBe(false);
    });

    it('hasRole accepts array of roles', () => {
        const store = useAuthStore();
        store.setUser({ id: 1, name: 'F', email: 'f@g.com', role: 'field_engineer' } as never);
        expect(store.hasRole(['field_engineer', 'admin'])).toBe(true);
        expect(store.hasRole(['customer', 'contractor'])).toBe(false);
    });

    // ── initFromCookie ──────────────────────────────────────────────────

    it('initFromCookie returns true when token exists', async () => {
        cookieRef.value = 'existing-token';
        const store = useAuthStore();
        const result = await store.initFromCookie();
        expect(result).toBe(true);
    });

    it('initFromCookie returns false when no token', async () => {
        const store = useAuthStore();
        const result = await store.initFromCookie();
        expect(result).toBe(false);
    });
});
