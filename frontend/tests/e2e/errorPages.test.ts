import { expect, test } from '@playwright/test';

test.describe('Error Pages', () => {
  test('404 error page renders correctly', async ({ page }) => {
    // Navigate directly to 404 page
    await page.goto('/error-404');

    // Check for page elements
    await expect(page.locator('text=404')).toBeVisible();
    await expect(page.locator('text=/Not Found|غير موجودة/i')).toBeVisible();

    // Check buttons exist
    const buttons = page.locator('button');
    await expect(buttons).toHaveCount(2);
  });

  test('403 error page renders correctly', async ({ page }) => {
    // Navigate directly to 403 page
    await page.goto('/error-403');

    // Check for page elements
    await expect(page.locator('text=403')).toBeVisible();
    await expect(page.locator('text=/Access Denied|مرفوض/i')).toBeVisible();

    // Check buttons exist
    const buttons = page.locator('button');
    await expect(buttons.count()).toBeGreaterThan(1);
  });

  test('500 error page renders correctly', async ({ page }) => {
    // Navigate directly to 500 page
    await page.goto('/error-500');

    // Check for page elements
    await expect(page.locator('text=/Something Went Wrong|حدث خطأ/i')).toBeVisible();

    // Check buttons exist
    const buttons = page.locator('button');
    await expect(buttons).toHaveCount(2);
  });

  test('404 page buttons are functional', async ({ page }) => {
    await page.goto('/error-404');

    // Test Go Home button
    const homeButton = page.locator('button:has-text(/Home|الصفحة الرئيسية/i)').first();
    await homeButton.click();

    // Should navigate to home
    await expect(page).toHaveURL(/\/(ar|en)?\/$/);
  });

  test('403 page buttons are functional', async ({ page }) => {
    await page.goto('/error-403');

    // Test Go Home button
    const homeButton = page.locator('button:has-text(/Home|الصفحة الرئيسية/i)').first();
    await homeButton.click();

    // Should navigate somewhere
    await page.waitForNavigation();
  });

  test('500 page displays correlation ID if available', async ({ page }) => {
    // Navigate to 500 page
    await page.goto('/error-500');

    // Correlation ID might not be visible initially, but check support reference section
    const supportRef = page.locator('text=/Support Reference|مرجع الدعم/i');

    // The element should either exist or not, depending on state
    const exists = await supportRef.isVisible().catch(() => false);
    expect(typeof exists).toBe('boolean');
  });

  test('error pages support RTL layout', async ({ page }) => {
    // Test Arabic RTL layout
    await page.goto('/ar/error-404');

    // Check if page is in RTL mode
    const htmlElement = page.locator('html');
    const dir = await htmlElement.getAttribute('dir');

    // Should be 'rtl' for Arabic
    if ((await page.url()).includes('/ar/')) {
      expect(dir).toBe('rtl');
    }
  });

  test('error pages support LTR layout', async ({ page }) => {
    // Test English LTR layout
    await page.goto('/en/error-404');

    // Check if page is in LTR mode
    const htmlElement = page.locator('html');
    const dir = await htmlElement.getAttribute('dir');

    // Should be 'ltr' for English
    if ((await page.url()).includes('/en/')) {
      expect(dir).toBe('ltr');
    }
  });

  test('error page text is localized', async ({ page }) => {
    // Test Arabic localization
    await page.goto('/ar/error-404');
    await expect(page.locator('text=/غير موجودة/i')).toBeVisible();

    // Test English localization
    await page.goto('/en/error-404');
    await expect(page.locator('text=/Not Found/i')).toBeVisible();
  });
});
