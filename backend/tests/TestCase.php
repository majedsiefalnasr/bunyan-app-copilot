<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear old logs before each test run to avoid false positives
        $this->clearLogs();
    }

    /**
     * Clear storage/logs/*.log files to ensure regression tests inspect only
     * logs generated during the current test run.
     */
    protected function clearLogs(): void
    {
        $logDir = base_path('storage/logs');

        if (! is_dir($logDir)) {
            return;
        }

        $files = glob($logDir.'/*.log');
        foreach ($files as $file) {
            if (is_file($file) && is_writable($file)) {
                // Truncate file contents
                @file_put_contents($file, '');
            }
        }
    }

    // Backwards-compatible assertion aliases for older PHPUnit-style names used in tests
    public function assertNotEqual($expected, $actual, string $message = ''): void
    {
        $this->assertNotEquals($expected, $actual, $message);
    }

    public function assertNotIn($needle, $haystack, string $message = ''): void
    {
        $this->assertNotContains($needle, $haystack, $message);
    }

    /**
     * Backwards-compatible alias for `assertContains` used by some tests.
     */
    public function assertIn($needle, $haystack, string $message = ''): void
    {
        $this->assertContains($needle, $haystack, $message);
    }
}
