const { test, expect } = require('@playwright/test');

async function login(page, role) {
  await page.goto('/');
  await page.locator('input[name="username"]').fill(process.env[`${role}_USERNAME`] || process.env[role]);
  await page.locator('input[name="password"]').fill(process.env[`${role}_PASSWORD`]);
  await Promise.all([page.waitForURL(/\/otp$/), page.locator('form').filter({ has: page.locator('input[name="username"]') }).locator('button[type="submit"]').click()]);
  const otp = process.env.PW_NEW_OTP || '111111';
  const inputs = page.locator('#otp-form input[name^="n"]');
  for (let i = 0; i < await inputs.count(); i += 1) await inputs.nth(i).fill(otp[i]);
  await page.waitForURL(url => !url.pathname.endsWith('/otp'));
}

test('branch 1 operational role can open every emergency/reception legacy page and representative PDF', async ({ page }) => {
  await login(page, 'PW_AUDIT_BRANCH_A');
  const paths = ['/emergency_cases_process.php', '/emergency_reception_mechanism.php', '/health_service_purchase_form.php', '/receiving_the_corpse.php', '/claiming_against_others.php', '/receive_unidentified_case.php', '/escape_report_form.php', '/incident_report_form.php'];
  for (const path of paths) {
    const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
    expect(response.status(), path).toBeLessThan(400);
    await expect(page.locator('h1').first(), path).toBeVisible();
  }
  await page.goto('/modules/emergency-reception/incident');
  const href = await page.locator('a[href]').evaluateAll(links => links.map(link => link.href).find(value => /\/modules\/emergency-reception\/incident\/\d+$/.test(new URL(value).pathname)) || null);
  if (href) {
    await page.goto(href);
    const pdf = page.locator('a[href$="/pdf"]');
    await expect(pdf).toBeVisible();
    const response = await page.request.get(await pdf.getAttribute('href'));
    expect(response.status()).toBe(200);
    expect(response.headers()['content-type']).toContain('application/pdf');
  }
});

test('branch/company scope denies branch 2 and super-admin shells', async ({ browser }) => {
  for (const role of ['PW_AUDIT_BRANCH_B', 'PW_AUDIT_SUPER_ADMIN']) {
    const context = await browser.newContext();
    const page = await context.newPage();
    await login(page, role);
    const response = await page.goto('/modules/emergency-reception/incident');
    expect(response.status(), role).toBe(403);
    await context.close();
  }
});
