<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApiErrorCode;
use App\Enums\UserRole;
use App\Exceptions\ApiException;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
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
    public function login(array $credentials): array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ApiException::make(ApiErrorCode::AUTH_INVALID_CREDENTIALS);
        }

        if (! $user->is_active) {
            throw ApiException::make(ApiErrorCode::AUTH_UNAUTHORIZED, 'Your account has been deactivated.');
        }

        $token = $user->createToken('api')->plainTextToken;

        return [
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
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
}
