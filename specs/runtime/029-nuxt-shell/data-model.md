# Data Model: STAGE_29 — Nuxt Shell

> **Phase:** 07_FRONTEND_APPLICATION
> **Target File:** `frontend/types/index.ts` > **Generated:** 2026-04-12

---

## Overview

All types and interfaces defined here belong in `frontend/types/index.ts`. They extend the `types/ambient.d.ts` that already exists — they must be placed in a separate `index.ts` file (TypeScript named exports, not ambient declarations).

---

## 1. UserRole

```typescript
/**
 * Enum covering all five platform roles.
 * Values match the backend `role` field exactly (snake_case strings).
 */
export enum UserRole {
  Customer = 'customer',
  Contractor = 'contractor',
  SupervisingArchitect = 'supervising_architect',
  FieldEngineer = 'field_engineer',
  Admin = 'admin',
}

/**
 * Union type alias — use when you need a plain string type
 * without importing the enum (e.g., Pinia state, API response shape).
 */
export type UserRoleType =
  | 'customer'
  | 'contractor'
  | 'supervising_architect'
  | 'field_engineer'
  | 'admin';
```

---

## 2. NavItem

```typescript
/**
 * A single navigation menu entry.
 * The `roles` array controls visibility — filtered in composable before passing to UNavigationMenu.
 * The `icon` field uses Iconify naming (e.g., 'i-heroicons-home').
 */
export interface NavItem {
  /** i18n key used for display label resolution */
  labelKey: string;
  /** Resolved label string (populated at runtime by composable) */
  label?: string;
  /** Iconify icon string, e.g. 'i-heroicons-home' */
  icon?: string;
  /** Target route path (locale prefix NOT included — added by useLocaleRoute) */
  to?: string;
  /** Optional badge count or string (e.g., notification count) */
  badge?: string | number;
  /** Child items for nested navigation */
  children?: NavItem[];
  /** Roles that can see this item — empty array means no-one sees it */
  roles: UserRole[];
}
```

---

## 3. BreadcrumbItem

```typescript
/**
 * A single breadcrumb step.
 * The last item in the array should have no `to` (current page — not clickable).
 */
export interface BreadcrumbItem {
  /** Display label (resolved string, NOT an i18n key) */
  label: string;
  /** Route path — omit for the current (leaf) breadcrumb */
  to?: string;
  /** Optional leading icon (Iconify string) */
  icon?: string;
}
```

---

## 4. AuthUser

```typescript
/**
 * Authenticated user profile as returned by GET /api/v1/auth/me.
 * Mirrors the Laravel API Resource response shape.
 */
export interface AuthUser {
  id: number;
  name: string;
  email: string;
  /** Role string matching UserRoleType values */
  role: UserRoleType;
  /** Full URL to avatar image, or null */
  avatar?: string | null;
  /** ISO 8601 string; null if not verified */
  email_verified_at?: string | null;
  /** ISO 8601 creation timestamp */
  created_at?: string;
}
```

---

## 5. Direction

```typescript
/**
 * Document writing direction.
 * Applied to document.documentElement.dir and Tailwind logical properties.
 */
export type Direction = 'rtl' | 'ltr';
```

---

## 6. Locale

```typescript
/**
 * Supported application locales.
 * Must match locale codes in nuxt.config.ts locales array.
 */
export type Locale = 'ar' | 'en';
```

---

## 7. UiPreferences

```typescript
/**
 * Persisted user interface preferences.
 * Stored across: localStorage (direction), @nuxtjs/color-mode (colorMode), i18n cookie (locale).
 */
export interface UiPreferences {
  direction: Direction;
  locale: Locale;
  colorMode: 'light' | 'dark' | 'system';
}
```

---

## 8. NavItemsByRole

```typescript
/**
 * Navigation configuration map.
 * Each role key maps to its allowed navigation items.
 * Defined statically in app/config/navigation.ts.
 */
export type NavItemsByRole = Record<UserRole, NavItem[]>;
```

---

## 9. DropdownMenuItem (User Menu)

```typescript
/**
 * Item shape for UDropdownMenu in the user avatar dropdown.
 * Matches Nuxt UI v4 UDropdownMenu items prop structure.
 */
export interface DropdownMenuItem {
  label: string;
  icon?: string;
  to?: string;
  click?: () => void;
  disabled?: boolean;
  shortcut?: string;
}

/** A group of dropdown items (UDropdownMenu accepts array of groups) */
export type DropdownMenuGroup = DropdownMenuItem[];
```

---

## 10. RouteMetaBreadcrumb

```typescript
/**
 * Page meta extension for breadcrumb configuration.
 * Pages declare their breadcrumb path via definePageMeta({ breadcrumb: [...] }).
 */
export interface RouteMetaBreadcrumb {
  breadcrumb?: BreadcrumbItem[];
}

/** Extend Nuxt's RouteMeta to include breadcrumb */
declare module '#app' {
  interface PageMeta {
    breadcrumb?: BreadcrumbItem[];
    /** Required role to access this page (UX guard — server re-validates) */
    requiredRole?: UserRoleType | UserRoleType[];
  }
}
```

---

## 11. Type Relationships Diagram

```
AuthUser ──── role: UserRoleType ──── UserRole (enum)
                                           │
NavItemsByRole ──── Record<UserRole, NavItem[]>
                                           │
NavItem ──── roles: UserRole[]             │
         └── labelKey, icon, to, badge ────┘

BreadcrumbItem ← RouteMetaBreadcrumb (route.meta.breadcrumb)
              ← useBreadcrumb() composable

UiPreferences ← usePreferences() composable
             ├── direction: Direction ← useDirection()
             ├── locale: Locale ← useI18n().locale
             └── colorMode ← useColorMode().preference
```

---

## Notes

- `UserRole` enum values **exactly match** backend role strings — never change these
- `NavItem.labelKey` is the i18n key; the composable resolves it with `t(item.labelKey)` before passing to `UNavigationMenu`
- `AuthUser.avatar` is a full URL — can be passed directly to `UAvatar :src`
- `BreadcrumbItem` arrays are defined statically in page `definePageMeta` — `useBreadcrumb` reads `route.meta.breadcrumb` reactively
