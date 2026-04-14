<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\AvatarUploadRequest;
use App\Services\AvatarService;
use Illuminate\Http\JsonResponse;

class UserController extends BaseController
{
    public function __construct(
        private readonly AvatarService $avatarService,
    ) {}

    public function uploadAvatar(AvatarUploadRequest $request): JsonResponse
    {
        $avatarUrl = $this->avatarService->uploadAvatar(
            file: $request->file('avatar'),
            userId: $request->user()->id
        );

        return $this->success([
            'avatarUrl' => $avatarUrl,
        ]);
    }
}
