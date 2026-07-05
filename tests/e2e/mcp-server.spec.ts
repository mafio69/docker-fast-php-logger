import { test, expect } from '@playwright/test';

test('MCP Server page should load and display instructions', async ({ page }) => {
  // Navigate to the MCP server page in DevBox
  await page.goto('/mcp');

  // Verify the page title
  await expect(page).toHaveTitle(/MCP Server/);

  // Verify there is an instruction section
  await expect(page.getByRole('heading', { name: 'MCP Server - Narzędzia Demo' })).toBeVisible();
  await expect(page.getByText('Instrukcja obsługi')).toBeVisible();
});
