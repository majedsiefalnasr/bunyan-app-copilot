// frontend/stores/auth.ts
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import type { AuthUser, UserRoleType } from '../types/index';

export const useAuthStore = defineStore('auth', () => {
  // CRITICAL: token derived from Nuxt cookie — NOT a separate ref.
  // When useApi.ts 401 handler sets useCookie('auth_token').value = null,
  // this computed automatically becomes null → isAuthenticated becomes false.
  // Using a separate ref would require manual synchronization and cause redirect loops.
  const authCookie = useCookie<string | null>('auth_token', {
    path: '/',
    sameSite: 'lax',
    secure: process.env.NODE_ENV === 'production',
    // NOTE: non-httpOnly is REQUIRED for client-side Bearer token attachment via useApi.ts.
    // This is an accepted architecture constraint (see spec.md §API Integration).
    // RBAC enforcement is server-side; client only reads this for UI presentation.
  });

  const token = computed<string | null>(() => authCookie.value ?? null);
  const user = ref<AuthUser | null>(null);
  const isLoading = ref(false);

  // Computed auth state
  const isAuthenticated = computed(() => token.value !== null && user.value !== null);
  const role = computed<UserRoleType | null>(() => user.value?.role ?? null);

  // Actions
  function setToken(newToken: string) {
    authCookie.value = newToken;
  }

  function setUser(newUser: AuthUser) {
    user.value = newUser;
  }

  function clearAuth() {
    user.value = null;
    authCookie.value = null;
  }

  /**
   * Initialize store state from cookie on app bootstrap.
   * Called in default.vue onMounted or app.vue setup when token exists but user is null.
   */
  async function initFromCookie() {
    // Token presence alone doesn't mean user is loaded.
    // Caller (useAuth.fetchCurrentUser) handles the API call.
    // This just exposes the readiness check.
    return token.value !== null;
  }

  /**
   * Presentation-layer RBAC helper.
   * SECURITY NOTE: hasRole() is for UI presentation (show/hide nav items) ONLY.
   * All RBAC enforcement is server-side via Laravel middleware and Policies.
   * Never use this as a security boundary.
   */
  function hasRole(roleOrRoles: UserRoleType | UserRoleType[]): boolean {
    if (!user.value) return false;
    if (Array.isArray(roleOrRoles)) {
      return roleOrRoles.includes(user.value.role);
    }
    return user.value.role === roleOrRoles;
  }

  /**
   * Presentation-layer permission helper.
   * Checks if the user has a specific permission from the permissions array.
   * SECURITY NOTE: Presentation-only — all enforcement is server-side.
   */
  function hasPermission(permissionName: string): boolean {
    if (!user.value) return false;
    return user.value.permissions?.includes(permissionName) ?? false;
  }

  return {
    token,
    user,
    isAuthenticated,
    isLoading,
    role,
    setToken,
    setUser,
    clearAuth,
    initFromCookie,
    hasRole,
    hasPermission,
  };
});

// Type re-export for convenience
export type { UserRoleType } from '../types/index';
