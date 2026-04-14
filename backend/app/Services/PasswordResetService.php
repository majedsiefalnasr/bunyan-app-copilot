<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApiErrorCode;
use App\Exceptions\ApiException;
use App\Models\User;
use App\Repositories\PasswordHistoryRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/**
 * PasswordResetService — Password Reset Hardening
 *
 * Implements secure password reset flow with hardening checks:
 * - Token expiration: 1 hour (default Laravel config)
 * - Single-use tokens: Token invalidated after successful reset
 * - Password reuse prevention: Users cannot reuse recent N passwords
 * - Rate limiting: Max 3 reset requests per 60 minutes (via middleware)
 * - Token rotation: All active sessions invalidated on reset
 *
 * @see T048
 */
class PasswordResetService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PasswordHistoryRepository $passwordHistoryRepository,
    ) {}

    /**
     * Initiate forgot password flow.
     *
     * Always returns success to prevent email enumeration attack.
     * Backend sends reset email only to existing users.
     */
    public function initiate(string $email): void
    {
        // Send reset link (Laravel Password::sendResetLink handles email existence check)
        // Always returns success even if user doesn't exist (security best practice)
        Password::sendResetLink(['email' => $email]);
    }

    /**
     * Reset password with security hardening checks.
     *
     * Validates:
     * - Token is valid and not expired (1 hour default)
     * - New password is not recently used
     * - Password confirmation matches
     * - Token is single-use (revoked after use)
     * - All active tokens invalidated (force re-login)
     *
     * @param  array{email: string, token: string, password: string, password_confirmation: string}  $data
     *
     * @throws ApiException If token expired, password reused, or other validation fails
     */
    public function reset(array $data): void
    {
        DB::transaction(function () use ($data) {
            // Find user by email
            $user = $this->userRepository->findByEmail($data['email']);

            if (! $user) {
                // Don't reveal user doesn't exist (security)
                throw ApiException::make(
                    ApiErrorCode::VALIDATION_ERROR,
                    'Invalid reset token or expired link'
                );
            }

            // Check if password has been recently used (reuse prevention)
            $currentPasswordHash = $user->password;
            if (Hash::check($data['password'], $currentPasswordHash)) {
                throw ApiException::make(
                    ApiErrorCode::VALIDATION_ERROR,
                    'New password cannot be the same as current password'
                );
            }

            // Check recent password history (prevent reusing last N passwords)
            if ($this->passwordHistoryRepository->isPasswordReused($user->id, $data['password'], 3)) {
                throw ApiException::make(
                    ApiErrorCode::VALIDATION_ERROR,
                    'Password has been used recently. Please choose a different password'
                );
            }

            // Reset password via Laravel's Password broker
            // This validates token expiry and single-use constraint
            $status = Password::reset(
                $data,
                function (User $resetUser, string $newPassword) use ($user) {
                    // Store old password in history BEFORE updating to new password
                    $this->passwordHistoryRepository->recordPasswordChange(
                        $user->id,
                        $user->password // Store OLD hash in history
                    );

                    // Update to new password
                    $resetUser->password = $newPassword;
                    $resetUser->save();

                    // Revoke ALL existing tokens (force re-login for security)
                    $resetUser->tokens()->delete();
                }
            );

            if ($status !== Password::PASSWORD_RESET) {
                throw ApiException::make(
                    ApiErrorCode::VALIDATION_ERROR,
                    'Invalid reset token or expired link'
                );
            }
        });
    }
}
