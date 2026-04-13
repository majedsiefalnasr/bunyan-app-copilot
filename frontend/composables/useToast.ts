import { computed } from 'vue';
import { type Toast, useErrorStore } from '../stores/errorStore';

export type ToastType = 'error' | 'warning' | 'success' | 'info';

export function useToast() {
  const errorStore = useErrorStore();

  const toasts = computed<Toast[]>(() => errorStore.toasts);

  /**
   * Show a toast notification
   * @param message - The toast message
   * @param type - The type of toast (error, warning, success, info)
   * @param duration - Duration in milliseconds (0 for no auto-dismiss, default 5000)
   * @returns The toast ID for manual removal
   */
  function showToast(message: string, type: ToastType = 'info', duration: number = 5000): string {
    return errorStore.addToast({
      message,
      type,
      duration,
    });
  }

  /**
   * Remove a toast by ID
   */
  function removeToast(id: string) {
    errorStore.removeToast(id);
  }

  /**
   * Show error toast
   */
  function showError(message: string, duration: number = 5000): string {
    return showToast(message, 'error', duration);
  }

  /**
   * Show warning toast
   */
  function showWarning(message: string, duration: number = 5000): string {
    return showToast(message, 'warning', duration);
  }

  /**
   * Show success toast
   */
  function showSuccess(message: string, duration: number = 3000): string {
    return showToast(message, 'success', duration);
  }

  /**
   * Show info toast
   */
  function showInfo(message: string, duration: number = 5000): string {
    return showToast(message, 'info', duration);
  }

  return {
    toasts,
    showToast,
    removeToast,
    showError,
    showWarning,
    showSuccess,
    showInfo,
  };
}
