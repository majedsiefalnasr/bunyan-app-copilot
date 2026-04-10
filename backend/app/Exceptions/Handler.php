<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($request->wantsJson()) {
            if ($exception instanceof ValidationException) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'message' => 'Validation failed',
                    'errors' => $exception->errors(),
                ], 422);
            }

            $statusCode = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;
            $message = match ($statusCode) {
                401 => 'Unauthenticated',
                403 => 'Unauthorized',
                404 => 'Not Found',
                default => $exception->getMessage() ?: 'Internal Server Error',
            };

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $message,
                'errors' => [],
            ], $statusCode);
        }

        return parent::render($request, $exception);
    }
}
