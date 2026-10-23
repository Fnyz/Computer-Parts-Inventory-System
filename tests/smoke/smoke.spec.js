// Read-only checks: sign in with the smoke-test account and open the pages people use every day.
// The account should be an Employee, so the test can't change anything important.
const { test, expect } = require('@playwright/test');

const user = process.env.SMOKE_USER;
const pass = process.env.SMOKE_PASS;

test('health check reports ok', async ({ request }) => {
  const res = await request.get('/health');
  expect(res.status()).toBe(200);
  expect((await res.json()).database).toBe('ok');
});

test('sign-in page loads', async ({ page }) => {
  await page.goto('/login');
  // A brand-new site with no accounts redirects to the first-run Set up screen instead
  if (page.url().endsWith('/setup')) {
    await expect(page.getByRole('button', { name: /create admin account/i })).toBeVisible();
    return;
  }
  await expect(page.getByRole('button', { name: /sign in/i })).toBeVisible();
});

test.describe('signed in', () => {
  test.skip(!user || !pass, 'Set SMOKE_USER and SMOKE_PASS to run the signed-in checks');

  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Username').fill(user);
    await page.getByLabel('Password').fill(pass);
    await page.getByRole('button', { name: /sign in/i }).click();
    await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
  });

  for (const [path, heading] of [['/', 'Dashboard'], ['/products', 'Products'], ['/pos', 'Current sale'], ['/sales', 'Sales']]) {
    test(`${path} opens without errors`, async ({ page }) => {
      const errors = [];
      page.on('pageerror', (err) => errors.push(err.message));
      const res = await page.goto(path);
      expect(res.status()).toBeLessThan(400);
      await expect(page.getByRole('heading', { name: heading })).toBeVisible();
      await expect(page.getByText('System error')).toHaveCount(0);
      expect(errors).toEqual([]);
    });
  }
});
