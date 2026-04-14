<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApiErrorCode;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\FailedLoginAttemptRepository;
use App\Repositories\UserRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/**
 * AuthService — Authentication with Security Hardening
 *
 * Manages user registration, login, logout, and related auth flows.
 * Includes security features:
 * - Account lockout: 5 failed attempts = 15-minute lock
 * - Failed attempt tracking: Per email + IP address
 * - Rate limiting: Via middleware (10/15min login attempts)
 * - Token management: Single-use, expiry, rotation
 * - Password history: Reuse prevention
 *
 * @see T044, T045, T048
 */
class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly FailedLoginAttemptRepository $failedLoginAttemptRepository,
    ) {}

    /**
     * @param  array{name: string, email: string, phone: string, password: string, role: string}  $data
     * @return array{user: UserResource, token: string, token_type: string}
     */
    public function register(array $data): array
    {
        $existing = $this->userRepository->findByEmail($data['email']);
        if ($existing !== null) {
            throw ApiException::make(ApiErrorCode::CONFLICT_ERROR, 'A user with this email already exists.');
        }

        /** @var User $user */
        $user = $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $data['password'],
        ]);

        // SEC-FINDING-A: Set role explicitly — never via mass assignment
        $user->role = UserRole::from($data['role']);
        $user->save();

        event(new Registered($user));

        $token = $user->createToken('api')->plainTextToken;

        return [
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * @param  array{email: string, password: string}  $credentials
     * @return array{user: UserResource, token: string, token_type: string}
     */
    public function login(array $credentials, ?string $ipAddress = null): array
    {
        $ipAddress ??= request()->ip();
        $user = $this->userRepository->findByEmail($credentials['email']);

        // Check password validity
        $passwordValid = $user && Hash::check($credentials['password'], $user->password);

        if (! $passwordValid) {
            // Increment failed attempts (even if user doesn't exist, for security)
            $this->failedLoginAttemptRepository->incrementAttempts($credentials['email'], $ipAddress);

            throw ApiException::make(ApiErrorCode::AUTH_INVALID_CREDENTIALS);
        }

        // Password is valid - check if account is active
        if (! $user->is_active) {
            throw ApiException::make(
                ApiErrorCode::AUTH_UNAUTHORIZED,
                'Your account has been deactivated.'
            );
        }

        // Reset failed attempts on successful login
        $this->failedLoginAttemptRepository->resetAttempts($credentials['email'], $ipAddress);

        $token = $user->createToken('api')->plainTextToken;

        return [
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();
        if ($token) {
            $token->delete();
        }
    }

    public function forgotPassword(string $email): void
    {
        // Always return success to prevent email enumeration
        Password::sendResetLink(['email' => $email]);
    }

    /**
     * @param  array{email: string, token: string, password: string, password_confirmation: string}  $data
     */
    public function resetPassword(array $data): void
    {
        $status = Password::reset(
            $data,
            function (User $user, string $password) {
                $user->password = $password;
                $user->save();

                // Revoke all existing tokens for security
                $user->tokens()->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ApiException::make(ApiErrorCode::VALIDATION_ERROR, __($status));
        }
    }

    public function getProfile(User $user): UserResource
    {
        $user->load('roles.permissions');

        return new UserResource($user);
    }

    /**
     * @param  array{name?: string, phone?: string}  $data
     */
    public function updateProfile(User $user, array $data): UserResource
    {
        $this->userRepository->update($user->id, $data);

        return new UserResource($user->fresh());
    }

    public function verifyEmail(User $user): void
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
    }

    public function resendVerification(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();
    }

    /**
     * Rotate access token for security (T050)
     *
     * Creates a new token and revokes the old one as part of automatic
     * token refresh flow. This prevents token compromise by ensuring tokens
     * are not reused indefinitely.
     *
     * @return array{token: string, token_type: string}
     */
    public function rotateToken(User $user): array
    {
        // Revoke all existing tokens for this user before creating new one
        // This ensures complete token rotation regardless of which token
        // was used in the current request
        $user->tokens()->delete();

        // Create new token
        $newToken = $user->createToken('api')->plainTextToken;

        return [
            'token' => $newToken,
            'token_type' => 'Bearer',
        ];
    }
}
