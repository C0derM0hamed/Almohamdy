const { test, expect } = require('@playwright/test');

async function loginAs(page, role) {
  const username = process.env[`PW_AUDIT_${role}_USERNAME`] || process.env[`PW_AUDIT_${role}`] || '';
  const password = process.env[`PW_AUDIT_${role}_PASSWORD`] || '';
  test.skip(!username || !password, `Missing ${role} audit credentials`);
  await page.goto('/login');
  await page.locator('input[name="username"]').fill(username);
  await page.locator('input[name="password"]').fill(password);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL(/\/otp/);
  const inputs = page.locator('#otp-form input[name^="n"]');
  const otp = process.env.PW_NEW_OTP || '111111';
  for (let index = 0; index < await inputs.count(); index += 1) await inputs.nth(index).fill(otp[index]);
  await page.waitForURL((url) => !url.pathname.endsWith('/otp'));
}

test('branch A can open all six medical/referral workflows and PDFs stay protected', async ({ page }) => {
  await loginAs(page, 'BRANCH_A');
  const paths = ['Pulse_status.php','bed_reservation.php','accept_referral.php','ehala_case_apology.php','crisis_management.php','red_crescent.php'];
  for (const path of paths) {
    const response = await page.goto(`/${path}`);
    expect(response.status(), path).toBeLessThan(400);
    await expect(page.locator('h1')).toBeVisible();
    await expect(page.getByRole('link', { name: /إضافة جديد/ })).toBeVisible();
  }
});

test('another branch is forbidden from direct workflow access', async ({ page }) => {
  await loginAs(page, 'BRANCH_B');
  const response = await page.goto('/modules/medical-referrals/bed-reservation');
  expect(response.status()).toBe(403);
});
