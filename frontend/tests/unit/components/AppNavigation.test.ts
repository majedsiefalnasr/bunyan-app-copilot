import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h, inject, ref, type Ref } from 'vue';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';

import { UserRole } from '../../../types/index';
import type { NavItem } from '../../../types/index';
import { NAV_ITEMS_BY_ROLE } from '../../../app/config/navigation';

import AppNavigation from '../../../app/components/navigation/AppNavigation.vue';
import { useAuthStore } from '../../../stores/auth';

// useI18n is a Nuxt auto-import in AppNavigation.vue — stub before any component evaluation.
vi.stubGlobal('useI18n', () => ({
  t: (key: string) => key,
  locale: ref('ar'),
}));

// useCookie is a Nuxt auto-import used inside useAuthStore's defineStore factory.
const cookieRef = { value: null as string | null };
vi.stubGlobal('useCookie', vi.fn().mockReturnValue(cookieRef));

/** Slot child that captures injected navItems for assertions */
const NavItemsConsumer = defineComponent({
  setup() {
    const navItems = inject<Ref<NavItem[]>>('navItems');
    return { navItems };
  },
  template: '<div />',
});

function mountNavigationWithRole(role: string | null = null) {
  const pinia = createPinia();
  setActivePinia(pinia);

  if (role) {
    const store = useAuthStore();
    store.setUser({ id: 1, name: 'Test', email: 'test@test.com', role } as never);
  }

  // Mount WITHOUT the i18n plugin so globalThis.useI18n stub is used by the component.
  const wrapper = mount(AppNavigation, {
    global: { plugins: [pinia] },
    slots: { default: () => h(NavItemsConsumer) },
  });

  return { wrapper, consumer: wrapper.findComponent(NavItemsConsumer) };
}

// ────────────────────────────────────────────────────────────────────────────
// Suite 1: NAV_ITEMS_BY_ROLE — pure configuration unit tests
// ────────────────────────────────────────────────────────────────────────────
describe('NAV_ITEMS_BY_ROLE navigation configuration', () => {
  it('Customer has 4 nav items', () => {
    expect(NAV_ITEMS_BY_ROLE[UserRole.Customer]).toHaveLength(4);
  });

  it('Customer includes dashboard, projects, orders, payments', () => {
    const keys = NAV_ITEMS_BY_ROLE[UserRole.Customer].map((i) => i.labelKey);
    expect(keys).toContain('nav.dashboard');
    expect(keys).toContain('nav.projects');
    expect(keys).toContain('nav.orders');
    expect(keys).toContain('nav.payments');
  });

  it('Customer has no admin routes', () => {
    const routes = NAV_ITEMS_BY_ROLE[UserRole.Customer].map((i) => i.to);
    expect(routes.every((r) => !r.startsWith('/admin'))).toBe(true);
  });

  it('Admin has 8 nav items', () => {
    expect(NAV_ITEMS_BY_ROLE[UserRole.Admin]).toHaveLength(8);
  });

  it('Admin includes users and configuration', () => {
    const keys = NAV_ITEMS_BY_ROLE[UserRole.Admin].map((i) => i.labelKey);
    expect(keys).toContain('nav.users');
    expect(keys).toContain('nav.configuration');
    expect(keys).toContain('nav.roles');
  });

  it('FieldEngineer includes submit_report', () => {
    const keys = NAV_ITEMS_BY_ROLE[UserRole.FieldEngineer].map((i) => i.labelKey);
    expect(keys).toContain('nav.submit_report');
  });

  it('Contractor includes earnings and withdrawals', () => {
    const keys = NAV_ITEMS_BY_ROLE[UserRole.Contractor].map((i) => i.labelKey);
    expect(keys).toContain('nav.earnings');
    expect(keys).toContain('nav.withdrawals');
  });

  it('SupervisingArchitect includes field_engineers', () => {
    const keys = NAV_ITEMS_BY_ROLE[UserRole.SupervisingArchitect].map((i) => i.labelKey);
    expect(keys).toContain('nav.field_engineers');
  });
});

// ────────────────────────────────────────────────────────────────────────────
// Suite 2: AppNavigation component — slot rendering + provide/inject
// ────────────────────────────────────────────────────────────────────────────
describe('AppNavigation component', () => {
  beforeEach(() => {
    cookieRef.value = null;
    vi.clearAllMocks();
  });

  it('renders slot content without errors', () => {
    const { consumer } = mountNavigationWithRole(null);
    expect(consumer.exists()).toBe(true);
  });
});
