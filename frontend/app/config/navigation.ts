// frontend/app/config/navigation.ts
import type { NavItem } from '../../types/index';
import { UserRole } from '../../types/index';

export const NAV_ITEMS_BY_ROLE: Record<UserRole, NavItem[]> = {
  [UserRole.Customer]: [
    { labelKey: 'nav.dashboard', icon: 'i-heroicons-home', to: '/dashboard' },
    { labelKey: 'nav.projects', icon: 'i-heroicons-folder', to: '/projects' },
    { labelKey: 'nav.orders', icon: 'i-heroicons-shopping-bag', to: '/orders' },
    { labelKey: 'nav.payments', icon: 'i-heroicons-credit-card', to: '/payments' },
  ],
  [UserRole.Contractor]: [
    { labelKey: 'nav.dashboard', icon: 'i-heroicons-home', to: '/dashboard' },
    { labelKey: 'nav.projects', icon: 'i-heroicons-folder', to: '/projects' },
    { labelKey: 'nav.earnings', icon: 'i-heroicons-banknotes', to: '/earnings' },
    { labelKey: 'nav.withdrawals', icon: 'i-heroicons-arrow-up-on-square', to: '/withdrawals' },
  ],
  [UserRole.SupervisingArchitect]: [
    { labelKey: 'nav.dashboard', icon: 'i-heroicons-home', to: '/dashboard' },
    { labelKey: 'nav.projects', icon: 'i-heroicons-folder', to: '/projects' },
    { labelKey: 'nav.field_engineers', icon: 'i-heroicons-user-group', to: '/field-engineers' },
    { labelKey: 'nav.reports', icon: 'i-heroicons-document-text', to: '/reports' },
  ],
  [UserRole.FieldEngineer]: [
    { labelKey: 'nav.dashboard', icon: 'i-heroicons-home', to: '/dashboard' },
    { labelKey: 'nav.my_projects', icon: 'i-heroicons-folder', to: '/projects' },
    {
      labelKey: 'nav.submit_report',
      icon: 'i-heroicons-paper-airplane',
      to: '/reports/create',
    },
  ],
  [UserRole.Admin]: [
    { labelKey: 'nav.dashboard', icon: 'i-heroicons-home', to: '/dashboard' },
    { labelKey: 'nav.users', icon: 'i-heroicons-users', to: '/admin/users' },
    { labelKey: 'nav.projects', icon: 'i-heroicons-folder', to: '/admin/projects' },
    { labelKey: 'nav.products', icon: 'i-heroicons-cube', to: '/admin/products' },
    { labelKey: 'nav.orders', icon: 'i-heroicons-shopping-bag', to: '/admin/orders' },
    { labelKey: 'nav.roles', icon: 'i-heroicons-shield-check', to: '/admin/roles' },
    {
      labelKey: 'nav.configuration',
      icon: 'i-heroicons-cog-6-tooth',
      to: '/admin/configuration',
    },
    { labelKey: 'nav.reports', icon: 'i-heroicons-document-text', to: '/admin/reports' },
  ],
};
