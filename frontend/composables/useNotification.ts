// frontend/composables/useNotification.ts
// Semantic notification facade over the existing useToast() system.
// Delegates ALL calls to useToast() — no duplicate toast logic.
import { useToast } from './useToast';

export function useNotification() {
  const toast = useToast();

  function notifySuccess(message: string, duration?: number): string {
    return toast.showSuccess(message, duration);
  }

  function notifyError(message: string, duration?: number): string {
    return toast.showError(message, duration);
  }

  function notifyWarning(message: string, duration?: number): string {
    return toast.showWarning(message, duration);
  }

  function notifyInfo(message: string, duration?: number): string {
    return toast.showInfo(message, duration);
  }

  function dismiss(id: string): void {
    toast.removeToast(id);
  }

  return {
    notifySuccess,
    notifyError,
    notifyWarning,
    notifyInfo,
    dismiss,
  };
}
