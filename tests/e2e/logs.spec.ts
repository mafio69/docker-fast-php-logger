import { test, expect } from '@playwright/test';

test('Log viewer should return main app shell', async ({ page }) => {
  // Navigate to the log viewer page in DevBox
  const response = await page.goto('/logs');

  // Fail fast with a clear signal if the backend returns an error page
  // (e.g. "headers already sent") instead of the viewer shell.
  expect(response?.status(), 'GET /logs should return 200').toBe(200);

  // Verify the page title
  await expect(page).toHaveTitle(/fast-php-log-viewer/i);

  // Verify the Vue app mount point rendered
  await expect(page.locator('#app')).toBeVisible();
});
