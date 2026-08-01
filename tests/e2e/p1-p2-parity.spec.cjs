const { test, expect } = require('@playwright/test');

const publicPages = ['/modules/publications', '/modules/publications/create', '/modules/settings'];
const adminPages = [
  '/modules/system-administration/reference/complaint-statuses',
  '/modules/system-administration/reference/complaint-closing-reasons',
  '/modules/system-administration/reference/complaint-letter-receivers',
  '/modules/system-administration/reference/post-types',
  '/modules/system-administration/reference/companies',
  '/modules/system-administration/reference/branches',
  '/modules/system-administration/reference/departments',
  '/modules/system-administration/reference/service-types',
  '/modules/system-administration/reference/medical-terminology',
  '/modules/system-administration/reference/service-codes',
];

async function login(page, role) {
  await page.goto('/login');
  await page.locator('input[name="username"]').fill(process.env[`${role}_USERNAME`]);
  await page.locator('input[name="password"]').fill(process.env[`${role}_PASSWORD`]);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL(/\/otp(?:$|\?)/);
  const inputs = page.locator('#otp-form input[name^="n"]');
  const otp = process.env.PW_NEW_OTP || '111111';
  for (let i = 0; i < await inputs.count(); i += 1) await inputs.nth(i).fill(otp[i]);
  await page.waitForURL((url) => !url.pathname.endsWith('/otp'));
}

for (const role of ['PW_AUDIT_SUPER_ADMIN', 'PW_AUDIT_BRANCH_A']) {
  test(`${role} verifies P1/P2 reachable pages`, async ({ page }) => {
    test.skip(!process.env[`${role}_USERNAME`] || !process.env[`${role}_PASSWORD`]);
    await login(page, role);
    for (const path of publicPages) {
      const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
      expect(response.status(), `${role} ${path}`).toBeLessThan(400);
    }
    for (const path of adminPages) {
      const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
      expect(response.status(), `${role} ${path}`).toBeLessThan(role === 'PW_AUDIT_SUPER_ADMIN' ? 400 : 500);
      if (role !== 'PW_AUDIT_SUPER_ADMIN') expect([401, 403, 404].includes(response.status())).toBe(true);
    }
  });
}
