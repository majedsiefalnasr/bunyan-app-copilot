// frontend/middleware/role.ts
import type { UserRoleType } from '../types/index';

export default defineNuxtRouteMiddleware((to) => {
  const auth = useAuthStore();
  const requiredRole = to.meta.requiredRole as UserRoleType | UserRoleType[] | undefined;

  if (!requiredRole) return;

  if (!auth.isAuthenticated) {
    const { locale } = useI18n();
    return navigateTo(`/${locale.value}/auth/login`);
  }

  const roles = Array.isArray(requiredRole) ? requiredRole : [requiredRole];
  if (!auth.hasRole(roles as UserRoleType[])) {
    const { locale } = useI18n();
    return navigateTo(`/${locale.value}/dashboard`);
  }
});
