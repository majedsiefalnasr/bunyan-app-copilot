// frontend/types/index.ts
// Application-wide TypeScript types and enums

// User roles matching backend Role enum
export enum UserRole {
  Customer = 'customer',
  Contractor = 'contractor',
  SupervisingArchitect = 'supervising_architect',
  FieldEngineer = 'field_engineer',
  Admin = 'admin',
}

export type UserRoleType = `${UserRole}`;

// Navigation
export interface NavItem {
  labelKey: string; // i18n key, e.g. 'nav.dashboard'
  to: string; // route path (without locale prefix)
  icon?: string; // Iconify icon name, e.g. 'i-heroicons-home'
  badge?: string | number;
  children?: NavItem[];
}

export type NavItemsByRole = Record<UserRole, NavItem[]>;

export interface DropdownMenuGroup {
  label?: string;
  items: Array<{
    label: string;
    icon?: string;
    to?: string;
    onSelect?: () => void;
  }>;
}

// Breadcrumb
export interface BreadcrumbItem {
  label: string;
  to?: string;
  icon?: string;
}

// Auth
export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: UserRoleType;
  phone: string;
  is_active: boolean;
  email_verified_at: string | null;
  created_at: string;
  avatar?: string;
  permissions: string[];
}

// Direction / i18n
export type Direction = 'rtl' | 'ltr';
export type Locale = 'ar' | 'en';

// UI Preferences
export interface UiPreferences {
  direction: Direction;
  locale: Locale;
  colorMode: 'light' | 'dark' | 'system';
}

// PageMeta augmentation for breadcrumb support
declare module '#app' {
  interface PageMeta {
    breadcrumb?: BreadcrumbItem[];
    requiredRole?: UserRoleType | UserRoleType[];
  }
}

declare module 'vue-router' {
  interface RouteMeta {
    breadcrumb?: BreadcrumbItem[];
    requiredRole?: UserRoleType | UserRoleType[];
  }
}
