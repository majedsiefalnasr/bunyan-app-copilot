// frontend/middleware/role.ts
/**
 * Role-based access control middleware
 * Checks if current user has required role(s) to access a route
 * Usage: definePageMeta({ middleware: ['auth', 'role'] })
 * Route meta: requireRole: 'customer' | ['customer', 'contractor']
 */
export default defineNuxtRouteMiddleware((to) => {
  const authStore = useAuthStore();

  // Get required roles from route meta (if any)
  const requiredRoles = to.meta.requireRole as string | string[] | undefined;

  if (!requiredRoles) {
    // No specific role requirement, allow all authenticated users
    return;
  }

  if (!authStore.user) {
    // Not authenticated
    const { locale } = useI18n();
    return navigateTo(`/${locale.value}/auth/login`);
  }

  // Check if user has required role
  const rolesArray = Array.isArray(requiredRoles) ? requiredRoles : [requiredRoles];

  if (!rolesArray.includes(authStore.user.role)) {
    // User does not have required role
    const { locale } = useI18n();
    return navigateTo(`/${locale.value}/auth/unauthorized`);
  }
});
