// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('TinyMCE Rich Text Editor', () => {
  async function loginAsAdmin(page) {
    await page.goto('/login');
    await page.fill('input[name="_username"]', 'admin');
    await page.fill('input[name="_password"]', 'admin');
    await page.click('button[type="submit"]');
    await page.waitForURL((url) => !url.pathname.includes('/login'));
  }

  test('initializes TinyMCE correctly in the admin lessons page', async ({ page }) => {
    await loginAsAdmin(page);

    const consoleErrors = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        consoleErrors.push(msg.text());
      }
    });

    await page.goto('/admin/lessons');
    await expect(page).toHaveTitle(/Gestion des cours|Les Quatre Trèfles/);

    // Wait for TinyMCE UI container to be injected into DOM
    await page.waitForSelector('.tox-tinymce', { timeout: 10000 });
    const isTinyMceVisible = await page.isVisible('.tox-tinymce');
    expect(isTinyMceVisible).toBe(true);

    // Verify TinyMCE JS object and active editor
    const tinyMceReady = await page.evaluate(() => {
      // @ts-ignore
      return typeof window.tinymce !== 'undefined' && Array.isArray(window.tinymce.get()) && window.tinymce.get().length > 0;
    });
    expect(tinyMceReady).toBe(true);

    expect(consoleErrors, `Console errors on admin lessons: ${consoleErrors.join(', ')}`).toHaveLength(0);
  });

  test('can edit content via TinyMCE and verify persistence on public page', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto('/admin/lessons');
    await page.waitForSelector('.tox-tinymce', { timeout: 10000 });

    const testTimestamp = Date.now();
    const testHtml = `<p>Test E2E TinyMCE Content - ${testTimestamp}</p>`;

    // Set content in TinyMCE editor and trigger save to textarea
    await page.evaluate((html) => {
      // @ts-ignore
      const editor = window.tinymce.get('form_content') || window.tinymce.get()[0];
      editor.setContent(html);
      window.tinymce.triggerSave();
    }, testHtml);

    // Submit form and wait for redirect
    await Promise.all([
      page.waitForNavigation(),
      page.click('button[type="submit"]'),
    ]);

    // Verify success flash message
    await expect(page.locator('.alert-success, .alert')).toBeVisible();

    // Now visit public /lessons page and verify content rendered
    await page.goto('/lessons');
    const content = await page.textContent('body');
    expect(content).toContain(`Test E2E TinyMCE Content - ${testTimestamp}`);
  });
});
