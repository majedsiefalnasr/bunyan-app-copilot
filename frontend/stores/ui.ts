// frontend/stores/ui.ts
import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useUiStore = defineStore('ui', () => {
  // Sidebar open state — persisted to localStorage
  const isSidebarOpen = ref<boolean>(
    import.meta.client ? localStorage.getItem('bunyan_sidebar_open') !== 'false' : true
  );

  // Drawer (mobile nav) state — session only
  const isDrawerOpen = ref<boolean>(false);

  // Page loading — driven by Nuxt page lifecycle hooks in default.vue
  const isPageLoading = ref<boolean>(false);

  function toggleSidebar() {
    isSidebarOpen.value = !isSidebarOpen.value;
    if (import.meta.client) {
      localStorage.setItem('bunyan_sidebar_open', String(isSidebarOpen.value));
    }
  }

  function openDrawer() {
    isDrawerOpen.value = true;
  }

  function closeDrawer() {
    isDrawerOpen.value = false;
  }

  function toggleDrawer() {
    isDrawerOpen.value = !isDrawerOpen.value;
  }

  function setPageLoading(value: boolean) {
    isPageLoading.value = value;
  }

  return {
    isSidebarOpen,
    isDrawerOpen,
    isPageLoading,
    toggleSidebar,
    openDrawer,
    closeDrawer,
    toggleDrawer,
    setPageLoading,
  };
});
