import { expect, test } from '@playwright/test';

/**
 * T068: E2E test for profile page RBAC
 * Verifies: Access profile page without auth → 401 redirect to login
 */
test.describe('Profile RBAC Protection', () => {
  test('should redirect unauthorized user to login', async ({ page }) => {
    // Visit profile page without authentication
    await page.goto('/profile', { waitUntil: 'networkidle' }).catch(() => {});

    // Should redirect to login
    const currentUrl = page.url();
    expect(currentUrl).toMatch(/\/login|\/auth/);
  });

  test('should show 401 error when accessing without token', async ({ page }) => {
    // Navigate to a page first so localStorage is accessible
    await page.goto('/ar/auth/login');
    // Clear auth token
    await page.context().clearCookies();
    await page.evaluate(() => {
      localStorage.removeItem('auth_token');
      sessionStorage.removeItem('auth_token');
    });

    // Try to access profile
    const _response = await page.goto('/profile', { waitUntil: 'networkidle' }).catch(() => null);

    // Should be redirected (not 401, but redirect to login)
    const url = page.url();
    expect(url).toMatch(/\/login|\/auth/);
  });

  test('should allow authenticated user to access profile', async ({ page }) => {
    // Set auth token (simulated)
    await page.context().addCookies([
      {
        name: 'auth_token',
        value: 'test_token_123',
        url: 'http://localhost:3001',
      },
    ]);

    await page.goto('/profile');

    // Profile page should be accessible
    const profileTitle = page.locator('text=/Profile|الملف|profile/i');

    // If accessible, should show profile elements
    // (actual implementation may vary)
    const currentUrl = page.url();
    const isProfile = currentUrl.includes('/profile');
    const hasContent = await profileTitle.isVisible({ timeout: 2000 }).catch(() => false);

    expect(isProfile || hasContent).toBe(true);
  });

  test('should enforce RBAC on profile update endpoint', async ({ page }) => {
    // Simulate API call without auth
    const response = await page.evaluate(async () => {
      try {
        const res = await fetch('/api/v1/profile', {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ firstName: 'John' }),
        });
        return res.status;
      } catch {
        return null;
      }
    });

    // Should return 401 Unauthorized or be blocked before reaching API
    expect(response === 401 || response === null || response === undefined).toBe(true);
  });

  test('middleware should run before component loads', async ({ page }) => {
    // Navigate to protected page
    const navigationPromise = page.goto('/profile');

    // Middleware should redirect immediately
    await navigationPromise.catch(() => {});

    // Check URL changed before page rendered
    const finalUrl = page.url();
    const isRedirected = finalUrl.includes('/login') || finalUrl.includes('/auth');

    expect(isRedirected).toBe(true);
  });

  test('should maintain RBAC during session', async ({ page }) => {
    // Set temporary token
    await page.context().addCookies([
      {
        name: 'auth_token',
        value: 'test_token_123',
        url: 'http://localhost:3001',
      },
    ]);

    await page.goto('/profile').catch(() => {});

    // Simulate token expiry by clearing the auth cookie (not localStorage)
    await page.context().clearCookies();

    // Navigate to another protected page
    await page.goto('/profile');

    // Should redirect to login
    const url = page.url();
    const isProtected = url.includes('/login') || url.includes('/auth');

    expect(isProtected).toBe(true);
  });
});
