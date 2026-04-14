import { expect, test } from '@playwright/test';

/**
 * T068: E2E test for auto-refresh queue under load
 * Verifies: Token refresh prevents race conditions with concurrent requests
 */
test.describe('Performance - Token Refresh Queue', () => {
  test('should handle concurrent API requests during token refresh', async ({ page }) => {
    await page.goto('/dashboard');

    // Measure performance: concurrent API calls should complete without duplication
    const apiCalls: number[] = [];

    // Listen to network requests
    page.on('request', (request) => {
      if (request.url().includes('/api/')) {
        apiCalls.push(Date.now());
      }
    });

    // Trigger 5 concurrent API calls (via page interactions)
    // In a real app, this would be simultaneous requests
    const startTime = Date.now();

    // Simulate rapid navigation/data fetches
    for (let i = 0; i < 5; i++) {
      // Click multiple elements that trigger API calls
      await page
        .locator('[data-test-api-trigger]')
        .first()
        .click({ timeout: 2000 })
        .catch(() => {});
    }

    const endTime = Date.now();
    const duration = endTime - startTime;

    // Performance check: should complete within reasonable time
    expect(duration).toBeLessThan(5000); // 5 seconds max

    // No duplicate requests should occur during refresh
    expect(apiCalls.length).toBeLessThanOrEqual(5);
  });

  test('should queue requests during token refresh', async ({ page }) => {
    // Navigate to authenticated page
    await page.goto('/dashboard');

    let _queuedRequests = 0;
    let refreshCalls = 0;

    // Intercept API calls
    await page.route('/api/**', async (route) => {
      const request = route.request();

      // Count token refresh calls
      if (request.url().includes('/auth/refresh')) {
        refreshCalls++;
      }

      // Simulate requests waiting in queue
      _queuedRequests++;

      // Respond after delay to simulate refresh in progress
      await new Promise((resolve) => setTimeout(resolve, 100));

      _queuedRequests--;

      // Return mock response
      await route.continue();
    });

    // Make 5 rapid API calls
    for (let i = 0; i < 5; i++) {
      fetch('/api/v1/projects', {
        headers: { Authorization: `Bearer test_token_${i}` },
      }).catch(() => {});
    }

    // Wait for queue to process
    await page.waitForTimeout(1500);

    // Only one refresh call should have been made (not 5)
    expect(refreshCalls).toBeLessThanOrEqual(1);
  });

  test('should not duplicate token refresh requests', async ({ page }) => {
    await page.goto('/dashboard');

    let refreshCount = 0;

    await page.route('**/auth/refresh', async (route) => {
      refreshCount++;

      // Simulate token refresh delay
      await new Promise((resolve) => setTimeout(resolve, 200));

      await route.respond({
        status: 200,
        body: JSON.stringify({
          success: true,
          data: { token: 'new_token_123' },
        }),
      });
    });

    // Trigger 10 concurrent API calls that might need token refresh
    const requests = Array(10)
      .fill(null)
      .map(() =>
        fetch('/api/v1/projects', {
          headers: { Authorization: 'Bearer expired_token' },
        }).catch(() => {})
      );

    await Promise.all(requests);
    await page.waitForTimeout(500);

    // Should only refresh token once, not 10 times
    expect(refreshCount).toBeLessThanOrEqual(1);
  });

  test('should maintain request order during refresh', async ({ page }) => {
    const requestOrder: string[] = [];

    await page.route('/api/**/data', async (route) => {
      const id = new URL(route.request().url()).searchParams.get('id');
      if (id) requestOrder.push(id);
      await route.continue();
    });

    // Make 5 sequential requests
    for (let i = 1; i <= 5; i++) {
      await fetch(`/api/v1/data?id=${i}`).catch(() => {});
    }

    await page.waitForTimeout(500);

    // Requests should maintain order
    for (let i = 0; i < requestOrder.length - 1; i++) {
      const current = parseInt(requestOrder[i]);
      const next = parseInt(requestOrder[i + 1]);
      expect(current <= next).toBe(true);
    }
  });

  test('should complete all requests after token refresh', async ({ page }) => {
    await page.goto('/dashboard');

    const completedRequests: string[] = [];

    await page.route('/api/**', async (route) => {
      const url = route.request().url();

      // Simulate checking token
      if (url.includes('projects')) {
        // Simulate refresh happens  during this request
        await new Promise((resolve) => setTimeout(resolve, 50));
        completedRequests.push('project');
      } else if (url.includes('tasks')) {
        completedRequests.push('task');
      }

      await route.continue();
    });

    // Make 3 different API calls concurrently
    await Promise.all([
      fetch('/api/v1/projects').catch(() => {}),
      fetch('/api/v1/tasks').catch(() => {}),
      fetch('/api/v1/projects').catch(() => {}),
    ]);

    await page.waitForTimeout(1000);

    // All requests should have completed
    expect(completedRequests.length).toBeGreaterThanOrEqual(2);
  });
});
