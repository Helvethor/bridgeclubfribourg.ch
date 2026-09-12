// @ts-check
const { defineConfig } = require('@playwright/test');
const fs = require('fs');

const chromiumPath = process.env.CHROMIUM_PATH || (fs.existsSync('/bin/chromium') ? '/bin/chromium' : undefined);

module.exports = defineConfig({
  testDir: './tests/e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: [['list'], ['html', { open: 'never', outputFolder: 'var/playwright-report' }]],
  use: {
    baseURL: process.env.BASE_URL || 'https://localhost:21918',
    ignoreHTTPSErrors: true,
    trace: 'on-first-retry',
    launchOptions: {
      executablePath: chromiumPath,
      args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
    },
  },
  projects: [
    {
      name: 'chromium',
      use: {
        viewport: { width: 1280, height: 720 },
      },
    },
  ],
});
