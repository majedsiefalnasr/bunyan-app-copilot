import { useErrorHandler } from './useErrorHandler';

export function useApi() {
  const config = useRuntimeConfig();
  const errorHandler = useErrorHandler();

  const baseURL =
    typeof config.public.apiBaseUrl === 'string' && config.public.apiBaseUrl
      ? config.public.apiBaseUrl
      : 'http://localhost:8000';

  // Request queue for token refresh race condition prevention
  let isRefreshing = false;
  let pendingRequests: Array<{
    resolve: (value: string) => void;
    reject: (reason?: Error) => void;
  }> = [];

  /**
   * Refresh token and drain pending request queue
   */
  async function refreshToken() {
    if (isRefreshing) {
      // Wait for refresh to complete
      return new Promise((resolve, reject) => {
        pendingRequests.push({ resolve, reject });
      });
    }

    isRefreshing = true;

    try {
      // Call token refresh endpoint
      const response = await $fetch<{ success: boolean; data: { token: string } }>(
        '/api/v1/auth/refresh',
        {
          baseURL,
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
          },
        }
      );

      if (response.success && response.data.token) {
        const tokenCookie = useCookie('auth_token');
        tokenCookie.value = response.data.token;

        // Drain all pending requests
        const queued = pendingRequests;
        pendingRequests = [];
        queued.forEach(({ resolve }) => resolve(response.data.token));

        return response.data.token;
      } else {
        throw new Error('Token refresh failed');
      }
    } catch (error) {
      // Reject all pending requests
      const queued = pendingRequests;
      pendingRequests = [];
      queued.forEach(({ reject }) => reject(error));

      // Clear auth and redirect
      const tokenCookie = useCookie('auth_token');
      tokenCookie.value = null;
      throw error;
    } finally {
      isRefreshing = false;
    }
  }

  const apiFetch = $fetch.create({
    baseURL,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    onRequest({ options }) {
      // Add auth token if available
      const token = useCookie('auth_token').value;
      if (token) {
        options.headers.set('Authorization', `Bearer ${token}`);
      }

      // Add accept-language header
      const locale = useI18n().locale.value;
      options.headers.set('Accept-Language', locale || 'ar');
    },
    onResponseError({ response }) {
      // Get correlation ID from response headers (convert null -> undefined)
      const correlationId = response.headers.get('x-correlation-id') || undefined;

      // Handle different status codes
      if (response.status === 401) {
        // Token expired or invalid
        const cookie = useCookie('auth_token');
        cookie.value = null;
        errorHandler.handleError({
          code: 'AUTH_TOKEN_EXPIRED',
          message: errorHandler.getLocalizedMessage('AUTH_TOKEN_EXPIRED'),
          correlationId,
          statusCode: 401,
        });
        navigateTo('/auth/login');
        return;
      }

      if (response.status === 403) {
        errorHandler.handleError({
          code: 'RBAC_ROLE_DENIED',
          message: errorHandler.getLocalizedMessage('RBAC_ROLE_DENIED'),
          correlationId,
          statusCode: 403,
        });
        return;
      }

      if (response.status === 404) {
        errorHandler.handleError({
          code: 'RESOURCE_NOT_FOUND',
          message: errorHandler.getLocalizedMessage('RESOURCE_NOT_FOUND'),
          correlationId,
          statusCode: 404,
        });
        return;
      }

      if (response.status === 429) {
        errorHandler.handleError({
          code: 'RATE_LIMIT_EXCEEDED',
          message: errorHandler.getLocalizedMessage('RATE_LIMIT_EXCEEDED'),
          correlationId,
          statusCode: 429,
        });
        return;
      }

      if (response.status >= 500) {
        errorHandler.handleError({
          code: 'SERVER_ERROR',
          message: errorHandler.getLocalizedMessage('SERVER_ERROR'),
          correlationId,
          statusCode: response.status,
        });
        return;
      }

      // Try to parse JSON error response
      try {
        const data = response._data || {};
        if (data.success === false && data.error) {
          errorHandler.handleError({
            code: data.error.code || 'SERVER_ERROR',
            message: data.error.message || errorHandler.getLocalizedMessage('SERVER_ERROR'),
            details: data.error.details,
            correlationId,
            statusCode: response.status,
          });
        } else {
          errorHandler.handleError({
            code: 'SERVER_ERROR',
            message: errorHandler.getLocalizedMessage('SERVER_ERROR'),
            correlationId,
            statusCode: response.status,
          });
        }
      } catch {
        errorHandler.handleError({
          code: 'SERVER_ERROR',
          message: errorHandler.getLocalizedMessage('SERVER_ERROR'),
          correlationId,
          statusCode: response.status,
        });
      }
    },
  });

  return { apiFetch, refreshToken };
}
