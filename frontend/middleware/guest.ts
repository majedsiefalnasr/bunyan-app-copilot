// frontend/middleware/guest.ts
export default defineNuxtRouteMiddleware(() => {
  if (import.meta.server) return;

  const authStore = useAuthStore();

  if (authStore.isAuthenticated) {
    const { locale } = useI18n();
    return navigateTo(`/${locale.value}/dashboard`);
  }
});
