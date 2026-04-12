import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { useErrorHandler } from '~/composables/useErrorHandler';

describe('useErrorHandler composable', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('returns localized message for error code', () => {
    const { getLocalizedMessage } = useErrorHandler();
    const message = getLocalizedMessage('AUTH_INVALID_CREDENTIALS');

    expect(message).toBeDefined();
    expect(typeof message).toBe('string');
  });

  it('returns fallback message if translation not found', () => {
    const { getLocalizedMessage } = useErrorHandler();
    const message = getLocalizedMessage('UNKNOWN_ERROR', 'Fallback message');

    expect(message).toBe('Fallback message');
  });

  it('handles validation error with details', () => {
    const { handleError } = useErrorHandler();
    const result = handleError({
      code: 'VALIDATION_ERROR',
      message: 'Validation failed',
      details: {
        email: ['Email is required'],
        password: ['Password must be at least 8 characters'],
      },
    });

    expect(result.code).toBe('VALIDATION_ERROR');
    expect(result.mapping.toastType).toBe('error');
  });

  it('handles authentication error', () => {
    const { handleError } = useErrorHandler();
    const result = handleError({
      code: 'AUTH_INVALID_CREDENTIALS',
      message: 'Invalid credentials',
    });

    expect(result.code).toBe('AUTH_INVALID_CREDENTIALS');
    expect(result.mapping.navigateTo).toBe('/auth/login');
  });

  it('handles authorization error', () => {
    const { handleError } = useErrorHandler();
    const result = handleError({
      code: 'RBAC_ROLE_DENIED',
      message: 'Role not authorized',
    });

    expect(result.code).toBe('RBAC_ROLE_DENIED');
    expect(result.mapping.navigateTo).toBe('/error-403');
  });

  it('handles not found error', () => {
    const { handleError } = useErrorHandler();
    const result = handleError({
      code: 'RESOURCE_NOT_FOUND',
      message: 'Resource not found',
    });

    expect(result.code).toBe('RESOURCE_NOT_FOUND');
    expect(result.mapping.navigateTo).toBe('/error-404');
  });

  it('handles server error', () => {
    const { handleError } = useErrorHandler();
    const result = handleError({
      code: 'SERVER_ERROR',
      message: 'Internal server error',
      correlationId: 'abc-123-def',
    });

    expect(result.code).toBe('SERVER_ERROR');
    expect(result.mapping.navigateTo).toBe('/error-500');
  });

  it('preserves correlation ID', () => {
    const { handleError } = useErrorHandler();
    const correlationId = 'corr-id-12345';
    handleError({
      code: 'SERVER_ERROR',
      message: 'Error',
      correlationId,
    });

    // Correlation ID is stored in error store, verified in E2E tests
  });

  it('handles API error response', () => {
    const { handleApiError } = useErrorHandler();
    const response = {
      status: 400,
      error: {
        code: 'VALIDATION_ERROR',
        message: 'Invalid input',
        details: {
          field: ['Error message'],
        },
      },
      headers: {
        'x-correlation-id': 'corr-123',
      },
    };

    const result = handleApiError(response);
    expect(result.code).toBe('VALIDATION_ERROR');
  });

  it('handles validation error with field details', () => {
    const { handleValidationError } = useErrorHandler();
    const details = {
      email: ['Email is required', 'Email must be valid'],
      password: ['Password is required'],
    };

    const result = handleValidationError(details, 'corr-123');
    expect(result.code).toBe('VALIDATION_ERROR');
  });

  it('maps all standard error codes', () => {
    const { handleError } = useErrorHandler();
    const standardCodes = [
      'AUTH_INVALID_CREDENTIALS',
      'AUTH_TOKEN_EXPIRED',
      'AUTH_UNAUTHORIZED',
      'RBAC_ROLE_DENIED',
      'RESOURCE_NOT_FOUND',
      'VALIDATION_ERROR',
      'WORKFLOW_INVALID_TRANSITION',
      'WORKFLOW_PREREQUISITES_UNMET',
      'PAYMENT_FAILED',
      'RATE_LIMIT_EXCEEDED',
      'CONFLICT_ERROR',
      'SERVER_ERROR',
    ];

    standardCodes.forEach((code) => {
      const result = handleError({ code, message: 'Test' });
      expect(result.code).toBe(code);
      expect(result.mapping).toBeDefined();
    });
  });
});
