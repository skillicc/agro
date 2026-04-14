// @ts-check
import { test, expect } from '@playwright/test';

/**
 * Smoke Tests — অ্যাপের মূল পেজগুলো লোড হচ্ছে কিনা চেক করে।
 * লোকাল ও লাইভ দুটোতেই চলে।
 */

test.describe('App Smoke Tests', () => {

  test('লগিন পেজ লোড হয়', async ({ page }) => {
    await page.goto('/');
    // Vue SPA লোড হওয়া পর্যন্ত অপেক্ষা
    await page.waitForLoadState('networkidle');
    // লগিন ফর্ম বা dashboard দেখা যাচ্ছে
    const hasLogin = await page.locator('input[type="password"], input[type="email"], [data-testid="login"]').count();
    const hasDashboard = await page.locator('[data-testid="dashboard"], .dashboard, nav').count();
    expect(hasLogin + hasDashboard).toBeGreaterThan(0);
  });

  test('API health check — /api/user 401 দেয় (unauthenticated)', async ({ request }) => {
    const response = await request.get('/api/user');
    expect([401, 302]).toContain(response.status());
  });

});
