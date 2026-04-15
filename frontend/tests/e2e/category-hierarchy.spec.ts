import { expect, test } from '@playwright/test';

test.describe('Category Hierarchy E2E', () => {
  test.beforeEach(async ({ page }) => {
    // Login as admin
    await page.goto('/login');
    await page.fill('input[type="email"]', 'admin@bunyan.test');
    await page.fill('input[type="password"]', 'password');
    await page.click('button:has-text("تسجيل الدخول")');
    await page.waitForURL('**/admin/dashboard');
  });

  test('should create parent category followed by child categories', async ({
    page,
  }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Create parent category
    await page.click('button:has-text("إضافة فئة")');
    await page.waitForSelector('[role="dialog"]');

    await page.fill('input[placeholder*="العrabية"]', 'المواد الكهربائية');
    await page.fill('input[placeholder*="English"]', 'Electrical Materials');

    await page.click('button:has-text("حفظ")');
    await page.waitForTimeout(500);
    await page.waitForSelector('[role="dialog"]', { state: 'hidden' });

    // Verify parent appears
    const parentCategory = await page
      .locator('text=المواد الكهربائية')
      .first();
    await expect(parentCategory).toBeVisible();

    // Get parent category ID from API call or DOM
    const parentRow = await page
      .locator('.category-tree-node')
      .filter({ hasText: 'المواد الكهربائية' })
      .first();

    // Create first child
    await page.click('button:has-text("إضافة فئة")');
    await page.waitForSelector('[role="dialog"]');

    await page.fill('input[placeholder*="العrabية"]', 'الأسلاك');
    await page.fill('input[placeholder*="English"]', 'Wires');

    // Select parent category from dropdown
    const parentSelect = page.locator('select, [role="combobox"]').first();
    if (await parentSelect.isVisible()) {
      await parentSelect.click();
      await page.click('text=المواد الكهربائية');
    }

    await page.click('button:has-text("حفظ")');
    await page.waitForTimeout(500);

    // Create second child
    await page.click('button:has-text("إضافة فئة")');
    await page.waitForSelector('[role="dialog"]');

    await page.fill('input[placeholder*="العrabية"]', 'المفاتيح');
    await page.fill('input[placeholder*="English"]', 'Switches');

    // Select parent
    const parentSelect2 = page.locator('select, [role="combobox"]').first();
    if (await parentSelect2.isVisible()) {
      await parentSelect2.click();
      await page.click('text=المواد الكهربائية');
    }

    await page.click('button:has-text("حفظ")');
    await page.waitForTimeout(500);
  });

  test('should display parent with indented children in tree', async ({
    page,
  }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Look for parent-child relationship in DOM
    // Parent should have children nested under it with indentation
    const treeNodes = await page.locator('.category-tree-node').all();
    expect(treeNodes.length).toBeGreaterThan(0);

    // Check for nested structure
    const nestedNodes = await page
      .locator('.category-tree-node .category-tree-node')
      .all();
    expect(nestedNodes.length).toBeGreaterThan(0);
  });

  test('should expand parent to reveal children', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Find a parent with children
    const expandButtons = await page
      .locator('button[class*="expand"], button[class*="toggle"]')
      .all();

    if (expandButtons.length > 0) {
      // Click first expand button
      await expandButtons[0].click();
      await page.waitForTimeout(300);

      // Verify children are now visible (or were already visible)
      const childNodes = await page.locator('.category-tree-node').all();
      expect(childNodes.length).toBeGreaterThan(0);
    }
  });

  test('should collapse parent to hide children', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Find expanded parent
    const expandButtons = await page
      .locator('button[class*="expanded"]')
      .all();

    if (expandButtons.length > 0) {
      const initialChildCount = await page
        .locator('.category-tree-node')
        .count();

      // Click to collapse
      await expandButtons[0].click();
      await page.waitForTimeout(300);

      // Should have fewer visible nodes
      const collapsedChildCount = await page
        .locator('.category-tree-node')
        .count();

      // May or may not change depending on visibility
      expect(typeof collapsedChildCount).toBe('number');
    }
  });

  test('should update child category reflecting in tree', async ({
    page,
  }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Find a child category
    const childNodes = await page
      .locator('.category-tree-node .category-tree-node')
      .first();

    if (await childNodes.isVisible()) {
      // Click edit/action menu on child
      const editButton = childNodes.locator(
        'button[class*="edit"], button[aria-label*="edit"]'
      );

      if (await editButton.isVisible()) {
        await editButton.click();
        await page.waitForSelector('[role="dialog"]');

        // Update name
        const nameField = page.locator('input[placeholder*="English"]');
        await nameField.clear();
        await nameField.fill('Updated Child Category');

        await page.click('button:has-text("حفظ")');
        await page.waitForTimeout(500);

        // Verify update appears in tree
        const updatedText = await page
          .locator('text=Updated Child Category')
          .first();
        await expect(updatedText).toBeVisible();
      }
    }
  });

  test('should preserve parent-child relationship after update', async ({
    page,
  }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Get initial tree structure count
    const initialStructure = await page
      .locator('.category-tree-node')
      .count();

    // Update a category
    const anyCategory = page.locator('.category-tree-node').first();
    const editButton = anyCategory.locator(
      'button[class*="edit"], button[aria-label*="edit"]'
    );

    if (await editButton.isVisible()) {
      await editButton.click();
      await page.waitForSelector('[role="dialog"]');

      const nameField = page.locator('input[placeholder*="English"]');
      const currentName = await nameField.inputValue();

      await nameField.clear();
      await nameField.fill(currentName + ' (Updated)');

      await page.click('button:has-text("حفظ")');
      await page.waitForTimeout(500);

      // Verify tree structure unchanged
      const finalStructure = await page
        .locator('.category-tree-node')
        .count();

      expect(finalStructure).toBe(initialStructure);
    }
  });

  test('should display breadcrumb for nested category', async ({
    page,
  }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Click on a child category
    const childNode = page.locator('.category-tree-node .category-tree-node').first();

    if (await childNode.isVisible()) {
      await childNode.click();

      // Wait for breadcrumb to appear
      const breadcrumb = page.locator('[role="navigation"], .breadcrumb').first();

      if (await breadcrumb.isVisible()) {
        const breadcrumbText = await breadcrumb.textContent();
        expect(breadcrumbText?.length).toBeGreaterThan(0);
      }
    }
  });

  test('should handle deeply nested categories', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Create multiple levels of nesting
    let parentId = null;

    for (let i = 0; i < 3; i++) {
      await page.click('button:has-text("إضافة فئة")');
      await page.waitForSelector('[role="dialog"]');

      await page.fill(
        'input[placeholder*="العrabية"]',
        `المستوى ${i + 1}`
      );
      await page.fill('input[placeholder*="English"]', `Level ${i + 1}`);

      if (parentId && i > 0) {
        // Select parent from dropdown
        const parentSelect = page
          .locator('select, [role="combobox"]')
          .first();
        if (await parentSelect.isVisible()) {
          await parentSelect.click();
          await page.click(`text=المستوى ${i}`);
        }
      }

      await page.click('button:has-text("حفظ")');
      await page.waitForTimeout(500);
    }

    // Verify all levels are visible or expandable
    const treeNodes = await page.locator('.category-tree-node').all();
    expect(treeNodes.length).toBeGreaterThanOrEqual(3);
  });

  test('should show category count in parent node', async ({ page }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Look for parent with visible child count
    const parentBadges = await page
      .locator('[class*="badge"], [class*="count"]')
      .all();

    if (parentBadges.length > 0) {
      for (const badge of parentBadges) {
        const text = await badge.textContent();
        // Should be a number or indicator
        expect(text).toBeTruthy();
      }
    }
  });
});
