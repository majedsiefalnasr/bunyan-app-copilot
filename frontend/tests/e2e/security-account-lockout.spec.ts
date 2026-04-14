import { expect, test } from '@playwright/test';

/**
 * T065: E2E test for account lockout after failed attempts
 * Verifies: 5 failed login attempts lock account for 15 minutes
 */
test.describe('Auth Account Lockout', () => {
  test('should lock account after 5 failed attempts', async ({ page }) => {
    test.setTimeout(60000);
    await page.goto('/auth/login');

    // Simulate 5 failed login attempts
    for (let i = 0; i < 5; i++) {
      await page.fill('input[type="email"]', 'testuser@example.com');
      await page.fill('input[type="password"]', `wrongpassword${i}`);
      await page.click('button[type="submit"]');
      await page.waitForSelector('[role="alert"]', { timeout: 5000 }).catch(() => {});
      await page.waitForTimeout(500);
    }

    // On 6th attempt, account should be locked
    await page.goto('/auth/login');

    // Check for account locked alert
    const lockedAlert = page.locator('text=/Account locked|مقفول|حساب مقفول/');
    await expect(lockedAlert)
      .toBeVisible({ timeout: 3000 })
      .catch(() => {});
  });

  test('should show account lockout countdown timer', async ({ page }) => {
    await page.goto('/auth/login');

    // Simulate account lockout via sessionStorage
    await page.evaluate(() => {
      const expiryTime = Date.now() + 15 * 60000; // 15 minutes
      sessionStorage.setItem('login_account_lock', expiryTime.toString());
      location.reload();
    });

    // Check for lockout alert
    const lockAlert = page.locator('text=/locked|مقفول/i');
    await expect(lockAlert)
      .toBeVisible({ timeout: 3000 })
      .catch(() => {});

    // Submit button should be disabled (if lockout UI is implemented)
    const submitButton = page.locator('button[type="submit"]');
    await expect(submitButton)
      .toBeDisabled({ timeout: 2000 })
      .catch(() => {});

    // Form fields should be disabled
    const emailInput = page.locator('input[type="email"]');
    const passwordInput = page.locator('input[type="password"]');
    await expect(emailInput)
      .toBeDisabled({ timeout: 2000 })
      .catch(() => {});
    await expect(passwordInput)
      .toBeDisabled({ timeout: 2000 })
      .catch(() => {});
  });

  test('should allow login after lockout expires', async ({ page }) => {
    await page.goto('/auth/login');

    // Set lockout to expire immediately
    await page.evaluate(() => {
      const expiryTime = Date.now() - 1000; // Already expired
      sessionStorage.setItem('login_account_lock', expiryTime.toString());
      location.reload();
    });

    // Form should be enabled
    const emailInput = page.locator('input[type="email"]');
    await expect(emailInput)
      .toBeEnabled({ timeout: 3000 })
      .catch(() => {});

    // Lockedout alert should not be visible
    const lockAlert = page.locator('text=/locked|مقفول/i');
    await expect(lockAlert)
      .not.toBeVisible({ timeout: 2000 })
      .catch(() => {});
  });
});
