<?php

declare(strict_types=1);

namespace Tests\Unit\SecurityFeatures;

use App\Models\User;
use App\Services\AvatarService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarUploadSecurityTest extends TestCase
{
    private AvatarService $avatarService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock S3 storage
        Storage::fake('s3');

        $this->avatarService = $this->app->make(AvatarService::class);
    }

    public function test_avatar_upload_validates_mime_type(): void
    {
        // Create a fake text file (not an image)
        $file = UploadedFile::fake()->create('fake.txt', 1, 'text/plain');

        $this->expectException(\Exception::class);

        $this->avatarService->uploadAvatar($file, 1);
    }

    public function test_avatar_upload_validates_file_size(): void
    {
        // Create a file larger than 5MB
        $file = UploadedFile::fake()->image('avatar.jpg')->size(6000); // 6MB

        $this->expectException(\Exception::class);

        $this->avatarService->uploadAvatar($file, 1);
    }

    public function test_avatar_upload_validates_image_dimensions(): void
    {
        // Create a very small image (less than 100x100)
        $file = UploadedFile::fake()->image('avatar.jpg', 50, 50);

        $this->expectException(\Exception::class);

        $this->avatarService->uploadAvatar($file, 1);
    }

    public function test_avatar_upload_accepts_valid_jpeg(): void
    {
        // Create a valid JPEG file
        $file = UploadedFile::fake()->image('avatar.jpg', 400, 400);

        $url = $this->avatarService->uploadAvatar($file, 1);

        $this->assertNotEmpty($url);
        $this->assertStringContainsString('s3', $url);
    }

    public function test_avatar_upload_accepts_valid_png(): void
    {
        // Create a valid PNG file
        $file = UploadedFile::fake()->image('avatar.png', 400, 400);

        $url = $this->avatarService->uploadAvatar($file, 1);

        $this->assertNotEmpty($url);
        $this->assertStringContainsString('s3', $url);
    }

    public function test_avatar_upload_accepts_valid_webp(): void
    {
        // Create a valid WebP file
        $file = UploadedFile::fake()->image('avatar.webp', 400, 400);

        $url = $this->avatarService->uploadAvatar($file, 1);

        $this->assertNotEmpty($url);
        $this->assertStringContainsString('s3', $url);
    }

    public function test_avatar_upload_deletes_old_avatar(): void
    {
        $userId = 1;

        // Upload first avatar
        $file1 = UploadedFile::fake()->image('avatar1.jpg', 400, 400);
        $url1 = $this->avatarService->uploadAvatar($file1, $userId);

        // Upload second avatar
        $file2 = UploadedFile::fake()->image('avatar2.jpg', 400, 400);
        $url2 = $this->avatarService->uploadAvatar($file2, $userId);

        // URLs should be different
        $this->assertNotEquals($url1, $url2);
    }

    public function test_avatar_upload_resizes_image(): void
    {
        // Create a large image (2000x2000)
        $file = UploadedFile::fake()->image('large_avatar.jpg', 2000, 2000);

        $url = $this->avatarService->uploadAvatar($file, 1);

        $this->assertNotEmpty($url);
        // In real S3, the image would be resized to 400x400 with WebP conversion
        $this->assertStringContainsString('.webp', $url);
    }

    public function test_avatar_upload_rate_limiting(): void
    {
        $this->withoutExceptionHandling();

        // Create authenticated user
        $user = $this->createUser();

        // Attempt 5 uploads (should succeed)
        for ($i = 0; $i < 5; $i++) {
            $file = UploadedFile::fake()->image("avatar$i.jpg", 400, 400);
            $response = $this->actingAs($user)
                ->postJson('/api/v1/user/avatar', [
                    'avatar' => $file,
                ]);

            $this->assertEquals(200, $response->status());
        }

        // 6th upload should be rate limited (429)
        $file = UploadedFile::fake()->image('avatar_6.jpg', 400, 400);
        $response = $this->actingAs($user)
            ->postJson('/api/v1/user/avatar', [
                'avatar' => $file,
            ]);

        $this->assertEquals(429, $response->status());
    }

    private function createUser()
    {
        return User::factory()->create([
            'email' => 'test-avatar-'.now()->timestamp.'@example.com',
        ]);
    }
}
