<?php

namespace Tests\Unit\Traits;

use App\Enums\ApiErrorCode;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class ApiResponseTraitTest extends TestCase
{
    use ApiResponseTrait;

    /**
     * Test success response format.
     */
    public function test_success_response_format(): void
    {
        $data = ['id' => 1, 'name' => 'Test Project'];
        $response = $this->success($data, null, 200);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->status());

        $json = $response->getData(true);
        $this->assertTrue($json['success']);
        $this->assertEquals($data, $json['data']);
        $this->assertNull($json['error']);
    }

    /**
     * Test success response with custom status code.
     */
    public function test_success_response_with_custom_status(): void
    {
        $response = $this->success(['id' => 1], null, 201);

        $this->assertEquals(201, $response->status());
        $this->assertTrue($response->getData(true)['success']);
    }

    /**
     * Test success response with null data.
     */
    public function test_success_response_with_null_data(): void
    {
        $response = $this->success(null, null, 204);

        $json = $response->getData(true);
        $this->assertTrue($json['success']);
        $this->assertNull($json['data']);
        $this->assertNull($json['error']);
    }

    /**
     * Test success response with array data.
     */
    public function test_success_response_with_array_data(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Project 1'],
            ['id' => 2, 'name' => 'Project 2'],
        ];

        $response = $this->success($data);

        $json = $response->getData(true);
        $this->assertTrue($json['success']);
        $this->assertEquals($data, $json['data']);
    }

    /**
     * Test error response format.
     */
    public function test_error_response_format(): void
    {
        $response = $this->error(ApiErrorCode::RESOURCE_NOT_FOUND);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->status());

        $json = $response->getData(true);
        $this->assertFalse($json['success']);
        $this->assertNull($json['data']);
        $this->assertIsArray($json['error']);
        $this->assertEquals('RESOURCE_NOT_FOUND', $json['error']['code']);
        $this->assertNotEmpty($json['error']['message']);
    }

    /**
     * Test error response uses error code HTTP status.
     */
    public function test_error_response_uses_error_code_http_status(): void
    {
        $response = $this->error(ApiErrorCode::AUTH_INVALID_CREDENTIALS);

        $this->assertEquals(401, $response->status());
    }

    /**
     * Test error response with custom message.
     */
    public function test_error_response_with_custom_message(): void
    {
        $customMessage = 'Custom error message';
        $response = $this->error(ApiErrorCode::VALIDATION_ERROR, $customMessage);

        $json = $response->getData(true);
        $this->assertEquals($customMessage, $json['error']['message']);
    }

    /**
     * Test error response with validation details.
     */
    public function test_error_response_with_validation_details(): void
    {
        $details = [
            'name' => ['Name is required'],
            'email' => ['Email must be valid'],
        ];

        $response = $this->error(
            ApiErrorCode::VALIDATION_ERROR,
            null,
            $details
        );

        $json = $response->getData(true);
        $this->assertEquals($details, $json['error']['details']);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Test error response with custom status code overrides error code status.
     */
    public function test_error_response_custom_status_overrides_error_code(): void
    {
        $response = $this->error(
            ApiErrorCode::RESOURCE_NOT_FOUND,
            null,
            null,
            400
        );

        $this->assertEquals(400, $response->status());
    }

    /**
     * Test error response with Arabic message.
     */
    public function test_error_response_with_arabic_message_locale(): void
    {
        app()->setLocale('ar');

        $response = $this->error(ApiErrorCode::AUTH_UNAUTHORIZED);

        $json = $response->getData(true);
        $this->assertNotEmpty($json['error']['message']);
        // Arabic message should contain Arabic characters
        $message = $json['error']['message'];
        $this->assertTrue(strlen($message) > 0);
    }

    /**
     * Test validation error response structure.
     */
    public function test_validation_error_response_structure(): void
    {
        $details = [
            'email' => ['The email field is required'],
            'password' => ['The password must be at least 8 characters'],
        ];

        $response = $this->error(
            ApiErrorCode::VALIDATION_ERROR,
            null,
            $details
        );

        $json = $response->getData(true);
        $this->assertFalse($json['success']);
        $this->assertNull($json['data']);
        $this->assertArrayHasKey('error', $json);
        $this->assertEquals('VALIDATION_ERROR', $json['error']['code']);
        $this->assertEquals($details, $json['error']['details']);
        $this->assertEquals(422, $response->status());
    }

    /**
     * Test rate limit error response.
     */
    public function test_rate_limit_error_response(): void
    {
        $response = $this->error(ApiErrorCode::RATE_LIMIT_EXCEEDED);

        $this->assertEquals(429, $response->status());
        $json = $response->getData(true);
        $this->assertEquals('RATE_LIMIT_EXCEEDED', $json['error']['code']);
    }

    /**
     * Test server error response.
     */
    public function test_server_error_response(): void
    {
        $response = $this->error(ApiErrorCode::SERVER_ERROR);

        $this->assertEquals(500, $response->status());
        $json = $response->getData(true);
        $this->assertEquals('SERVER_ERROR', $json['error']['code']);
    }

    /**
     * Test RBAC error response.
     */
    public function test_rbac_error_response(): void
    {
        $response = $this->error(ApiErrorCode::RBAC_ROLE_DENIED);

        $this->assertEquals(403, $response->status());
        $json = $response->getData(true);
        $this->assertEquals('RBAC_ROLE_DENIED', $json['error']['code']);
    }

    /**
     * Test all error codes produce valid error responses.
     */
    public function test_all_error_codes_produce_valid_responses(): void
    {
        foreach (ApiErrorCode::cases() as $errorCode) {
            $response = $this->error($errorCode);

            $this->assertInstanceOf(JsonResponse::class, $response);
            $this->assertEquals($errorCode->httpStatus(), $response->status());

            $json = $response->getData(true);
            $this->assertFalse($json['success']);
            $this->assertNull($json['data']);
            $this->assertIsArray($json['error']);
            $this->assertEquals($errorCode->value, $json['error']['code']);
        }
    }

    /**
     * Test error response without details field by default.
     */
    public function test_error_response_without_details_by_default(): void
    {
        $response = $this->error(ApiErrorCode::AUTH_UNAUTHORIZED);

        $json = $response->getData(true);
        $this->assertArrayNotHasKey('details', $json['error']);
    }

    /**
     * Test special characters and UTF-8 in error messages.
     */
    public function test_special_characters_in_error_messages(): void
    {
        $customMessage = 'مرحبا بك في النظام - عذراً حدث خطأ! 🔐';
        $response = $this->error(ApiErrorCode::SERVER_ERROR, $customMessage);

        $json = $response->getData(true);
        $this->assertEquals($customMessage, $json['error']['message']);
        $this->assertStringContainsString('مرحبا', $json['error']['message']);
    }
}
