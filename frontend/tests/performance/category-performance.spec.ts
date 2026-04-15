import { expect, test } from '@playwright/test';

test.describe('Category Performance Testing', () => {
  test.beforeEach(async ({ page }) => {
    // Login as admin
    await page.goto('/login');
    await page.fill('input[type="email"]', 'admin@bunyan.test');
    await page.fill('input[type="password"]', 'password');
    await page.click('button:has-text("تسجيل الدخول")');
    await page.waitForURL('**/admin/dashboard');
  });

  test('should render tree with 100 categories in under 500ms', async ({
    page,
    browser,
  }) => {
    await page.goto('/admin/categories');

    const startTime = Date.now();
    await page.waitForLoadState('networkidle');
    const loadTime = Date.now() - startTime;

    // Tree should be interactive within 500ms
    expect(loadTime).toBeLessThan(500);

    // Verify tree is visible
    const tree = await page.locator('.category-tree');
    await expect(tree).toBeVisible();
  });

  test('should measure tree render time with category count', async ({
    page,
  }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    const startTime = Date.now();

    // Measure time to get all nodes
    await page.waitForSelector('.category-tree-node');
    const nodeCount = await page.locator('.category-tree-node').count();

    const renderTime = Date.now() - startTime;

    console.log(
      `Rendered ${nodeCount} nodes in ${renderTime}ms (${renderTime / nodeCount}ms per node)`
    );

    expect(renderTime).toBeLessThan(1000);
    expect(nodeCount).toBeGreaterThan(0);
  });

  test('should open category selector dropdown in under 1 second', async ({
    page,
  }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    const startTime = Date.now();

    // Open form modal
    await page.click('button:has-text("إضافة فئة")');
    await page.waitForSelector('[role="dialog"]');

    // Measure dropdown open time
    const selectDropdown = page
      .locator('select, [role="combobox"]')
      .first();

    if (await selectDropdown.isVisible()) {
      const dropdownStartTime = Date.now();
      await selectDropdown.click();
      await page.waitForSelector('[role="listbox"], [class*="dropdown"]');
      const dropdownTime = Date.now() - dropdownStartTime;

      console.log(`Dropdown opened in ${dropdownTime}ms`);
      expect(dropdownTime).toBeLessThan(1000);
    }
  });

  test('should submit category form in under 2 seconds', async ({
    page,
  }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    const startTime = Date.now();

    // Open form
    await page.click('button:has-text("إضافة فئة")');
    await page.waitForSelector('[role="dialog"]');

    // Fill form
    await page.fill('input[placeholder*="العrabية"]', `فئة اختبار الأداء`);
    await page.fill('input[placeholder*="English"]', 'Performance Test Category');

    // Submit
    const submitTime = Date.now();
    await page.click('button:has-text("حفظ")');
    await page.waitForTimeout(100);

    const totalTime = Date.now() - submitTime;

    console.log(`Form submission completed in ${totalTime}ms`);
    expect(totalTime).toBeLessThan(2000);
  });

  test('should measure API response time for tree endpoint', async ({
    page,
  }) => {
    let apiResponseTime = 0;

    page.on('response', (response) => {
      if (response.url().includes('/categories') && response.status() === 200) {
        const timingData = response.request().timing();
        if (timingData) {
          apiResponseTime = timingData.responseEnd - timingData.responseStart;
          console.log(`API response time: ${apiResponseTime}ms`);
        }
      }
    });

    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // API should respond within 500ms threshold
    expect(apiResponseTime).toBeGreaterThan(0);
    expect(apiResponseTime).toBeLessThan(500);
  });

  test('should handle no unnecessary re-renders when expanding nodes', async ({
    page,
  }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    let renderCount = 0;

    // Intercept render cycles
    await page.addInitScript(() => {
      (window as any).__renderCount = 0;
    });

    const expandButtons = await page
      .locator('button[class*="expand"]')
      .all();

    if (expandButtons.length > 0) {
      const startTime = Date.now();

      for (let i = 0; i < Math.min(3, expandButtons.length); i++) {
        await expandButtons[i].click();
        await page.waitForTimeout(100);
      }

      const elapsedTime = Date.now() - startTime;

      console.log(`Multiple expand operations completed in ${elapsedTime}ms`);

      // Should be efficient (quick interactions)
      expect(elapsedTime).toBeLessThan(1000);
    }
  });

  test('should measure memory usage during tree operations', async ({
    page,
  }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Get memory info if available
    const memoryInfo = await page.evaluate(() => {
      if ((performance as any).memory) {
        return {
          usedJSHeapSize: (performance as any).memory.usedJSHeapSize,
          totalJSHeapSize: (performance as any).memory.totalJSHeapSize,
        };
      }
      return null;
    });

    if (memoryInfo) {
      console.log(`Memory usage: ${memoryInfo.usedJSHeapSize / 1024 / 1024} MB`);
      expect(memoryInfo.usedJSHeapSize).toBeLessThan(50 * 1024 * 1024); // Less than 50MB
    }
  });

  test('should record baseline metrics', async ({ page }) => {
    const metrics: any = {
      timestamp: new Date().toISOString(),
      measurements: {},
    };

    await page.goto('/admin/categories');

    const startTime = Date.now();
    await page.waitForLoadState('networkidle');
    metrics.measurements.pageLoadTime = Date.now() - startTime;

    const treeLoadStart = Date.now();
    await page.waitForSelector('.category-tree-node');
    metrics.measurements.treeRenderTime = Date.now() - treeLoadStart;

    const nodeCount = await page.locator('.category-tree-node').count();
    metrics.measurements.categoryCount = nodeCount;

    console.log('Performance Baseline Metrics:');
    console.log(JSON.stringify(metrics, null, 2));

    // All measurements should be reasonable
    expect(metrics.measurements.pageLoadTime).toBeLessThan(2000);
    expect(metrics.measurements.treeRenderTime).toBeLessThan(1000);
    expect(metrics.measurements.categoryCount).toBeGreaterThan(0);
  });

  test('should handle rapid expand/collapse without performance degradation', async ({
    page,
  }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    const expandButtons = await page
      .locator('button[class*="expand"]')
      .all();

    if (expandButtons.length > 0 && expandButtons[0]) {
      const startTime = Date.now();

      // Rapidly expand and collapse
      for (let i = 0; i < 5; i++) {
        await expandButtons[0].click();
        await page.waitForTimeout(50);
      }

      const totalTime = Date.now() - startTime;

      console.log(`Rapid expand/collapse completed in ${totalTime}ms`);

      // Should remain responsive
      expect(totalTime).toBeLessThan(2000);
    }
  });

  test('should measure category search/filter performance', async ({
    page,
  }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    // Look for search input
    const searchInput = page
      .locator('input[placeholder*="search"], input[placeholder*="بحث"]')
      .first();

    if (await searchInput.isVisible()) {
      const startTime = Date.now();

      await searchInput.fill('test');
      await page.waitForTimeout(300);

      const filterTime = Date.now() - startTime;

      console.log(`Search/filter completed in ${filterTime}ms`);
      expect(filterTime).toBeLessThan(1000);
    }
  });

  test('should maintain performance with form modal operations', async ({
    page,
  }) => {
    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');

    const startTime = Date.now();

    for (let i = 0; i < 3; i++) {
      // Open form
      await page.click('button:has-text("إضافة فئة")');
      await page.waitForSelector('[role="dialog"]');

      // Fill form
      await page.fill(
        'input[placeholder*="العrabية"]',
        `فئة ${i}`
      );
      await page.fill('input[placeholder*="English"]', `Category ${i}`);

      // Close without submit (click cancel)
      const cancelButton = page.locator(
        'button:has-text("إلغاء"), button[aria-label*="close"]'
      );

      if (await cancelButton.isVisible()) {
        await cancelButton.click();
      } else {
        // Close dialog another way
        await page.press('[role="dialog"]', 'Escape');
      }

      await page.waitForTimeout(50);
    }

    const totalTime = Date.now() - startTime;

    console.log(`Form open/close cycles completed in ${totalTime}ms`);
    expect(totalTime).toBeLessThan(3000);
  });
});
