<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;

/**
 * Application TrustProxies middleware
 *
 * Ensures the application trusts proxy headers (X-Forwarded-*) during tests
 * so rate limiting and client IP detection behave as expected.
 */
class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     * Use wildcard in test/dev to trust forwarded headers from the test client.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     * Bitmask: 31 = all X-Forwarded headers (FOR | HOST | PROTO | AWS_ELB | AWS_ELB_PROTO)
     *
     * @var int
     */
    protected $headers = 31;
}
