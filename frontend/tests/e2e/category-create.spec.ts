import { expect, test } from '@playwright/test';

test.describe('Category Creation E2E', () => {
  test.beforeEach(async ({ page }) => {
    // Login as admin
    await page.goto('/login');
    await page.fill('input[type="email"]', 'admin@bunyan.test');
    await page.fill('input[type="password"]', 'password');
    await page.click('button:has-text("تسجيل الدخول")'); // Arabic for "Sign In"
    await page.waitForURL('**/admin/dashboard');
  });

  test('should navigate to categories admin page and display tree', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Check for page title
    expect(await page.locator('h1, h2').first().textContent()).toContain('الفئات'); // "Categories" in Arabic
  });

  test('should create a new category with Arabic and English names', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Click add category button
    await page.click('button:has-text("إضافة فئة")'); // "Add Category" in Arabic

    // Wait for form modal
    await page.waitForSelector('[role="dialog"]', { timeout: 5000 });

    // Fill form
    await page.fill('input[placeholder*="العربية"]', 'فئة جديدة');
    await page.fill('input[placeholder*="English"]', 'New Category');

    // Submit form
    await page.click('button:has-text("حفظ")'); // "Save" in Arabic

    // Wait for success toast
    await page.waitForTimeout(500);

    // Verify category appears in tree
    const categoryText = await page.locator('text=فئة جديدة').first();
    await expect(categoryText).toBeVisible();
  });

  test('should verify category appears immediately after creation', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    const initialCount = await page.locator('.category-tree-node').count();

    // Create new category
    await page.click('button:has-text("إضافة فئة")');
    await page.waitForSelector('[role="dialog"]');

    await page.fill('input[placeholder*="العربية"]', 'فئة اختبار');
    await page.fill('input[placeholder*="English"]', 'Test Category');

    await page.click('button:has-text("حفظ")');
    await page.waitForTimeout(500);

    // Verify new count
    const newCount = await page.locator('.category-tree-node').count();
    expect(newCount).toBeGreaterThan(initialCount);
  });

  test('should show success notification after category creation', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    await page.click('button:has-text("إضافة فئة")');
    await page.waitForSelector('[role="dialog"]');

    await page.fill('input[placeholder*="العربية"]', 'فئة للإخطار');
    await page.fill('input[placeholder*="English"]', 'Notification Test');

    await page.click('button:has-text("حفظ")');

    // Wait for toast notification
    const toast = await page.locator('[role="status"]').first();
    await expect(toast).toBeVisible({ timeout: 3000 });

    // Verify success message
    const toastText = await toast.textContent();
    expect(toastText).toContain('تم'); // "Done" in Arabic
  });

  test('should close modal after successful submission', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    await page.click('button:has-text("إضافة فئة")');
    await page.waitForSelector('[role="dialog"]');

    await page.fill('input[placeholder*="العrabية"]', 'فئة أخرى');
    await page.fill('input[placeholder*="English"]', 'Another Category');

    await page.click('button:has-text("حفظ")');

    // Wait for modal to close
    await page.waitForSelector('[role="dialog"]', { state: 'hidden', timeout: 3000 });

    // Verify modal is not visible
    const modal = await page.locator('[role="dialog"]');
    await expect(modal).not.toBeVisible();
  });

  test('should prevent submission with empty required fields', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    await page.click('button:has-text("إضافة فئة")');
    await page.waitForSelector('[role="dialog"]');

    // Try submitting without filling fields
    const saveButton = page.locator('button:has-text("حفظ")');

    // Save button should be disabled or show error
    if (await saveButton.isEnabled()) {
      await saveButton.click();
      // Should show validation error
      const errorText = await page.locator('[class*="error"]').first().textContent();
      expect(errorText).toBeTruthy();
    } else {
      await expect(saveButton).toBeDisabled();
    }
  });

  test('should validate Arabic name field', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    await page.click('button:has-text("إضافة فئة")');
    await page.waitForSelector('[role="dialog"]');

    // Fill only English name
    await page.fill('input[placeholder*="English"]', 'Test');

    // Try to submit or check for validation error
    await page.click('button:has-text("حفظ")');

    // Should show error about Arabic name
    const error = await page.locator('text=مطلوب').first();
    await expect(error).toBeVisible({ timeout: 2000 });
  });

  test('should validate English name field', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    await page.click('button:has-text("إضافة فئة")');
    await page.waitForSelector('[role="dialog"]');

    // Fill only Arabic name
    await page.fill('input[placeholder*="العrabية"]', 'اختبار');

    // Try to submit
    await page.click('button:has-text("حفظ")');

    // Should show error about English name
    const error = await page.locator('text=required|مطلوب').first();
    await expect(error).toBeVisible({ timeout: 2000 });
  });
});
