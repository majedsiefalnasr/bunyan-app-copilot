<script setup lang="ts">
import { computed, watch } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useUiStore } from '../stores/ui';
import { NAV_ITEMS_BY_ROLE } from '../app/config/navigation';
import type { NavItem, UserRole } from '../types/index';

const authStore = useAuthStore();
const uiStore = useUiStore();
const nuxtApp = useNuxtApp();
const { locale } = useI18n();

// Compute nav items directly — inject('navItems') in <script setup> would look up
// the ancestor chain from default.vue's perspective, and AppNavigation is a child
// (rendered in the template), not an ancestor. So we reproduce the same computation
// here and pass items as props to AppSidebar and MobileDrawer for testability.
const navItems = computed<NavItem[]>(() => {
  const role = authStore.role as UserRole | null;
  if (!role) return [];
  return (NAV_ITEMS_BY_ROLE[role] ?? []).map((item) => ({
    ...item,
    to: `/${locale.value}${item.to}`,
  }));
});

// Page loading hooks driven by Nuxt page lifecycle
nuxtApp.hook('page:start', () => {
  uiStore.setPageLoading(true);
});
nuxtApp.hook('page:finish', () => {
  uiStore.setPageLoading(false);
});

// Auth guard: redirect unauthenticated users to login
// NOTE: bootstrapping (fetchCurrentUser on startup) is handled in app.vue (T021).
// On initial load with a valid token, isAuthenticated will be true once user is set.
watch(
  () => authStore.isAuthenticated,
  (isAuthenticated) => {
    if (!isAuthenticated && !authStore.token) {
      navigateTo(`/${locale.value}/auth/login`);
    }
  },
  { immediate: true }
);
</script>

<template>
  <div class="min-h-screen flex flex-col bg-[#fafafa] dark:bg-[#0a0a0a]">
    <!-- AppNavigation wraps everything — provides resolved nav items to deep descendants -->
    <AppNavigation>
      <!-- Top navigation bar -->
      <AppHeader :show-hamburger="true" @hamburger-click="uiStore.toggleDrawer()" />

      <!-- Content area: sidebar + main -->
      <div class="flex flex-1 overflow-hidden">
        <!-- Desktop sidebar (receives items as prop — prop-based for testability) -->
        <AppSidebar :items="navItems" />

        <!-- Main content -->
        <main class="flex-1 flex flex-col overflow-auto">
          <!-- Breadcrumb -->
          <AppBreadcrumb />

          <!-- Page loading progress bar -->
          <UProgress
            v-if="uiStore.isPageLoading"
            animation="carousel"
            class="fixed top-0 start-0 end-0 z-50 h-0.5"
            color="primary"
          />

          <!-- Page content -->
          <div class="p-6 flex-1">
            <slot />
          </div>
        </main>
      </div>

      <!-- Footer -->
      <AppFooter />

      <!-- Mobile drawer (receives items as prop — prop-based for testability) -->
      <MobileDrawer :items="navItems" />
    </AppNavigation>
  </div>
</template>
