import { useErrorHandler } from './useErrorHandler';

export function useApi() {
    const config = useRuntimeConfig();
    const errorHandler = useErrorHandler();

    const baseURL =
        typeof config.public.apiBaseUrl === 'string' && config.public.apiBaseUrl
            ? config.public.apiBaseUrl
            : 'http://localhost:8000';

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
                        message:
                            data.error.message || errorHandler.getLocalizedMessage('SERVER_ERROR'),
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

    return { apiFetch };
}
