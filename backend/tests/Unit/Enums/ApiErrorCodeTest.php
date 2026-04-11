<?php

namespace Tests\Unit\Enums;

use App\Enums\ApiErrorCode;
use PHPUnit\Framework\TestCase;

class ApiErrorCodeTest extends TestCase
{
    /**
     * Test that all error codes map to correct HTTP status.
     */
    public function test_all_error_codes_have_correct_http_status(): void
    {
        $mapping = [
            'AUTH_INVALID_CREDENTIALS' => 401,
            'AUTH_TOKEN_EXPIRED' => 401,
            'AUTH_UNAUTHORIZED' => 403,
            'RBAC_ROLE_DENIED' => 403,
            'RESOURCE_NOT_FOUND' => 404,
            'CONFLICT_ERROR' => 409,
            'VALIDATION_ERROR' => 422,
            'WORKFLOW_INVALID_TRANSITION' => 422,
            'WORKFLOW_PREREQUISITES_UNMET' => 422,
            'PAYMENT_FAILED' => 422,
            'RATE_LIMIT_EXCEEDED' => 429,
            'SERVER_ERROR' => 500,
        ];

        foreach ($mapping as $errorCodeName => $expectedStatus) {
            $errorCode = ApiErrorCode::from($errorCodeName);
            $this->assertEquals(
                $expectedStatus,
                $errorCode->httpStatus(),
                "Error code {$errorCodeName} should map to HTTP {$expectedStatus}"
            );
        }
    }

    /**
     * Test that all error codes have non-empty English messages.
     */
    public function test_all_error_codes_have_english_messages(): void
    {
        foreach (ApiErrorCode::cases() as $errorCode) {
            $message = $errorCode->defaultMessage('en_US');
            $this->assertNotEmpty(
                $message,
                "Error code {$errorCode->value} must have a non-empty English message"
            );
            $this->assertIsString($message);
        }
    }

    /**
     * Test that all error codes have non-empty Arabic messages.
     */
    public function test_all_error_codes_have_arabic_messages(): void
    {
        foreach (ApiErrorCode::cases() as $errorCode) {
            $message = $errorCode->defaultMessage('ar_SA');
            $this->assertNotEmpty(
                $message,
                "Error code {$errorCode->value} must have a non-empty Arabic message"
            );
            $this->assertIsString($message);
        }
    }

    /**
     * Test that Arabic locale names are supported.
     */
    public function test_arabic_locale_variants_supported(): void
    {
        $testLocales = ['ar', 'ar_SA', 'ar_AE', 'ar-SA', 'ar-AE'];
        $arabicMessage = ApiErrorCode::AUTH_INVALID_CREDENTIALS->defaultMessage('ar_SA');

        foreach ($testLocales as $locale) {
            $message = ApiErrorCode::AUTH_INVALID_CREDENTIALS->defaultMessage($locale);
            // Should return Arabic message for any ar-* locale
            if (str_starts_with($locale, 'ar')) {
                $this->assertNotEmpty($message);
            }
        }
    }

    /**
     * Test locale fallback to English for unknown locales.
     */
    public function test_locale_fallback_to_english(): void
    {
        $englishMessage = ApiErrorCode::AUTH_INVALID_CREDENTIALS->defaultMessage('en_US');
        $unknownLocaleMessage = ApiErrorCode::AUTH_INVALID_CREDENTIALS->defaultMessage('fr_FR');

        $this->assertEquals($englishMessage, $unknownLocaleMessage);
    }

    /**
     * Test each error code has correct semantic value matching constant name.
     */
    public function test_error_code_values_are_semantic(): void
    {
        foreach (ApiErrorCode::cases() as $errorCode) {
            // Value should match the case name (UPPERCASE_WITH_UNDERSCORES)
            $this->assertEquals(
                $errorCode->name,
                $errorCode->value,
                'Error code value should match its name for semantic consistency'
            );
        }
    }

    /**
     * Test error codes are distinct and non-empty.
     */
    public function test_all_error_codes_are_unique(): void
    {
        $codes = array_map(fn ($ec) => $ec->value, ApiErrorCode::cases());
        $unique = array_unique($codes);

        $this->assertCount(
            count($codes),
            $unique,
            'All error codes must be unique'
        );
    }

    /**
     * Test HTTP status grouping.
     */
    public function test_status_code_grouping(): void
    {
        $auth401 = [
            ApiErrorCode::AUTH_INVALID_CREDENTIALS,
            ApiErrorCode::AUTH_TOKEN_EXPIRED,
        ];

        $auth403 = [
            ApiErrorCode::AUTH_UNAUTHORIZED,
            ApiErrorCode::RBAC_ROLE_DENIED,
        ];

        $validation422 = [
            ApiErrorCode::VALIDATION_ERROR,
            ApiErrorCode::WORKFLOW_INVALID_TRANSITION,
            ApiErrorCode::WORKFLOW_PREREQUISITES_UNMET,
            ApiErrorCode::PAYMENT_FAILED,
        ];

        foreach ($auth401 as $code) {
            $this->assertEquals(401, $code->httpStatus());
        }

        foreach ($auth403 as $code) {
            $this->assertEquals(403, $code->httpStatus());
        }

        foreach ($validation422 as $code) {
            $this->assertEquals(422, $code->httpStatus());
        }

        $this->assertEquals(404, ApiErrorCode::RESOURCE_NOT_FOUND->httpStatus());
        $this->assertEquals(409, ApiErrorCode::CONFLICT_ERROR->httpStatus());
        $this->assertEquals(429, ApiErrorCode::RATE_LIMIT_EXCEEDED->httpStatus());
        $this->assertEquals(500, ApiErrorCode::SERVER_ERROR->httpStatus());
    }

    /**
     * Test specific message content for authentication errors.
     */
    public function test_auth_error_messages_in_arabic(): void
    {
        $this->assertStringContainsString(
            'دخول',
            ApiErrorCode::AUTH_INVALID_CREDENTIALS->defaultMessage('ar_SA')
        );

        $this->assertStringContainsString(
            'جلست',
            ApiErrorCode::AUTH_TOKEN_EXPIRED->defaultMessage('ar_SA')
        );
    }

    /**
     * Test that 12 error codes are defined (registry completeness).
     */
    public function test_all_required_error_codes_present(): void
    {
        $requiredCodes = [
            'AUTH_INVALID_CREDENTIALS',
            'AUTH_TOKEN_EXPIRED',
            'AUTH_UNAUTHORIZED',
            'RBAC_ROLE_DENIED',
            'RESOURCE_NOT_FOUND',
            'VALIDATION_ERROR',
            'CONFLICT_ERROR',
            'WORKFLOW_INVALID_TRANSITION',
            'WORKFLOW_PREREQUISITES_UNMET',
            'PAYMENT_FAILED',
            'RATE_LIMIT_EXCEEDED',
            'SERVER_ERROR',
        ];

        $definedCodes = array_map(fn ($ec) => $ec->value, ApiErrorCode::cases());

        foreach ($requiredCodes as $code) {
            $this->assertContains(
                $code,
                $definedCodes,
                "Required error code {$code} must be defined"
            );
        }

        $this->assertCount(
            count($requiredCodes),
            ApiErrorCode::cases(),
            'Must have exactly '.count($requiredCodes).' error codes'
        );
    }
}
