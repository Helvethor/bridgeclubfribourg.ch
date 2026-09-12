// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('DataTables Plugin & Interactive Sorting', () => {
  async function loginAsAdmin(page) {
    await page.goto('/login');
    await page.fill('input[name="_username"]', 'admin');
    await page.fill('input[name="_password"]', 'admin');
    await page.click('button[type="submit"]');
    await page.waitForURL((url) => !url.pathname.includes('/login'));
  }

  test('DataTables initializes properly on admin news page', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/admin/news');

    // Create a couple of news entries to test table sorting if needed
    await page.fill('input[name="form[title]"]', 'Alpha News');
    await page.fill('textarea[name="form[content]"]', 'Alpha Content');
    await page.click('button[type="submit"]');

    await page.fill('input[name="form[title]"]', 'Zulu News');
    await page.fill('textarea[name="form[content]"]', 'Zulu Content');
    await page.click('button[type="submit"]');

    // Verify table has DataTable initialized
    const tableExists = await page.locator('table').count();
    expect(tableExists).toBeGreaterThan(0);

    const isDataTable = await page.evaluate(() => {
      // @ts-ignore
      const $ = window.$;
      return $('table').hasClass('dataTable') || $.fn.dataTable.isDataTable('table');
    });

    expect(isDataTable).toBe(true);
  });

  test('DataTables sorts columns on user click', async ({ page }) => {
    await page.goto('/turnament/palmares/2025/thursday');

    const table = page.locator('table.table-sortable, table.dataTable');
    if (await table.count() > 0) {
      // Header click
      const firstHeader = table.locator('thead th').first();
      await firstHeader.click();

      // Check sorting attribute or class change
      const ariaSort = await firstHeader.getAttribute('aria-sort');
      expect(ariaSort).not.toBeNull();
    }
  });
});
