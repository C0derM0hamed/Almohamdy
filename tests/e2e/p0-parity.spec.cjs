const { test, expect } = require('@playwright/test');

const roles = ['PW_AUDIT_SUPER_ADMIN', 'PW_AUDIT_PERMISSION_ADMIN', 'PW_AUDIT_BRANCH_A', 'PW_AUDIT_BRANCH_B'];
const branchPages = ['/modules/transferal/outgoing', '/modules/admission-calculator/standard', '/modules/admission-calculator/manual', '/modules/employee-requests/permission', '/modules/employee-requests/duty', '/modules/employee-requests/resignation'];
const adminPages = ['/modules/legal-claims', '/modules/system-administration', '/modules/system-administration/reference/groups', '/modules/system-administration/reference/job-titles'];

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

for (const role of roles) {
  test(`${role} completes P0 navigation smoke`, async ({ page }) => {
    test.skip(!process.env[`${role}_USERNAME`] || !process.env[`${role}_PASSWORD`]);
    await login(page, role);
    for (const path of branchPages) {
      const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
      expect(response.status(), `${role} ${path}`).toBeLessThan(role === 'PW_AUDIT_BRANCH_B' ? 500 : 400);
      expect(await page.locator('body').count()).toBe(1);
    }
    if (role === 'PW_AUDIT_SUPER_ADMIN') {
      for (const path of adminPages) {
        const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
        expect(response.status(), `${role} ${path}`).toBeLessThan(400);
      }
    } else {
      for (const path of adminPages) {
        const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
        expect([401, 403, 404].includes(response.status()), `${role} ${path} should be protected`).toBe(true);
      }
    }
  });
}
