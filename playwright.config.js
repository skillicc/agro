// @ts-check
import { defineConfig, devices } from '@playwright/test';

/**
 * লোকাল টেস্ট: http://localhost:8000 (php artisan serve)
 *
 * লোকাল রান:   npm run test:e2e
 */

const baseURL = 'http://localhost:8000';

export default defineConfig({
  testDir: './tests/e2e',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  reporter: [['list'], ['html', { open: 'never' }]],

  use: {
    baseURL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    headless: true,
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],

  // PHP artisan serve অটো স্টার্ট
  webServer: {
    command: 'php artisan serve --port=8000',
    url: 'http://localhost:8000',
    reuseExistingServer: true,
    timeout: 30000,
  },
});
