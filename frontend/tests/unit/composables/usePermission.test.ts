import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import { usePermission } from '../../../composables/usePermission';
import { useAuthStore } from '../../../stores/auth';

describe('usePermission composable', () => {
  let cookieRef: ReturnType<typeof ref<string | null>>;

  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();

    cookieRef = ref<string | null>(null);
    vi.stubGlobal('useCookie', vi.fn().mockReturnValue(cookieRef));
  });

  it('returns false when no user is logged in', () => {
    const { hasPermission } = usePermission();
    expect(hasPermission('projects.view')).toBe(false);
  });

  it('returns true when user has the permission', () => {
    const store = useAuthStore();
    store.setUser({
      id: 1,
      name: 'Admin',
      email: 'admin@test.com',
      role: 'admin',
      phone: '0500000000',
      is_active: true,
      email_verified_at: null,
      created_at: '2025-01-01',
      permissions: ['projects.view', 'projects.create', 'users.manage'],
    } as never);

    const { hasPermission } = usePermission();
    expect(hasPermission('projects.view')).toBe(true);
    expect(hasPermission('projects.create')).toBe(true);
  });

  it('returns false when user does not have the permission', () => {
    const store = useAuthStore();
    store.setUser({
      id: 1,
      name: 'Customer',
      email: 'customer@test.com',
      role: 'customer',
      phone: '0500000000',
      is_active: true,
      email_verified_at: null,
      created_at: '2025-01-01',
      permissions: ['projects.view', 'orders.view'],
    } as never);

    const { hasPermission } = usePermission();
    expect(hasPermission('users.manage')).toBe(false);
  });

  it('returns false when permissions array is empty', () => {
    const store = useAuthStore();
    store.setUser({
      id: 1,
      name: 'Empty',
      email: 'empty@test.com',
      role: 'customer',
      phone: '0500000000',
      is_active: true,
      email_verified_at: null,
      created_at: '2025-01-01',
      permissions: [],
    } as never);

    const { hasPermission } = usePermission();
    expect(hasPermission('projects.view')).toBe(false);
  });
});
