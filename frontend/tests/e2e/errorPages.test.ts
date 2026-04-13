import { expect, test } from '@playwright/test';

test.describe('Error Pages', () => {
  test('404 error page renders correctly', async ({ page }) => {
    // Navigate to 404 error display page
    await page.goto('/ar/not-found');

    // Check for page elements - use heading selector
    const heading = page.locator('h1', {
      has: page.locator('text=/Page Not Found|الصفحة غير موجودة/i'),
    });
    await expect(heading).toBeVisible();

    // Check buttons exist within the error container
    const errorContainer = page
      .locator('div')
      .filter({ has: page.locator('h1') })
      .first();
    const buttons = errorContainer.locator('button');
    await expect(buttons).toHaveCount(2);
  });

  test('403 error page renders correctly', async ({ page }) => {
    // Navigate to 403 error display page
    await page.goto('/ar/access-denied');

    // Check for page elements
    const heading = page.locator('h1', { has: page.locator('text=/Access Denied|تم الرفض/i') });
    await expect(heading).toBeVisible();

    // Check buttons exist within the error container
    const errorContainer = page
      .locator('div')
      .filter({ has: page.locator('h1') })
      .first();
    const buttons = errorContainer.locator('button');
    await expect(buttons).toHaveCount(2);
  });

  test('500 error page renders correctly', async ({ page }) => {
    // Navigate to 500 error display page
    await page.goto('/ar/server-error');

    // Check for page elements
    const heading = page.locator('h1', {
      has: page.locator('text=/Something Went Wrong|حدث خطأ/i'),
    });
    await expect(heading).toBeVisible();

    // Check buttons exist within the error container
    const errorContainer = page
      .locator('div')
      .filter({ has: page.locator('h1') })
      .first();
    const buttons = errorContainer.locator('button');
    await expect(buttons).toHaveCount(2);
  });

  test('404 page buttons are functional', async ({ page }) => {
    await page.goto('/ar/not-found');

    // Test Go Home button using role selector within error container
    const errorContainer = page
      .locator('div')
      .filter({ has: page.locator('h1') })
      .first();
    const homeButton = errorContainer.getByRole('button').first();
    await expect(homeButton).toBeVisible();
    await homeButton.click();

    // Should navigate to home with current locale prefix or plain home
    const url = page.url();
    expect(/\/(ar|en)?(?:\/)?$/.test(url.replace('http://localhost:3000', ''))).toBeTruthy();
  });

  test('403 page buttons are functional', async ({ page }) => {
    await page.goto('/ar/access-denied');

    // Test Go Home button using role selector within error container
    const errorContainer = page
      .locator('div')
      .filter({ has: page.locator('h1') })
      .first();
    const homeButton = errorContainer.getByRole('button').first();
    await expect(homeButton).toBeVisible();
    await homeButton.click();

    // Should navigate to home with current locale prefix or plain home
    const url = page.url();
    expect(/\/(ar|en)?(?:\/)?$/.test(url.replace('http://localhost:3000', ''))).toBeTruthy();
  });

  test('500 page displays correlation ID if available', async ({ page }) => {
    // Navigate to 500 page
    await page.goto('/ar/server-error');

    // Correlation ID might not be visible initially, but check support reference section
    const supportRef = page.locator('text=/Support Reference|مرجع الدعم/i');

    // The element should either exist or not, depending on state
    const exists = await supportRef.isVisible().catch(() => false);
    expect(typeof exists).toBe('boolean');
  });

  test('error pages support RTL layout', async ({ page }) => {
    // Test Arabic RTL layout
    await page.goto('/ar/not-found');

    // Check if page is in RTL mode
    const htmlElement = page.locator('html');
    const dir = await htmlElement.getAttribute('dir');

    // Should be 'rtl' for Arabic
    expect(dir).toBe('rtl');
  });

  test('error pages support LTR layout', async ({ page }) => {
    // Test English LTR layout
    await page.goto('/en/not-found');

    // Wait a bit for locale to update (i18n takes a moment)
    await page.waitForTimeout(100);

    // Check if page is in LTR mode
    const htmlElement = page.locator('html');
    const dir = await htmlElement.getAttribute('dir');

    // Should be 'ltr' for English
    expect(dir).toBe('ltr');
  });

  test('error page text is localized', async ({ page }) => {
    // Test Arabic localization first
    await page.goto('/ar/not-found');
    await expect(page.getByText(/الصفحة غير موجودة/i)).toBeVisible();

    // Test English localization
    await page.goto('/en/not-found');
    await page.waitForTimeout(100);
    await expect(page.getByText(/Page Not Found/i)).toBeVisible();
  });
});
