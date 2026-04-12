import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export type ToastType = 'error' | 'warning' | 'success' | 'info';

export interface Toast {
    id: string;
    message: string;
    type: ToastType;
    duration: number;
}

export interface ApiError {
    code: string;
    message: string;
    details?: Record<string, string[]>;
    correlationId?: string;
}

export const useErrorStore = defineStore('error', () => {
    // State
    const toasts = ref<Toast[]>([]);
    const currentError = ref<ApiError | null>(null);

    // Getters
    const toastCount = computed(() => toasts.value.length);
    const hasError = computed(() => currentError.value !== null);

    // Actions
    function addToast(toast: Omit<Toast, 'id'>): string {
        const id = `toast-${Date.now()}-${Math.random()}`;
        const newToast: Toast = { ...toast, id };
        toasts.value.push(newToast);

        // Auto-dismiss if duration > 0
        if (toast.duration > 0) {
            setTimeout(() => {
                removeToast(id);
            }, toast.duration);
        }

        return id;
    }

    function removeToast(id: string) {
        const index = toasts.value.findIndex((t) => t.id === id);
        if (index !== -1) {
            toasts.value.splice(index, 1);
        }
    }

    function clearToasts() {
        toasts.value = [];
    }

    function setCurrentError(error: ApiError) {
        currentError.value = error;
    }

    function clearCurrentError() {
        currentError.value = null;
    }

    function setError(
        code: string,
        message: string,
        details?: Record<string, string[]>,
        correlationId?: string
    ) {
        currentError.value = {
            code,
            message,
            details,
            correlationId,
        };
    }

    return {
        // State
        toasts,
        currentError,
        // Getters
        toastCount,
        hasError,
        // Actions
        addToast,
        removeToast,
        clearToasts,
        setCurrentError,
        clearCurrentError,
        setError,
    };
});
