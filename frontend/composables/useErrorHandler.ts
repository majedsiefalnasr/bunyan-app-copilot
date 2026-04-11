import { useErrorStore } from '~/stores/errorStore';
import { useToast } from '~/composables/useToast';
import { useI18n } from 'vue-i18n';

interface ErrorContext {
  code?: string;
  message?: string;
  details?: Record<string, string[]>;
  correlationId?: string;
  statusCode?: number;
}

interface ErrorMapEntry {
  toastType: 'error' | 'warning' | 'success' | 'info';
  navigateTo?: string;
  logLevel: 'error' | 'warn' | 'info';
}

const ERROR_CODE_MAP: Record<string, ErrorMapEntry> = {
  AUTH_INVALID_CREDENTIALS: {
    toastType: 'error',
    navigateTo: '/auth/login',
    logLevel: 'warn',
  },
  AUTH_TOKEN_EXPIRED: {
    toastType: 'error',
    navigateTo: '/auth/login',
    logLevel: 'warn',
  },
  AUTH_UNAUTHORIZED: {
    toastType: 'error',
    navigateTo: '/dashboard',
    logLevel: 'warn',
  },
  RBAC_ROLE_DENIED: {
    toastType: 'error',
    navigateTo: '/error-403',
    logLevel: 'warn',
  },
  RESOURCE_NOT_FOUND: {
    toastType: 'error',
    navigateTo: '/error-404',
    logLevel: 'warn',
  },
  VALIDATION_ERROR: {
    toastType: 'error',
    logLevel: 'warn',
  },
  WORKFLOW_INVALID_TRANSITION: {
    toastType: 'error',
    logLevel: 'warn',
  },
  WORKFLOW_PREREQUISITES_UNMET: {
    toastType: 'error',
    logLevel: 'warn',
  },
  PAYMENT_FAILED: {
    toastType: 'error',
    logLevel: 'error',
  },
  RATE_LIMIT_EXCEEDED: {
    toastType: 'warning',
    logLevel: 'warn',
  },
  CONFLICT_ERROR: {
    toastType: 'error',
    logLevel: 'warn',
  },
  SERVER_ERROR: {
    toastType: 'error',
    navigateTo: '/error-500',
    logLevel: 'error',
  },
};

export function useErrorHandler() {
  const errorStore = useErrorStore();
  const toast = useToast();
  const { t } = useI18n();

  /**
   * Get localized error message for an error code
   */
  function getLocalizedMessage(code: string, fallback?: string): string {
    const key = `errors.${code.toLowerCase()}`;
    const message = t(key, null, { missingWarn: false });

    // If translation not found, use fallback or code as message
    if (message === key) {
      return fallback || code;
    }

    return message;
  }

  /**
   * Handle an error from the API or application
   */
  function handleError(error: ErrorContext & { originalError?: Error }) {
    const code = error.code || 'SERVER_ERROR';
    const mapping = ERROR_CODE_MAP[code] || ERROR_CODE_MAP.SERVER_ERROR;

    // Get localized message
    let message = error.message || getLocalizedMessage(code);

    // For validation errors, build a summary message
    if (code === 'VALIDATION_ERROR' && error.details) {
      const fieldErrors = Object.entries(error.details)
        .map(([field, errors]) => `${field}: ${errors?.[0] || 'Error'}`)
        .slice(0, 2); // Show first 2 errors
      if (fieldErrors.length > 0) {
        message = fieldErrors.join(', ');
        if (Object.keys(error.details).length > 2) {
          message += '...';
        }
      }
    }

    // Store error in error store
    errorStore.setError(code, message, error.details, error.correlationId);

    // Show toast notification
    toast.showToast(message, mapping.toastType, mapping.toastType === 'error' ? 5000 : 3000);

    // Log error
    const logFn =
      mapping.logLevel === 'error'
        ? console.error
        : mapping.logLevel === 'warn'
        ? console.warn
        : console.info;
    logFn(`[${code}]${error.correlationId ? ` [${error.correlationId}]` : ''}`, {
      message,
      details: error.details,
      statusCode: error.statusCode,
    });

    // Navigate if needed
    if (mapping.navigateTo) {
      navigateTo(mapping.navigateTo);
    }

    return { code, message, mapping };
  }

  /**
   * Handle standard error response from API
   */
  function handleApiError(response: Record<string, unknown>) {
    if (!response || typeof response !== 'object') {
      return handleError({
        code: 'SERVER_ERROR',
        message: getLocalizedMessage('SERVER_ERROR'),
      });
    }

    const error = (response.error as Record<string, unknown> | undefined) || {};
    const correlationId =
      (response.headers as Record<string, string> | undefined)?.['x-correlation-id'] ||
      (response.correlationId as string | undefined);

    return handleError({
      code: (error.code as string | undefined) || 'SERVER_ERROR',
      message:
        (error.message as string | undefined) ||
        getLocalizedMessage((error.code as string | undefined) || 'SERVER_ERROR'),
      details: error.details as Record<string, string[]> | undefined,
      correlationId,
      statusCode: response.status as number | undefined,
      originalError: response.originalError as Error | undefined,
    });
  }

  /**
   * Handle field-level validation errors
   */
  function handleValidationError(details: Record<string, string[]>, correlationId?: string) {
    return handleError({
      code: 'VALIDATION_ERROR',
      message: getLocalizedMessage('VALIDATION_ERROR'),
      details,
      correlationId,
    });
  }

  return {
    getLocalizedMessage,
    handleError,
    handleApiError,
    handleValidationError,
  };
}
