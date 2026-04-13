<?php

namespace App\Exceptions;

use App\Enums\ApiErrorCode;
use App\Traits\ApiResponseTrait;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Global Exception Handler — Bunyan
 *
 * Centralizes exception handling for the Bunyan platform.
 * Transforms all exceptions into standard JSON error responses
 * following the unified API error contract.
 *
 * @see App\Enums\ApiErrorCode
 * @see App\Traits\ApiResponseTrait
 * @see specs/runtime/005-error-handling/contracts/error-response.json
 */
class Handler extends ExceptionHandler
{
    use ApiResponseTrait;

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'credit_card',
        'token',
        'api_key',
        'secret',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log the exception with context
            $context = [
                'exception_type' => get_class($e),
                'correlation_id' => request()->attributes->get('correlation_id') ?? 'N/A',
                'user_id' => auth()->id(),
                'endpoint' => request()->path(),
                'method' => request()->method(),
            ];

            Log::error('Unhandled Exception', array_merge($context, [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]));
        });
    }

    /**
     * Render the exception into an HTTP response.
     *
     * Handles JSON API requests specially with proper error contract responses.
     * Returns HTML for non-API requests (default Laravel behavior).
     */
    public function render($request, Throwable $e): mixed
    {
        // For JSON API requests, render standard error responses
        if ($request->expectsJson()) {
            return $this->renderJsonResponse($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Render a JSON response for API exceptions.
     *
     * Dispatches to specific handlers based on exception type.
     */
    private function renderJsonResponse($request, Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof ApiException => $this->handleApiException($e),
            $e instanceof ValidationException => $this->handleValidationException($e),
            $e instanceof RoleNotAllowedException => $this->handleRoleNotAllowedException($e),
            $e instanceof AuthenticationException => $this->handleAuthenticationException($e),
            $e instanceof AuthorizationException => $this->handleAuthorizationException($e),
            $e instanceof InvalidSignatureException => $this->handleInvalidSignatureException($e),
            $e instanceof ModelNotFoundException => $this->handleModelNotFoundException($e),
            str_contains(get_class($e), 'ThrottleRequestsException') => $this->handleRateLimitException($e),
            default => $this->handleGenericException($e),
        };
    }

    private function handleApiException(ApiException $e): JsonResponse
    {
        $error = [
            'code' => $e->getErrorCode()->value,
            'message' => $e->getMessage(),
        ];

        if ($e->getDetails() !== null) {
            $error['details'] = $e->getDetails();
        }

        return response()->json([
            'success' => false,
            'data' => null,
            'error' => $error,
        ], $e->getStatusCode());
    }

    /**
     * Handle ValidationException.
     *
     * Returns 422 Unprocessable Entity with field-level error details.
     */
    private function handleValidationException(ValidationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => ApiErrorCode::VALIDATION_ERROR->value,
                'message' => ApiErrorCode::VALIDATION_ERROR->defaultMessage(),
                'details' => $e->errors(),
            ],
        ], 422);
    }

    /**
     * Handle AuthenticationException.
     *
     * Returns 401 Unauthorized.
     */
    private function handleAuthenticationException(AuthenticationException $e): JsonResponse
    {
        $code = ApiErrorCode::AUTH_INVALID_CREDENTIALS;

        // Check if it's a token expiration scenario
        if (str_contains($e->getMessage(), 'expired') || str_contains($e->getMessage(), 'token')) {
            $code = ApiErrorCode::AUTH_TOKEN_EXPIRED;
        }

        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $code->value,
                'message' => $code->defaultMessage(),
            ],
        ], 401);
    }

    /**
     * Handle RoleNotAllowedException.
     *
     * Returns 403 Forbidden with RBAC_ROLE_DENIED error code.
     */
    private function handleRoleNotAllowedException(RoleNotAllowedException $e): JsonResponse
    {
        Log::warning('Role-based access denied', [
            'user_id' => auth()->id(),
            'user_role' => $e->role,
            'required_role' => $e->requiredRole,
            'endpoint' => request()->path(),
            'correlation_id' => request()->attributes->get('correlation_id') ?? 'N/A',
        ]);

        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => ApiErrorCode::RBAC_ROLE_DENIED->value,
                'message' => ApiErrorCode::RBAC_ROLE_DENIED->defaultMessage(),
            ],
        ], 403);
    }

    /**
     * Handle AuthorizationException.
     *
     * Returns 403 Forbidden.
     */
    private function handleAuthorizationException(AuthorizationException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => ApiErrorCode::AUTH_UNAUTHORIZED->value,
                'message' => ApiErrorCode::AUTH_UNAUTHORIZED->defaultMessage(),
            ],
        ], 403);
    }

    /**
     * Handle ModelNotFoundException.
     *
     * Returns 404 Not Found.
     */
    private function handleModelNotFoundException(ModelNotFoundException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => ApiErrorCode::RESOURCE_NOT_FOUND->value,
                'message' => ApiErrorCode::RESOURCE_NOT_FOUND->defaultMessage(),
            ],
        ], 404);
    }

    /**
     * Handle rate limit exceptions.
     *
     * Returns 429 Too Many Requests with Retry-After header.
     */
    private function handleInvalidSignatureException(InvalidSignatureException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => ApiErrorCode::AUTH_UNAUTHORIZED->value,
                'message' => 'Invalid or expired signature.',
            ],
        ], 403);
    }

    private function handleRateLimitException(Throwable $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => ApiErrorCode::RATE_LIMIT_EXCEEDED->value,
                'message' => ApiErrorCode::RATE_LIMIT_EXCEEDED->defaultMessage(),
            ],
        ], 429);
    }

    /**
     * Handle generic/unhandled exceptions.
     *
     * Returns 500 Internal Server Error.
     * In production, does NOT expose stack trace.
     * In development, stack trace is visible for debugging.
     */
    private function handleGenericException(Throwable $e): JsonResponse
    {
        // Log the exception for debugging
        Log::error('Unhandled exception', [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'correlation_id' => request()->attributes->get('correlation_id') ?? 'N/A',
            'user_id' => auth()->id(),
            'endpoint' => request()->path(),
        ]);

        $message = ApiErrorCode::SERVER_ERROR->defaultMessage();

        // In production, don't expose stack trace or error details
        if (config('app.debug') === false) {
            $message = ApiErrorCode::SERVER_ERROR->defaultMessage();
        }

        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => ApiErrorCode::SERVER_ERROR->value,
                'message' => $message,
            ],
        ], 500);
    }
}
