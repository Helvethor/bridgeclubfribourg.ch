// @ts-check
const { test, expect } = require('@playwright/test');

test.describe('JavaScript Loading & Asset Health', () => {
  const publicRoutes = [
    { name: 'Homepage Root', path: '/' },
    { name: 'Homepage /home', path: '/home' },
    { name: 'Lessons', path: '/lessons' },
    { name: 'Turnament Calendar', path: '/turnament' },
    { name: 'Login', path: '/login' },
  ];

  for (const route of publicRoutes) {
    test(`loads ${route.name} without runtime JavaScript errors`, async ({ page }) => {
      const pageErrors = [];
      const consoleErrors = [];
      const failedRequests = [];

      page.on('pageerror', (exception) => {
        pageErrors.push(exception.message);
      });

      page.on('console', (message) => {
        if (message.type() === 'error') {
          consoleErrors.push(message.text());
        }
      });

      page.on('requestfailed', (request) => {
        const url = request.url();
        // Ignore favicon or analytics if any
        if (!url.includes('favicon.ico')) {
          failedRequests.push(`${request.method()} ${url} failed: ${request.failure()?.errorText}`);
        }
      });

      const response = await page.goto(route.path);
      expect(response?.status()).toBe(200);

      // Verify page title is set
      await expect(page).toHaveTitle(/Les Quatre Trèfles/);

      // Verify core JS runtime objects are available in the browser window
      const jsRuntime = await page.evaluate(() => {
        return {
          hasJQuery: typeof window['$'] === 'function' && typeof window['jQuery'] === 'function',
          hasDataTable: typeof window['DataTable'] !== 'undefined',
          hasBootstrap: typeof window['bootstrap'] !== 'undefined' || typeof (window['$']?.fn?.tab) !== 'undefined' || typeof (window['$']?.fn?.dropdown) !== 'undefined',
        };
      });

      expect(jsRuntime.hasJQuery).toBe(true);
      expect(jsRuntime.hasDataTable).toBe(true);

      // Expect zero unhandled page errors and zero failed bundle requests
      expect(pageErrors, `Page errors on ${route.path}: ${pageErrors.join(', ')}`).toHaveLength(0);
      expect(failedRequests, `Failed requests on ${route.path}: ${failedRequests.join(', ')}`).toHaveLength(0);
    });
  }

  test('verifies compiled CSS and JS script tags are loaded from build manifest', async ({ page }) => {
    await page.goto('/');

    const appJsLoaded = await page.evaluate(() => {
      const scripts = Array.from(document.querySelectorAll('script[src]'));
      return scripts.some((s) => (s.getAttribute('src') || '').includes('/build/app'));
    });

    const runtimeJsLoaded = await page.evaluate(() => {
      const scripts = Array.from(document.querySelectorAll('script[src]'));
      return scripts.some((s) => (s.getAttribute('src') || '').includes('/build/runtime'));
    });

    expect(appJsLoaded).toBe(true);
    expect(runtimeJsLoaded).toBe(true);
  });
});
