// @ts-check
import { test, expect } from '@playwright/test';

/**
 * Auth Tests — লগিন/লগআউট flow।
 * .env.testing এ TEST_USER_EMAIL ও TEST_USER_PASSWORD সেট করুন।
 * লোকালে: php artisan serve দিয়ে চালান।
 */

const EMAIL = process.env.TEST_USER_EMAIL ?? 'admin@example.com';
const PASSWORD = process.env.TEST_USER_PASSWORD ?? 'password';

test.describe('Authentication', () => {

  test('ভুল পাসওয়ার্ডে লগিন ব্যর্থ হয়', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('networkidle');

    const emailInput = page.locator('input[type="email"], input[name="email"]').first();
    const passInput = page.locator('input[type="password"]').first();

    if (await emailInput.count() === 0) {
      test.skip(); // লগিন পেজ নেই
    }

    await emailInput.fill(EMAIL);
    await passInput.fill('wrong-password-xyz');
    await page.keyboard.press('Enter');

    await page.waitForTimeout(1500);
    // এরর মেসেজ দেখায় বা পেজ সরে না
    const url = page.url();
    const stillOnLogin = !url.includes('/dashboard') && !url.includes('/home');
    expect(stillOnLogin).toBe(true);
  });

  test('সঠিক ক্রেডেনশিয়ালে সফলভাবে লগিন হয়', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('networkidle');

    const emailInput = page.locator('input[type="email"], input[name="email"]').first();
    const passInput = page.locator('input[type="password"]').first();

    if (await emailInput.count() === 0) {
      test.skip();
    }

    await emailInput.fill(EMAIL);
    await passInput.fill(PASSWORD);
    await page.keyboard.press('Enter');

    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Dashboard বা nav দেখায়
    const nav = page.locator('nav, [data-testid="sidebar"], .v-navigation-drawer');
    await expect(nav.first()).toBeVisible({ timeout: 8000 });
  });

});
