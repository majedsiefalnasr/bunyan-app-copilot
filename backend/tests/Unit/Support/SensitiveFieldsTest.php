<?php

namespace Tests\Unit\Support;

use App\Support\SensitiveFields;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * SensitiveFieldsTest — Unit Tests
 *
 * Test sensitive field masking and registry.
 */
class SensitiveFieldsTest extends TestCase
{
    #[Test]
    public function it_masks_passwords_fully(): void
    {
        $data = ['password' => 'super-secret-pass-123', 'username' => 'user@example.com'];
        $masked = SensitiveFields::mask($data);

        $this->assertEquals('***', $masked['password']);
        $this->assertEquals('user@example.com', $masked['username']);
    }

    #[Test]
    public function it_masks_tokens_with_truncate_rule(): void
    {
        $data = ['api_token' => 'tok_test_1234567890abcdef'];
        $masked = SensitiveFields::mask($data);

        $this->assertStringStartsWith('tok_', $masked['api_token']);
        $this->assertStringContainsString('****', $masked['api_token']);
        $this->assertNotContainString('1234567890abcdef', $masked['api_token']);
    }

    #[Test]
    public function it_masks_credit_cards_partial(): void
    {
        $data = ['credit_card' => '4532123456789010'];
        $masked = SensitiveFields::mask($data);

        $this->assertStringEndsWith('9010', $masked['credit_card']);
        $this->assertStringStartsWith('****', $masked['credit_card']);
    }

    #[Test]
    public function it_masks_nested_arrays(): void
    {
        $data = [
            'user' => [
                'email' => 'user@example.com',
                'password' => 'secret',
                'profile' => [
                    'phone' => '555-1234',
                    'api_secret' => 'sk_test_abc123def456',
                ],
            ],
        ];

        $masked = SensitiveFields::mask($data);

        $this->assertEquals('***', $masked['user']['password']);
        $this->assertStringContainsString('****', $masked['user']['profile']['phone']);
        $this->assertEquals('***', $masked['user']['profile']['api_secret']);
        $this->assertStringContainsString('****', $masked['user']['email']);
    }

    #[Test]
    public function it_identifies_sensitive_field_names(): void
    {
        $this->assertTrue(SensitiveFields::isSensitive('password'));
        $this->assertTrue(SensitiveFields::isSensitive('api_token'));
        $this->assertTrue(SensitiveFields::isSensitive('credit_card'));
        $this->assertTrue(SensitiveFields::isSensitive('email')); // email is sensitive (partial mask)
        $this->assertFalse(SensitiveFields::isSensitive('username'));
        $this->assertFalse(SensitiveFields::isSensitive('project_name'));
    }

    #[Test]
    public function it_masks_case_insensitively(): void
    {
        $data = [
            'Password' => 'secret',
            'PASSWORD' => 'another_secret',
            'Api_Token' => 'tok_123',
        ];

        $masked = SensitiveFields::mask($data);

        $this->assertEquals('***', $masked['Password']);
        $this->assertEquals('***', $masked['PASSWORD']);
        $this->assertStringContainsString('****', $masked['Api_Token']);
    }

    #[Test]
    public function it_preserves_non_sensitive_data(): void
    {
        $data = [
            'name' => 'John Doe',
            'project_id' => 123,
            'status' => 'active',
            'created_at' => '2026-01-01',
        ];

        $masked = SensitiveFields::mask($data);

        $this->assertEquals($data, $masked);
    }

    #[Test]
    public function it_handles_empty_arrays(): void
    {
        $data = [];
        $masked = SensitiveFields::mask($data);

        $this->assertEquals([], $masked);
    }

    #[Test]
    public function it_masks_multiple_sensitive_fields(): void
    {
        $data = [
            'password' => 'pass123',
            'credit_card' => '4532-1111-2222-3333',
            'api_token' => 'sk_live_abc123',
            'ssn' => '123-45-6789',
            'token' => 'eyJhbGc...',
        ];

        $masked = SensitiveFields::mask($data);

        $this->assertEquals('***', $masked['password']);
        $this->assertStringEndsWith('3333', $masked['credit_card']);
        $this->assertStringContainsString('****', $masked['api_token']);
        $this->assertStringEndsWith('6789', $masked['ssn']);
        $this->assertStringContainsString('****', $masked['token']);
    }

    /**
     * Helper to verify string does NOT contain substring.
     */
    private function assertNotContainString(string $needle, string $haystack): void
    {
        $this->assertFalse(str_contains($haystack, $needle), "String '$haystack' should not contain '$needle'");
    }
}
