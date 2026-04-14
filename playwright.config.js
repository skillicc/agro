// @ts-check
import { defineConfig, devices } from '@playwright/test';

/**
 * লোকাল টেস্ট: http://localhost:8000 (php artisan serve)
 * লাইভ স্মোক টেস্ট: https://agro.tishohagh.com
 *
 * লোকাল রান:   npm run test:e2e
 * লাইভ রান:    npm run test:e2e:live
 */

const isLive = process.env.TEST_ENV === 'live';
const baseURL = isLive
  ? 'https://agro.tishohagh.com'
  : 'http://localhost:8000';

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

  // লোকাল টেস্টে PHP artisan serve অটো স্টার্ট
  webServer: isLive
    ? undefined
    : {
        command: 'php artisan serve --port=8000',
        url: 'http://localhost:8000',
        reuseExistingServer: true,
        timeout: 30000,
      },
});
