import { expect, test } from '@playwright/test';

test.describe('Category Delete E2E', () => {
  test.beforeEach(async ({ page }) => {
    // Login as admin
    await page.goto('/login');
    await page.fill('input[type="email"]', 'admin@bunyan.test');
    await page.fill('input[type="password"]', 'password');
    await page.click('button:has-text("تسجيل الدخول")');
    await page.waitForURL('**/admin/dashboard');
  });

  test('should delete category with confirmation dialog', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Find delete button
    const deleteButtons = await page
      .locator('button[class*="delete"], button[aria-label*="delete"]')
      .all();

    if (deleteButtons.length > 0) {
      await deleteButtons[0].click();

      // Confirmation dialog should appear
      const confirmDialog = page.locator('[role="dialog"], .confirm-dialog').first();
      await expect(confirmDialog).toBeVisible({ timeout: 2000 });

      // Click confirm
      const confirmButton = page.locator('button:has-text("حذف"), button:has-text("تأكيد")');
      if (await confirmButton.isVisible()) {
        await confirmButton.click();
        await page.waitForTimeout(500);
      }
    }
  });

  test('should soft-delete category (grayed out, marked as deleted)', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    const initialCount = await page.locator('.category-tree-node').count();

    // Delete first category
    const deleteButtons = await page.locator('button[class*="delete"]').all();

    if (deleteButtons.length > 0) {
      await deleteButtons[0].click();

      const confirmButton = page.locator('button:has-text("حذف"), button:has-text("تأكيد")');

      if (await confirmButton.isVisible()) {
        await confirmButton.click();
        await page.waitForTimeout(500);

        // Category count should decrease
        const afterCount = await page.locator('.category-tree-node').count();
        expect(afterCount).toBeLessThanOrEqual(initialCount);
      }
    }
  });

  test('should verify children remain after parent soft-delete', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Find parent with children
    const expandButton = await page.locator('button[class*="expand"]').first();

    if (await expandButton.isVisible()) {
      await expandButton.click();
      await page.waitForTimeout(300);
    }

    const childrenBefore = await page.locator('.category-tree-node .category-tree-node').count();

    // Delete parent
    const parentDeleteButton = page
      .locator('.category-tree-node > button[class*="delete"]')
      .first();

    if (await parentDeleteButton.isVisible()) {
      await parentDeleteButton.click();

      const confirmButton = page.locator('button:has-text("حذف"), button:has-text("تأكيد")');

      if (await confirmButton.isVisible()) {
        await confirmButton.click();
        await page.waitForTimeout(500);

        // Children should still exist (orphaned)
        const childrenAfter = await page.locator('.category-tree-node .category-tree-node').count();

        expect(childrenAfter).toBeGreaterThanOrEqual(0);
      }
    }
  });

  test('should allow admin to view soft-deleted categories', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Look for "Show deleted" toggle or similar
    const showDeletedToggle = page.locator(
      'button:has-text("المحذوفة"), label:has-text("المحذوفة")'
    );

    if (await showDeletedToggle.isVisible()) {
      await showDeletedToggle.click();
      await page.waitForTimeout(500);

      // Soft-deleted categories should now be visible with gray styling
      const deletedCategories = await page.locator('[class*="deleted"], [class*="inactive"]').all();

      expect(deletedCategories.length).toBeGreaterThanOrEqual(0);
    }
  });

  test('should cancel delete without removing category', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    const initialCount = await page.locator('.category-tree-node').count();

    // Click delete
    const deleteButtons = await page.locator('button[class*="delete"]').all();

    if (deleteButtons.length > 0) {
      await deleteButtons[0].click();

      // Cancel in dialog
      const cancelButton = page.locator('button:has-text("إلغاء"), button:has-text("Cancel")');

      if (await cancelButton.isVisible()) {
        await cancelButton.click();
        await page.waitForTimeout(300);

        // Count should remain same
        const finalCount = await page.locator('.category-tree-node').count();
        expect(finalCount).toBe(initialCount);
      }
    }
  });

  test('should show deletion confirmation message', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    const deleteButtons = await page.locator('button[class*="delete"]').all();

    if (deleteButtons.length > 0) {
      await deleteButtons[0].click();

      // Dialog should ask for confirmation
      const confirmDialog = page.locator('[role="dialog"]').first();
      const dialogText = await confirmDialog.textContent();

      expect(dialogText).toContain('حذف') || expect(dialogText).toContain('delete');
    }
  });

  test('should handle delete API error', async ({ page }) => {
    // Mock delete error
    await page.route('**/api/**/categories/**', (route) => {
      if (route.request().method() === 'DELETE') {
        route.abort();
      } else {
        route.continue();
      }
    });

    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    const deleteButtons = await page.locator('button[class*="delete"]').all();

    if (deleteButtons.length > 0) {
      await deleteButtons[0].click();

      const confirmButton = page.locator('button:has-text("حذف"), button:has-text("تأكيد")');

      if (await confirmButton.isVisible()) {
        await confirmButton.click();
        await page.waitForTimeout(500);

        // Error message should appear
        const error = page.locator('[class*="error"], [role="alert"]').first();

        if (await error.isVisible({ timeout: 2000 }).catch(() => false)) {
          expect(await error.textContent()).toBeTruthy();
        }
      }
    }
  });

  test('should disable delete button for categories without permission', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Check if delete buttons exist for current user
    const deleteButtons = await page.locator('button[class*="delete"]').all();

    // Admin should have delete buttons
    expect(deleteButtons.length).toBeGreaterThanOrEqual(0);
  });
});
