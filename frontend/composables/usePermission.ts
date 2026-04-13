// frontend/composables/usePermission.ts
import { useAuthStore } from '../stores/auth';

/**
 * Composable for checking user permissions in templates.
 * SECURITY NOTE: Presentation-only — all enforcement is server-side.
 */
export function usePermission() {
  const authStore = useAuthStore();

  function hasPermission(permissionName: string): boolean {
    return authStore.hasPermission(permissionName);
  }

  return {
    hasPermission,
  };
}
