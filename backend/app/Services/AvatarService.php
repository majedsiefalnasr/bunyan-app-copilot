<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ApiErrorCode;
use App\Exceptions\ApiException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class AvatarService
{
    private const AVATAR_DIRECTORY = 'avatars';

    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    private const RESIZE_WIDTH = 400;

    private const RESIZE_HEIGHT = 400;

    public function uploadAvatar(UploadedFile $file, int $userId): string
    {
        // Validate MIME type
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES)) {
            throw new ApiException(
                ApiErrorCode::VALIDATION_ERROR,
                'Invalid file type. Only JPEG, PNG, and WebP are allowed.',
                ['mime_type' => $file->getMimeType()]
            );
        }

        // Validate magic bytes (file signature)
        $this->validateMagicBytes($file);

        // Delete old avatar if it exists
        $this->deleteOldAvatar($userId);

        // Process and resize image
        $manager = new ImageManager(new Driver);
        $image = $manager->read($file->getRealPath());

        // Resize to 400x400 (contain)
        $image->scaleDown(
            width: self::RESIZE_WIDTH,
            height: self::RESIZE_HEIGHT
        );

        // Generate filename
        $filename = sprintf(
            'user_%d_%s.webp',
            $userId,
            now()->timestamp
        );

        // Save to storage
        $path = self::AVATAR_DIRECTORY.'/'.$filename;
        $avatarData = $image->toWebp(quality: 80)->toString();

        Storage::disk('s3')->put(
            $path,
            $avatarData,
            [
                'visibility' => 'public',
                'CacheControl' => 'max-age=2592000', // 30 days
            ]
        );

        return Storage::disk('s3')->url($path);
    }

    /**
     * Validate file magic bytes to prevent spoofed file uploads
     */
    private function validateMagicBytes(UploadedFile $file): void
    {
        $fileStream = fopen($file->getRealPath(), 'rb');
        $bytes = unpack('C*', fread($fileStream, 8));
        fclose($fileStream);

        $isValid = false;

        // Check JPEG magic bytes
        if (isset($bytes[1]) && $bytes[1] === 0xFF && isset($bytes[2]) && $bytes[2] === 0xD8) {
            $isValid = true;
        }

        // Check PNG magic bytes
        if (
            isset($bytes[1]) && $bytes[1] === 0x89 &&
            isset($bytes[2]) && $bytes[2] === 0x50 &&
            isset($bytes[3]) && $bytes[3] === 0x4E &&
            isset($bytes[4]) && $bytes[4] === 0x47
        ) {
            $isValid = true;
        }

        // Check WebP magic bytes (RIFF....WEBP)
        if (
            isset($bytes[1]) && $bytes[1] === 0x52 &&
            isset($bytes[2]) && $bytes[2] === 0x49 &&
            isset($bytes[3]) && $bytes[3] === 0x46 &&
            isset($bytes[4]) && $bytes[4] === 0x46
        ) {
            $isValid = true;
        }

        if (! $isValid) {
            throw new ApiException(
                ApiErrorCode::VALIDATION_ERROR,
                'Invalid file signature. The file may not be a valid image.'
            );
        }
    }

    /**
     * Delete old avatar for user
     */
    private function deleteOldAvatar(int $userId): void
    {
        $files = Storage::disk('s3')->files(self::AVATAR_DIRECTORY);

        foreach ($files as $file) {
            if (str_contains($file, (string) $userId)) {
                Storage::disk('s3')->delete($file);
            }
        }
    }
}
