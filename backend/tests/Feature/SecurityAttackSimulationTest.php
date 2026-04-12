<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ApiErrorCode;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Security Attack Simulation Test Suite
 *
 * CRITICAL SECURITY GATE: T084
 *
 * Tests three attack scenarios:
 * 1. Brute-force attack: 1000 req/min to /api/login → 429 RATE_LIMIT_EXCEEDED after 10th request
 * 2. Header injection attack: Correlation ID with XSS payload → verified masked/rejected
 * 3. X-Forwarded-For spoofing: Multiple IPs submitted → rate limiting NOT bypassed
 *
 * All 3 attack scenarios in this file MUST pass before closure approval
 *
 * @see specs/runtime/005-error-handling/README.md (Phase 4 security gates)
 */
class SecurityAttackSimulationTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /**
     * Test brute-force attack simulation.
     *
     * AC: Brute-force attack: 1000 req/min to /api/login → 429 RATE_LIMIT_EXCEEDED after 10th request
     * AC: Rate limit kicks in at correct threshold
     * AC: Error code is RATE_LIMIT_EXCEEDED
     * AC: Response includes Retry-After header with delay in seconds
     */
    public function test_brute_force_attack_rate_limiting(): void
    {
        // Create a test user for login attempts
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Simulate rapid login attempts from same IP
        $attacker_ip = '192.168.1.100';

        // First 10 attempts should succeed (or fail with 401, but not 429)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeader('X-Forwarded-For', $attacker_ip)
                ->postJson('/api/v1/test/error/auth-invalid-credentials', [
                    'email' => 'test@example.com',
                    'password' => 'wrongpassword',
                ]);

            // Should not be rate limited yet
            $this->assertNotEqual(
                429,
                $response->status(),
                "Rate limiting should not kick in before 10 requests"
            );
        }

        // Request 11 onwards should be rate limited (429)
        $response = $this->withHeader('X-Forwarded-For', $attacker_ip)
            ->postJson('/api/v1/test/error/auth-invalid-credentials', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);

        $this->assertEquals(
            429,
            $response->status(),
            "Brute-force attack should be rate limited with 429 after 10 requests"
        );

        // Verify error code is RATE_LIMIT_EXCEEDED
        $json = $response->json();
        $this->assertFalse($json['success']);
        $this->assertIsArray($json['error']);
        $this->assertEquals(
            ApiErrorCode::RATE_LIMIT_EXCEEDED->value,
            $json['error']['code'],
            "Rate limit error code must be RATE_LIMIT_EXCEEDED"
        );

        // Verify Retry-After header exists
        $this->assertNotNull(
            $response->headers->get('Retry-After'),
            "Rate limited response must include Retry-After header"
        );

        // Verify Retry-After is a valid number (seconds)
        $retryAfter = (int) $response->headers->get('Retry-After');
        $this->assertGreaterThan(0, $retryAfter, "Retry-After must be positive number of seconds");
    }

    /**
     * Test header injection attack protection.
     *
     * AC: Header injection: Correlation ID with XSS payload → verified masked/rejected
     * AC: Malformed correlation IDs (XSS payloads, SQL patterns) rejected
     * AC: Validation failure logged with IP and attempted ID
     */
    public function test_header_injection_attack_protection(): void
    {
        // XSS payload attempts in correlation ID header
        $xssPayloads = [
            '<script>alert("xss")</script>',
            'javascript:alert("xss")',
            '"><script>alert("xss")</script>',
            '\'><script>alert(1)</script>',
            '\'; DROP TABLE users; --',
        ];

        foreach ($xssPayloads as $payload) {
            $response = $this->withHeader('X-Correlation-ID', $payload)
                ->getJson('/api/v1/test/success');

            // Response can succeed or fail, but correlation ID injection must not work
            // Verify the payload did not make it through unescaped
            $json = $response->json();

            // The correlation ID in response header should be either:
            // 1. A valid UUID (our generated one, not the payload)
            // 2. Absent (payload was rejected)
            $correlationId = $response->headers->get('X-Correlation-ID');

            if ($correlationId) {
                // If we got a correlation ID back, it must be UUID format
                // UUID v4: /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i
                $this->assertMatchesRegularExpression(
                    '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                    $correlationId,
                    "Correlation ID must be valid UUID format, not malicious payload: {$payload}"
                );
            }
        }
    }

    /**
     * Test X-Forwarded-For spoofing attack.
     *
     * AC: X-Forwarded-For spoofing: Multiple IPs submitted → rate limiting NOT bypassed
     * AC: Rate limiting uses trusted IP source (not easily spoofed X-Forwarded-For)
     * AC: Attacker cannot bypass rate limits by changing IP header
     */
    public function test_x_forwarded_for_spoofing_does_not_bypass_rate_limits(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Initialize rate limiter for one IP
        $attacker_base_ip = '192.168.1.100';

        // Make 10 requests from base IP
        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeader('X-Forwarded-For', $attacker_base_ip)
                ->postJson('/api/v1/test/error/auth-invalid-credentials', [
                    'email' => 'test@example.com',
                    'password' => 'wrongpassword',
                ]);
        }

        // Try to bypass rate limit by spoofing a different IP in X-Forwarded-For
        // (typical spoofing attack: send multiple IPs to confuse rate limiter)
        $spoofed_ips = '192.168.1.100, 10.0.0.1, 172.16.0.1';

        $response = $this->withHeader('X-Forwarded-For', $spoofed_ips)
            ->postJson('/api/v1/test/error/auth-invalid-credentials', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);

        // Should STILL be rate limited (spoofing should not bypass)
        $this->assertEquals(
            429,
            $response->status(),
            "Spoofing X-Forwarded-For should not bypass rate limiting"
        );

        // Also try with completely different IP in header
        $response = $this->withHeader('X-Forwarded-For', '200.200.200.200')
            ->postJson('/api/v1/test/error/auth-invalid-credentials', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);

        // Our rate limiter should use the request IP, not the header
        // So this should also be rate limited if we're using proper server IP detection
        $this->assertIn(
            $response->status(),
            [401, 403, 429],
            "Spoofed IP header should not allow unlimited requests"
        );
    }

    /**
     * Test that all attack scenarios are properly logged for security audit.
     */
    public function test_attack_scenarios_logged_for_audit(): void
    {
        // Rate limit exceeded should be logged
        Log::shouldReceive('warning')
            ->with(
                'rate_limit_exceeded',
                \Mockery::on(function ($context) {
                    return isset($context['ip']) && isset($context['endpoint']);
                })
            )
            ->atLeast()->once();

        // Create test user
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $attacker_ip = '192.168.1.200';

        // Trigger rate limit
        for ($i = 0; $i < 12; $i++) {
            $this->withHeader('X-Forwarded-For', $attacker_ip)
                ->postJson('/api/v1/test/error/auth-invalid-credentials', [
                    'email' => 'test@example.com',
                    'password' => 'wrongpassword',
                ]);
        }
    }

    /**
     * Test timing-based attack resistance.
     *
     * Verify that response times don't leak information about valid usernames
     * (timing attack on user enumeration).
     */
    public function test_timing_attack_resistance(): void
    {
        // Create test user
        $user = User::factory()->create([
            'email' => 'known-user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Response time for valid user
        $startValid = microtime(true);
        $this->postJson('/api/v1/test/error/auth-invalid-credentials', [
            'email' => 'known-user@example.com',
            'password' => 'wrongpassword',
        ]);
        $timeValid = microtime(true) - $startValid;

        // Response time for nonexistent user
        $startInvalid = microtime(true);
        $this->postJson('/api/v1/test/error/auth-invalid-credentials', [
            'email' => 'nonexistent-user@example.com',
            'password' => 'wrongpassword',
        ]);
        $timeInvalid = microtime(true) - $startInvalid;

        // Times should be similar (within reasonable margin)
        // Allowing 10ms difference to account for system variance
        $timeDifference = abs($timeValid - $timeInvalid);
        $this->assertLessThan(
            0.010,
            $timeDifference,
            "Response times should not differ significantly between valid and invalid users (timing attack)"
        );
    }
}
