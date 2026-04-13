<script setup lang="ts">
  import { computed, provide } from 'vue';
  import { useAuthStore } from '../../../stores/auth';
  import { NAV_ITEMS_BY_ROLE } from '../../config/navigation';
  import type { NavItem, UserRole } from '../../../types/index';

  const authStore = useAuthStore();
  const { locale } = useI18n();

  // Resolve nav items for the current role with translated labels and locale-prefixed routes.
  // useLocaleRoute() only exposes { push, back } — no path-resolve helper.
  // We compute the locale prefix directly, matching the same logic push() uses internally.
  const resolvedItems = computed<NavItem[]>(() => {
    const role = authStore.role as UserRole | null;
    if (!role) return [];
    const items = NAV_ITEMS_BY_ROLE[role] ?? [];
    return items.map((item) => ({
      ...item,
      to: `/${locale.value}${item.to}`,
    }));
  });

  // Provide to any descendant rendered inside this component's <slot />.
  // NOTE: default.vue cannot inject from here because it is AppNavigation's parent,
  // not a descendant. AppSidebar and MobileDrawer rendered via <slot /> CAN inject,
  // but they are prop-based for testability (props passed from default.vue).
  provide('navItems', resolvedItems);
</script>

<template>
  <slot />
</template>
