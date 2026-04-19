import { expect, test } from '@playwright/test';

test.describe('Projects Module', () => {
  test('projects listing page loads', async ({ page }) => {
    await page.goto('/admin/projects');
    // Page should render without errors
    await expect(page).toHaveURL(/\/admin\/projects/);
  });

  test('create project page loads', async ({ page }) => {
    await page.goto('/admin/projects/create');
    await expect(page).toHaveURL(/\/admin\/projects\/create/);
  });
});
