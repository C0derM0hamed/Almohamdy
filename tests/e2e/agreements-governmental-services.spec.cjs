const { test, expect } = require('@playwright/test');

async function loginAs(page, role) {
  const username = process.env[`PW_AUDIT_${role}_USERNAME`] || '';
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

test('branch A opens the legacy agreement and governmental-services entry points with real data', async ({ page }) => {
  await loginAs(page, 'BRANCH_A');
  for (const path of ['/medical_services_agreement.php', '/governmental_services.php']) {
    const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
    expect(response.status(), path).toBeLessThan(400);
    await expect(page.locator('main h1').last()).toBeVisible();
    await expect(page.locator('table tbody tr').first()).toBeVisible();
  }
  const pdf = page.locator('a[href$="/pdf"]').first();
  await expect(pdf).toBeVisible();
  const pdfResponse = await page.request.get(await pdf.getAttribute('href'));
  expect(pdfResponse.status()).toBe(200);
  expect(pdfResponse.headers()['content-type']).toContain('application/pdf');
});

test('legacy privilege and governmental branch restrictions are enforced server-side', async ({ page }) => {
  await loginAs(page, 'BRANCH_A');
  expect((await page.goto('/medical_services_agreement_sadq.php')).status()).toBe(403);
  await page.context().clearCookies();
  await loginAs(page, 'BRANCH_B');
  expect((await page.goto('/governmental_services.php')).status()).toBe(403);
});
