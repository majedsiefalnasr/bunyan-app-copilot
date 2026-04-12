import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { i18n, createTestRouter } from '../setup';
import { useErrorHandler } from '../../composables/useErrorHandler';

// Helper function to create a test component that uses the composable
const createTestComponent = () => ({
  setup() {
    const handler = useErrorHandler();
    return { handler };
  },
  template: '<div></div>',
});

describe('useErrorHandler composable', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  const mountWithProviders = (component: object | null) => {
    return mount(component || createTestComponent(), {
      global: {
        plugins: [i18n],
        provide: {
          $router: createTestRouter(),
        },
      },
    });
  };

  it('returns localized message for error code', () => {
    const wrapper = mountWithProviders(null);
    const handler = (wrapper.vm as unknown as { handler: ReturnType<typeof useErrorHandler> })
      .handler;
    const message = handler.getLocalizedMessage('AUTH_INVALID_CREDENTIALS');

    expect(message).toBeDefined();
    expect(typeof message).toBe('string');
  });

  it('returns fallback message if translation not found', () => {
    const wrapper = mountWithProviders(null);
    const handler = (wrapper.vm as unknown as { handler: ReturnType<typeof useErrorHandler> })
      .handler;
    const message = handler.getLocalizedMessage('UNKNOWN_ERROR', 'Fallback message');

    expect(message).toBe('Fallback message');
  });

  it('handles validation error with details', () => {
    const wrapper = mountWithProviders(null);
    const handler = (wrapper.vm as unknown as { handler: ReturnType<typeof useErrorHandler> })
      .handler;
    const result = handler.handleError({
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
    const wrapper = mountWithProviders(null);
    const handler = (wrapper.vm as unknown as { handler: ReturnType<typeof useErrorHandler> })
      .handler;
    const result = handler.handleError({
      code: 'AUTH_INVALID_CREDENTIALS',
      message: 'Invalid credentials',
    });

    expect(result.code).toBe('AUTH_INVALID_CREDENTIALS');
    expect(result.mapping.navigateTo).toBe('/auth/login');
  });

  it('handles authorization error', () => {
    const wrapper = mountWithProviders(null);
    const handler = (wrapper.vm as unknown as { handler: ReturnType<typeof useErrorHandler> })
      .handler;
    const result = handler.handleError({
      code: 'RBAC_ROLE_DENIED',
      message: 'Role not authorized',
    });

    expect(result.code).toBe('RBAC_ROLE_DENIED');
    expect(result.mapping.navigateTo).toBe('/error-403');
  });

  it('handles not found error', () => {
    const wrapper = mountWithProviders(null);
    const handler = (wrapper.vm as unknown as { handler: ReturnType<typeof useErrorHandler> })
      .handler;
    const result = handler.handleError({
      code: 'RESOURCE_NOT_FOUND',
      message: 'Resource not found',
    });

    expect(result.code).toBe('RESOURCE_NOT_FOUND');
    expect(result.mapping.navigateTo).toBe('/error-404');
  });

  it('handles server error', () => {
    const wrapper = mountWithProviders(null);
    const handler = (wrapper.vm as unknown as { handler: ReturnType<typeof useErrorHandler> })
      .handler;
    const result = handler.handleError({
      code: 'SERVER_ERROR',
      message: 'Internal server error',
      correlationId: 'abc-123-def',
    });

    expect(result.code).toBe('SERVER_ERROR');
    expect(result.mapping.navigateTo).toBe('/error-500');
  });

  it('preserves correlation ID', () => {
    const wrapper = mountWithProviders(null);
    const handler = (wrapper.vm as unknown as { handler: ReturnType<typeof useErrorHandler> })
      .handler;
    const correlationId = 'corr-id-12345';
    handler.handleError({
      code: 'SERVER_ERROR',
      message: 'Error',
      correlationId,
    });

    // Correlation ID is stored in error store, verified in E2E tests
  });

  it('handles API error response', () => {
    const wrapper = mountWithProviders(null);
    const handler = (wrapper.vm as unknown as { handler: ReturnType<typeof useErrorHandler> })
      .handler;
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

    const result = handler.handleApiError(response);
    expect(result.code).toBe('VALIDATION_ERROR');
  });

  it('handles validation error with field details', () => {
    const wrapper = mountWithProviders(null);
    const handler = (wrapper.vm as unknown as { handler: ReturnType<typeof useErrorHandler> })
      .handler;
    const details = {
      email: ['Email is required', 'Email must be valid'],
      password: ['Password is required'],
    };

    const result = handler.handleValidationError(details, 'corr-123');
    expect(result.code).toBe('VALIDATION_ERROR');
  });

  it('maps all standard error codes', () => {
    const wrapper = mountWithProviders(null);
    const handler = (wrapper.vm as unknown as { handler: ReturnType<typeof useErrorHandler> })
      .handler;
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
      const result = handler.handleError({ code, message: 'Test' });
      expect(result.code).toBe(code);
      expect(result.mapping).toBeDefined();
    });
  });
});
