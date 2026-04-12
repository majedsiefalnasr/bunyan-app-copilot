// frontend/middleware/auth.ts
export default defineNuxtRouteMiddleware(async () => {
    const authStore = useAuthStore();

    // If no token at all, redirect to login
    if (!authStore.token) {
        const { locale } = useI18n();
        return navigateTo(`/${locale.value}/auth/login`);
    }

    // Token exists but user not loaded yet — fetch from API
    if (!authStore.user) {
        const { fetchCurrentUser } = useAuth();
        const user = await fetchCurrentUser();
        if (!user) {
            // Token invalid or expired — clear and redirect
            authStore.clearAuth();
            const { locale } = useI18n();
            return navigateTo(`/${locale.value}/auth/login`);
        }
    }
});
