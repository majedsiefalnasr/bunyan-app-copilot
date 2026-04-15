<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\ApiErrorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Feature tests for rate limiting (T031).
 *
 * Covers AC-08 through AC-12:
 * - AC-08: authenticated user's 61st request returns HTTP 429 RATE_LIMIT_EXCEEDED
 * - AC-09: unauthenticated IP's 11th request returns HTTP 429
 * - AC-10: admin user is not blocked at 60 requests on admin routes
 * - AC-11: every 429 response includes Retry-After, X-RateLimit-Limit, X-RateLimit-Remaining
 * - AC-12: GET /api/health is never rate-limited
 *
 * Implementation note:
 * ThrottleRequests::$shouldHashKeys defaults to true, meaning the actual cache key
 * used by the middleware is md5($limiterName . $byKey). We disable hashing for this
 * test class (restoring it in tearDown) so we can pre-fill the rate limiter cache
 * with predictable keys in the format "{limiterName}:{byKey}".
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable key hashing so we can pre-fill with a predictable key format:
        // "{limiterName}:{byKey}" e.g. "api-authenticated:user:1"
        ThrottleRequests::shouldHashKeys(false);

        // Register test-only routes with named rate limiters
        Route::middleware(['throttle:api-authenticated'])
            ->get('/test-api-authenticated', fn () => response()->json(['ok' => true]));

        Route::middleware(['throttle:api-public'])
            ->get('/test-api-public', fn () => response()->json(['ok' => true]));

        Route::middleware(['throttle:api-admin'])
            ->get('/test-api-admin', fn () => response()->json(['ok' => true]));
    }

    protected function tearDown(): void
    {
        // Flush cache to prevent cross-test rate limiter contamination
        Cache::flush();
        // Restore key hashing for other test classes
        ThrottleRequests::shouldHashKeys(true);
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // AC-08: api-authenticated — 61st request returns 429
    // -------------------------------------------------------------------------

    public function test_authenticated_user_61st_request_returns_429(): void
    {
        $user = User::factory()->create();
        // With hashing disabled, middleware uses "api-authenticated:user:{id}"
        $cacheKey = 'api-authenticated:user:'.$user->id;

        // Pre-exhaust 60 of 60 allowed attempts
        for ($i = 0; $i < 60; $i++) {
            RateLimiter::hit($cacheKey, 60);
        }

        // 61st request should be throttled
        $response = $this->actingAs($user)->getJson('/test-api-authenticated');

        $response->assertStatus(429);
        $response->assertJson([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => ApiErrorCode::RATE_LIMIT_EXCEEDED->value,
            ],
        ]);
    }

    public function test_authenticated_user_within_limit_returns_200(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/test-api-authenticated');

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // AC-09: api-public — 11th unauthenticated request returns 429
    // -------------------------------------------------------------------------

    public function test_unauthenticated_ip_11th_request_returns_429(): void
    {
        // With hashing disabled, middleware uses "api-public:127.0.0.1"
        $cacheKey = 'api-public:127.0.0.1';

        // Pre-exhaust 10 of 10 allowed attempts
        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit($cacheKey, 60);
        }

        // 11th request should be throttled
        $response = $this->getJson('/test-api-public');

        $response->assertStatus(429);
        $response->assertJson([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => ApiErrorCode::RATE_LIMIT_EXCEEDED->value,
            ],
        ]);
    }

    public function test_unauthenticated_request_within_public_limit_returns_200(): void
    {
        $response = $this->getJson('/test-api-public');

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // AC-10: admin user not blocked at 60 requests (api-admin is 300/min)
    // -------------------------------------------------------------------------

    public function test_admin_user_is_not_blocked_at_60_requests_on_admin_throttled_route(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();
        // With hashing disabled, middleware uses "api-admin:admin:{id}"
        $cacheKey = 'api-admin:admin:'.$admin->id;

        // Simulate 60 requests (well within the 300/min api-admin limit)
        for ($i = 0; $i < 60; $i++) {
            RateLimiter::hit($cacheKey, 60);
        }

        // 61st request should NOT be throttled (300/min allows 300 before blocking)
        $response = $this->actingAs($admin)->getJson('/test-api-admin');

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);
    }

    // -------------------------------------------------------------------------
    // AC-11: 429 responses include Retry-After, X-RateLimit-Limit, X-RateLimit-Remaining
    // -------------------------------------------------------------------------

    public function test_429_response_includes_retry_after_header(): void
    {
        $user = User::factory()->create();
        $cacheKey = 'api-authenticated:user:'.$user->id;

        for ($i = 0; $i < 60; $i++) {
            RateLimiter::hit($cacheKey, 60);
        }

        $response = $this->actingAs($user)->getJson('/test-api-authenticated');

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');

        $retryAfter = (int) $response->headers->get('Retry-After');
        $this->assertGreaterThan(0, $retryAfter, 'Retry-After must be a positive integer');
    }

    public function test_429_response_includes_x_rate_limit_headers(): void
    {
        $user = User::factory()->create();
        $cacheKey = 'api-authenticated:user:'.$user->id;

        for ($i = 0; $i < 60; $i++) {
            RateLimiter::hit($cacheKey, 60);
        }

        $response = $this->actingAs($user)->getJson('/test-api-authenticated');

        $response->assertStatus(429);
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');

        $limit = (int) $response->headers->get('X-RateLimit-Limit');
        $remaining = (int) $response->headers->get('X-RateLimit-Remaining');

        $this->assertSame(60, $limit, 'X-RateLimit-Limit must equal the api-authenticated max (60)');
        $this->assertSame(0, $remaining, 'X-RateLimit-Remaining must be 0 when limit is exhausted');
    }

    // -------------------------------------------------------------------------
    // AC-12: GET /api/health is never rate-limited
    // -------------------------------------------------------------------------

    public function test_health_endpoint_is_never_rate_limited(): void
    {
        // Make 20 consecutive requests — none should return 429
        for ($i = 0; $i < 20; $i++) {
            $response = $this->getJson('/api/health');
            $this->assertNotEquals(
                429,
                $response->status(),
                "Health endpoint returned 429 on request #{$i}"
            );
        }
    }

    public function test_health_endpoint_returns_non_401_without_auth(): void
    {
        $response = $this->getJson('/api/health');

        $this->assertNotEquals(401, $response->status());
        $this->assertNotEquals(429, $response->status());
    }
}
