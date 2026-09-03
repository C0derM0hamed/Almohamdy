const { test, expect } = require('@playwright/test');

const password = process.env.CLIENT_TEST_PASSWORD;
const stamp = Date.now();
const authority = `UAT Authority ${stamp}`;
const type = `UAT Type ${stamp}`;
const authorityAr = `جهة اختبار ${stamp}`;
const typeAr = `نوع اختبار ${stamp}`;
const stage = `UAT Stage ${stamp}`;
const stageAr = `مرحلة اختبار ${stamp}`;
const title = `UAT License ${stamp}`;
const number = `UAT-${stamp}`;
const pdf = { name: 'uat-proof.pdf', mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4 UAT proof') };

async function login(page, username) {
  await page.goto('/login', { waitUntil: 'domcontentloaded' });
  await page.locator('input[name="username"]').fill(username);
  await page.locator('input[name="password"]').fill(password);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL(/\/otp(?:$|\?)/, { timeout: 30_000 });
  const text = (await page.locator('.hm-auth-alert--success').allTextContents()).join(' ');
  const code = text.match(/\b(\d{6})\b/)?.[1];
  expect(code).toBeTruthy();
  const inputs = page.locator('#otp-form input[name^="n"]');
  for (let i = 0; i < 6; i += 1) await inputs.nth(i).fill(code[i]);
  await page.waitForURL((url) => !url.pathname.endsWith('/otp'), { timeout: 30_000 });
}

async function logout(page) {
  await page.goto('/logout');
  await page.waitForURL(/\/login/, { timeout: 30_000 });
}

test('License Management final authenticated UAT workflow', async ({ page }, testInfo) => {
  test.setTimeout(300_000);
  test.skip(!password, 'CLIENT_TEST_PASSWORD is required for local UAT');

  await login(page, 'CLIENT_TEST_SUPER_ADMIN');
  await page.goto('/dashboard');
  await page.locator('#hmAppSidebar').hover();
  await page.locator('a[href="#sidebar-corporate-communication"]').click();
  await page.locator('a[href="#sidebar-licenses"]').click();
  await expect(page.locator('#sidebar-menu a[href$="/modules/licenses"]')).toBeVisible();
  await page.goto('/modules/licenses/dashboard');
  await expect(page.locator('body')).toContainText(/License dashboard|لوحة التراخيص/i);

  for (const [resource, en, ar] of [['authorities', authority, authorityAr], ['types', type, typeAr], ['stages', stage, stageAr]]) {
    await page.goto(`/modules/licenses/admin/${resource}/create`);
    await page.locator('#name_en').fill(en);
    await page.locator('#name_ar').fill(ar);
    await page.locator('#ranking').fill('1');
    await page.locator('main form button[type="submit"]').click();
    await page.waitForURL(new RegExp(`/modules/licenses/admin/${resource}`));
    await expect(page.locator('body')).toContainText(en);
  }

  await page.goto('/modules/licenses/admin/escalation-groups/create');
  await page.locator('#name').fill(`UAT Escalation ${stamp}`);
  await page.locator('main form button[type="submit"]').click();
  await page.waitForURL(/\/escalation-groups\/\d+\/edit/);
  await page.locator('#member_user_id').selectOption('8');
  await page.locator('form[action*="/members"] button[type="submit"]').click();
  await expect(page.locator('body')).toContainText(/CLIENT_TEST_BRANCH_A/);

  await page.goto('/modules/licenses/create');
  await page.locator('#authority_id').selectOption({ label: authorityAr });
  await page.locator('#type_id').selectOption({ label: typeAr });
  await page.locator('#title').fill(title);
  await page.locator('#license_number').fill(number);
  await page.locator('input[name="branch_ids[]"]').first().check();
  await page.locator('#responsible_user_id').selectOption('7');
  await page.locator('#issue_date').fill('2026-01-01');
  await page.locator('#expiry_date').fill('2026-11-28');
  await page.locator('#renewal_stage_id').selectOption({ label: stageAr });
  await page.locator('#attachments').setInputFiles(pdf);
  await page.locator('main form button[type="submit"]').click();
  await page.waitForURL(/\/modules\/licenses\/\d+$/);
  const licenseUrl = page.url();
  const licenseId = licenseUrl.match(/licenses\/(\d+)$/)[1];
  await expect(page.locator('body')).toContainText(title);

  await page.locator('#new_responsible_user_id').selectOption('8');
  await page.locator('form[action$="/assign"] button[type="submit"]').click();
  await page.waitForURL(new RegExp(`/modules/licenses/${licenseId}$`));
  await expect(page.locator('#responsibility')).toContainText(/Pending/i);
  await logout(page);

  await login(page, 'CLIENT_TEST_BRANCH_A');
  await page.goto(`/modules/licenses/${licenseId}`);
  await page.waitForURL(new RegExp(`/modules/licenses/${licenseId}/undertaking$`));
  await page.locator('#undertaking_confirm').check();
  await page.locator('main form button[type="submit"]').click();
  await page.waitForURL(new RegExp(`/modules/licenses/${licenseId}$`));
  await expect(page.locator('#responsibility')).toContainText(/Accepted/i);

  await page.locator('#comment_body').fill('UAT responsible comment');
  await page.locator('form[action$="/comments"] button[type="submit"]').click();
  await expect(page.locator('#comments')).toContainText('UAT responsible comment');
  await page.locator('#attachment_file').setInputFiles(pdf);
  await page.locator('#attachment_description').fill('UAT private attachment');
  await page.locator('form[action$="/attachments"] button[type="submit"]').click();
  await expect(page.locator('#attachments')).toContainText('uat-proof.pdf');

  await page.locator('#renewal_notes').fill('UAT renewal start');
  await page.locator('form[action$="/renewal/start"] button[type="submit"]').click();
  await page.locator('#renewal_stage_id').selectOption({ label: stageAr });
  await page.locator('form[action$="/stage"] button[type="submit"]').click();
  await page.locator('#new_expiry_date').fill('2027-11-28');
  await page.locator('#new_license_copy').setInputFiles(pdf);
  await page.locator('form[action$="/renewal/complete"] button[type="submit"]').click();
  await expect(page.locator('#history')).toContainText('2027-11-28');

  await page.locator('#payment_amount').fill('1250');
  await page.locator('#bank_name').fill('UAT Bank');
  await page.locator('#invoice_number').fill(`INV-${stamp}`);
  await page.locator('#payment_attachment').setInputFiles(pdf);
  await page.locator('form[action$="/payment-requests"] button[type="submit"]').click();
  await expect(page.locator('#payments')).toContainText('1,250.00');
  const financeHref = await page.locator('#payments a[href*="/finance/"]').getAttribute('href');
  await logout(page);

  await login(page, 'CLIENT_TEST_PERM_ADMIN');
  await page.goto(financeHref);
  await page.locator('#payment_status').selectOption('paid');
  await page.locator('form[data-payment-status-form] button[type="submit"]').click();
  await expect(page.locator('body')).toContainText(/proof.*required|required.*proof/i);
  await page.locator('#payment_proof').setInputFiles(pdf);
  await page.locator('form[data-payment-status-form] button[type="submit"]').click();
  await expect(page.locator('body')).toContainText(/Paid/i);
  await logout(page);

  await login(page, 'CLIENT_TEST_BRANCH_A');
  await page.goto('/modules/licenses/notifications');
  await expect(page.locator('body')).toContainText(/Notification/i);
  const readButton = page.locator('form[action*="/notifications/"] button[type="submit"]').first();
  if (await readButton.count()) await readButton.click();
  await page.goto(`/modules/licenses/${licenseId}`);
  await expect(page.locator('#timeline .lic-timeline__item')).toHaveCount(await page.locator('#timeline .lic-timeline__item').count());
  await expect(page.locator('#timeline')).toContainText(/renewal|payment|undertaking/i);
  await logout(page);

  await login(page, 'CLIENT_TEST_SUPER_ADMIN');
  for (const suffix of ['xls', 'pdf']) {
    const response = await page.goto(`/modules/licenses/export/${suffix}`);
    expect(response.status()).toBe(200);
    expect(response.headers()['content-disposition']).toMatch(/attachment/);
  }
  await page.goto(`/modules/licenses/${licenseId}/pdf`);
  await expect(page.locator('body')).toContainText(title);
  await page.goto('/lang/ar');
  await page.goto('/modules/licenses/dashboard');
  await expect(page.locator('html')).toHaveAttribute('lang', /ar/);
  await page.goto('/lang/en');
  await page.goto('/modules/licenses/dashboard');
  await expect(page.locator('body')).toContainText(/License dashboard/i);

  await testInfo.attach('license-uat.json', { body: JSON.stringify({ licenseId, licenseUrl, financeHref }), contentType: 'application/json' });
});
