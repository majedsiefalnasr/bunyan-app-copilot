<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\ApiErrorCode;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->success($result, statusCode: 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->success($result);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(null);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->forgotPassword($request->validated()['email']);

        return $this->success(null);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->authService->resetPassword($request->validated());

        return $this->success(null);
    }

    public function user(Request $request): JsonResponse
    {
        $result = $this->authService->getProfile($request->user());

        return $this->success($result);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $result = $this->authService->updateProfile($request->user(), $request->validated());

        return $this->success($result);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $user = User::findOrFail($request->route('id'));

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash'))) {
            return $this->error(ApiErrorCode::AUTH_UNAUTHORIZED, 'Invalid verification link.');
        }

        $this->authService->verifyEmail($user);

        return $this->success(null);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $this->authService->resendVerification($request->user());

        return $this->success(null);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $result = $this->authService->rotateToken($request->user());

        return $this->success($result);
    }
}
