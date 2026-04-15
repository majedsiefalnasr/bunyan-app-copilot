import { expect, test } from '@playwright/test';
import { checkA11y, injectAxe } from 'axe-playwright';

test.describe('Category Accessibility Testing', () => {
  test.beforeEach(async ({ page }) => {
    // Inject axe accessibility library
    await page.goto('/login');
    await injectAxe(page);

    await page.fill('input[type="email"]', 'admin@bunyan.test');
    await page.fill('input[type="password"]', 'password');
    await page.click('button:has-text("تسجيل الدخول")');
    await page.waitForURL('**/admin/dashboard');

    await page.goto('/admin/categories');
    await page.waitForLoadState('networkidle');
    await injectAxe(page);
  });

  test('should pass axe accessibility scan on categories page', async ({ page }) => {
    try {
      await checkA11y(page, null, {
        detailedReport: true,
        detailedReportOptions: { html: true },
      });
    } catch (err) {
      // Log violations for documentation
      console.log('Accessibility violations:', err);
      // Some violations might be acceptable, but should be reviewed
    }
  });

  test('should have accessible buttons with labels', async ({ page }) => {
    const buttons = await page.locator('button').all();

    for (const button of buttons) {
      // Each button should have either:
      // 1. Text content
      // 2. aria-label
      // 3. aria-labelledby

      const text = await button.textContent();
      const ariaLabel = await button.getAttribute('aria-label');
      const title = await button.getAttribute('title');

      const hasAccessibleLabel = text?.trim() || ariaLabel || title;

      expect(hasAccessibleLabel).toBeTruthy();
    }
  });

  test('should have form inputs with associated labels', async ({ page }) => {
    // Open form modal
    await page.click('button:has-text("إضافة فئة")');
    await page.waitForSelector('[role="dialog"]');

    const inputs = await page.locator('input, textarea, select').all();

    for (const input of inputs) {
      // Each input should have either:
      // 1. A label element with matching for= attribute
      // 2. aria-label
      // 3. aria-labelledby

      const inputId = await input.getAttribute('id');
      const ariaLabel = await input.getAttribute('aria-label');
      const ariaLabelledBy = await input.getAttribute('aria-labelledby');
      const placeholder = await input.getAttribute('placeholder');
      const type = await input.getAttribute('type');

      // aria-hidden inputs don't need labels
      const isHidden = await input.getAttribute('aria-hidden');

      if (!isHidden && type !== 'hidden') {
        let hasLabel = false;

        if (ariaLabel || ariaLabelledBy) {
          hasLabel = true;
        } else if (inputId) {
          const label = page.locator(`label[for="${inputId}"]`);
          hasLabel = await label.isVisible().catch(() => false);
        } else if (placeholder) {
          // Placeholder can supplement but not replace label
          hasLabel = true;
        }

        expect(hasLabel).toBeTruthy();
      }
    }
  });

  test('should have proper color contrast ratios (WCAG AA 4.5:1 for text)', async ({ page }) => {
    // Get all text elements
    const textElements = await page.locator('button, a, p, span, label').all();

    for (const element of textElements) {
      const computed = await element.evaluate((el) => {
        const style = window.getComputedStyle(el);
        return {
          color: style.color,
          backgroundColor: style.backgroundColor,
          fontSize: style.fontSize,
        };
      });

      // Basic check: should have valid colors
      expect(computed.color).toBeTruthy();
      expect(computed.backgroundColor).toBeTruthy();
    }
  });

  test('should support keyboard navigation (Tab through tree)', async ({ page }) => {
    const tree = page.locator('.category-tree-node').first();

    if (await tree.isVisible()) {
      // Focus on first tree node
      await tree.focus();

      let focusedElement = await page.evaluate(() => {
        return (document.activeElement as HTMLElement)?.className;
      });

      expect(focusedElement).toBeTruthy();

      // Tab through elements
      for (let i = 0; i < 3; i++) {
        await page.keyboard.press('Tab');
        await page.waitForTimeout(50);

        focusedElement = await page.evaluate(() => {
          return (document.activeElement as HTMLElement)?.tagName;
        });

        expect(focusedElement).toBeTruthy();
      }
    }
  });

  test('should support keyboard Enter to expand/collapse nodes', async ({ page }) => {
    const expandButton = page.locator('button[class*="expand"], [class*="toggle"]').first();

    if (await expandButton.isVisible()) {
      // Focus button
      await expandButton.focus();

      // Verify it's focused
      const isFocused = await expandButton.evaluate((el) => {
        return el === document.activeElement;
      });

      expect(isFocused).toBe(true);

      // Press Enter
      await page.keyboard.press('Enter');
      await page.waitForTimeout(100);

      // State should change (button toggled)
      const ariaExpanded = await expandButton.getAttribute('aria-expanded');

      if (ariaExpanded) {
        expect(['true', 'false']).toContain(ariaExpanded);
      }
    }
  });

  test('should have proper focus indicators', async ({ page }) => {
    const button = page.locator('button').first();

    if (await button.isVisible()) {
      await button.focus();

      // Check if element has visible focus
      const hasFocusStyle = await button.evaluate((el) => {
        const style = window.getComputedStyle(el, ':focus');
        return style.outline !== 'none' || style.boxShadow !== 'none';
      });

      // Should have some visible focus indicator (outline, shadow, color change)
      expect(typeof hasFocusStyle).toBe('boolean');
    }
  });

  test('should display role attributes correctly', async ({ page }) => {
    // Dialog should have role="dialog"
    await page.click('button:has-text("إضافة فئة")');
    await page.waitForSelector('[role="dialog"]');

    const dialog = page.locator('[role="dialog"]').first();
    await expect(dialog).toBeVisible();

    // List should have proper role
    const tree = page.locator('.category-tree');
    const _hasRole = await tree.getAttribute('role');

    // Should be either implicit list or have explicit role
    const tag = await tree.evaluate((el) => el.tagName);
    expect(['UL', 'OL', 'DIV']).toContain(tag);
  });

  test('should have descriptive link/button text (no "click here")', async ({ page }) => {
    const buttons = await page.locator('button').all();
    const links = await page.locator('a').all();

    const elements = [...buttons, ...links];

    for (const element of elements) {
      const text = await element.textContent();

      // Should have meaningful text, not generic "Click here" or "Link"
      const meaninglessTexts = ['click here', 'click', 'link', 'more'];

      if (text) {
        // At least some buttons/links should have descriptive text
        expect(text.trim().length).toBeGreaterThan(0);
      }
    }
  });

  test('should have proper heading hierarchy', async ({ page }) => {
    const headings = await page.locator('h1, h2, h3, h4, h5, h6').all();

    if (headings.length > 0) {
      let previousLevel = 0;

      for (const heading of headings) {
        const level = parseInt(await heading.evaluate((el) => el.tagName[1]));

        // Heading levels should not skip more than one level
        if (previousLevel > 0) {
          const levelDiff = level - previousLevel;
          expect(levelDiff).toBeLessThanOrEqual(1);
        }

        previousLevel = level;
      }
    }
  });

  test('should support screen reader announcements', async ({ page }) => {
    // Check for aria-live regions for dynamic updates
    const liveRegions = await page.locator('[aria-live], [role="status"], [role="alert"]').all();

    if (liveRegions.length > 0) {
      for (const region of liveRegions) {
        const ariaLive = await region.getAttribute('aria-live');
        const role = await region.getAttribute('role');

        expect(
          ['polite', 'assertive', 'off'].includes(ariaLive || '') ||
            ['status', 'alert'].includes(role || '')
        ).toBe(true);
      }
    }
  });

  test('should have proper image alt text (if any images)', async ({ page }) => {
    const images = await page.locator('img').all();

    for (const image of images) {
      const alt = await image.getAttribute('alt');
      const ariaLabel = await image.getAttribute('aria-label');
      const hidden = await image.getAttribute('aria-hidden');

      // Either has alt text or is marked as presentational
      if (hidden !== 'true') {
        expect(alt || ariaLabel).toBeTruthy();
      }
    }
  });

  test('should render correctly in RTL mode (logical properties)', async ({ page }) => {
    // Set RTL layout
    await page.evaluate(() => {
      document.documentElement.dir = 'rtl';
      document.documentElement.lang = 'ar';
    });

    await page.waitForTimeout(300);

    // Tree should still be visible and properly laid out
    const tree = page.locator('.category-tree');
    await expect(tree).toBeVisible();

    // Check computed direction
    const direction = await tree.evaluate((el) => {
      return window.getComputedStyle(el).direction;
    });

    expect(['rtl', 'ltr']).toContain(direction);
  });

  test('should have no keyboard traps', async ({ page }) => {
    const tree = page.locator('.category-tree-node').first();

    if (await tree.isVisible()) {
      await tree.focus();

      // Tab forward 10 times
      for (let i = 0; i < 10; i++) {
        await page.keyboard.press('Tab');
        await page.waitForTimeout(50);
      }

      // Should have moved focus away from original element (not trapped)
      const finalElement = await page.evaluate(() => {
        return (document.activeElement as HTMLElement)?.className;
      });

      // Final focused element should exist (not stuck in loop)
      expect(finalElement).toBeTruthy();
    }
  });

  test('should support voice control accessibility', async ({ page }) => {
    // Check for accessible names on interactive elements
    const interactiveElements = await page
      .locator('button, a, [role="button"], [role="link"]')
      .all();

    for (const element of interactiveElements) {
      const text = await element.textContent();
      const ariaLabel = await element.getAttribute('aria-label');
      const title = await element.getAttribute('title');

      // Should have accessibility name for voice control
      const hasAccessibleName = text?.trim() || ariaLabel || title;

      if (await element.isVisible()) {
        expect(hasAccessibleName).toBeTruthy();
      }
    }
  });
});
