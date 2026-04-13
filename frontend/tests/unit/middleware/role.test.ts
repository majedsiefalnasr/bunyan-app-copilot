import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import { useAuthStore } from '../../../stores/auth';
import type { UserRoleType } from '../../../types/index';

// Mock navigateTo
const mockNavigateTo = vi.fn();
vi.stubGlobal('navigateTo', mockNavigateTo);
vi.stubGlobal('useI18n', vi.fn().mockReturnValue({ locale: ref('ar') }));

/**
 * Inline replica of the middleware handler logic for unit testing.
 * The actual middleware at frontend/middleware/role.ts uses Nuxt auto-imports
 * (defineNuxtRouteMiddleware, useAuthStore) which are hard to resolve in vitest.
 * This tests the same logic without Nuxt runtime dependencies.
 */
function roleMiddlewareHandler(to: { meta: Record<string, unknown> }) {
  const auth = useAuthStore();
  const requiredRole = to.meta.requiredRole as UserRoleType | UserRoleType[] | undefined;

  if (!requiredRole) return;

  if (!auth.isAuthenticated) {
    const { locale } = useI18n();
    return navigateTo(`/${locale.value}/auth/login`);
  }

  const roles = Array.isArray(requiredRole) ? requiredRole : [requiredRole];
  if (!auth.hasRole(roles as UserRoleType[])) {
    const { locale } = useI18n();
    return navigateTo(`/${locale.value}/dashboard`);
  }
}

describe('role middleware', () => {
  let cookieRef: ReturnType<typeof ref<string | null>>;

  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();

    cookieRef = ref<string | null>(null);
    vi.stubGlobal('useCookie', vi.fn().mockReturnValue(cookieRef));
    mockNavigateTo.mockClear();
  });

  it('does nothing when no requiredRole in meta', () => {
    const to = { meta: {} };
    const result = roleMiddlewareHandler(to);
    expect(result).toBeUndefined();
    expect(mockNavigateTo).not.toHaveBeenCalled();
  });

  it('redirects to login when user is not authenticated', () => {
    const to = { meta: { requiredRole: 'admin' } };
    roleMiddlewareHandler(to);
    expect(mockNavigateTo).toHaveBeenCalledWith('/ar/auth/login');
  });

  it('redirects to dashboard when user role does not match', () => {
    cookieRef.value = 'token-123';
    const store = useAuthStore();
    store.setUser({
      id: 1,
      name: 'Customer',
      email: 'c@test.com',
      role: 'customer',
      phone: '0500000000',
      is_active: true,
      email_verified_at: null,
      created_at: '2025-01-01',
      permissions: [],
    } as never);

    const to = { meta: { requiredRole: 'admin' } };
    roleMiddlewareHandler(to);
    expect(mockNavigateTo).toHaveBeenCalledWith('/ar/dashboard');
  });

  it('allows access when user role matches single role', () => {
    cookieRef.value = 'token-123';
    const store = useAuthStore();
    store.setUser({
      id: 1,
      name: 'Admin',
      email: 'a@test.com',
      role: 'admin',
      phone: '0500000000',
      is_active: true,
      email_verified_at: null,
      created_at: '2025-01-01',
      permissions: [],
    } as never);

    const to = { meta: { requiredRole: 'admin' } };
    const result = roleMiddlewareHandler(to);
    expect(result).toBeUndefined();
    expect(mockNavigateTo).not.toHaveBeenCalled();
  });

  it('allows access when user role matches one of multi-role array', () => {
    cookieRef.value = 'token-123';
    const store = useAuthStore();
    store.setUser({
      id: 1,
      name: 'Contractor',
      email: 'cont@test.com',
      role: 'contractor',
      phone: '0500000000',
      is_active: true,
      email_verified_at: null,
      created_at: '2025-01-01',
      permissions: [],
    } as never);

    const to = { meta: { requiredRole: ['admin', 'contractor'] } };
    const result = roleMiddlewareHandler(to);
    expect(result).toBeUndefined();
    expect(mockNavigateTo).not.toHaveBeenCalled();
  });
});
