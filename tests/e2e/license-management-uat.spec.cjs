const { test, expect } = require('@playwright/test');
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const password = process.env.CLIENT_TEST_PASSWORD;
const stamp = Date.now();
const authority = `UAT Authority ${stamp}`;
const type = `UAT Type ${stamp}`;
const authorityAr = `جهة اختبار ${stamp}`;
const typeAr = `نوع اختبار ${stamp}`;
const stage = `UAT Stage ${stamp}`;
const stageAr = `مرحلة اختبار ${stamp}`;
const title = `UAT License ${stamp}`;
const number = `${stamp}`;
const pdf = { name: 'uat-proof.pdf', mimeType: 'application/pdf', buffer: fs.readFileSync(path.join(__dirname, '..', 'fixtures', 'uat-proof.pdf')) };

function ensureDepartmentFixtures() {
  const php = `require 'vendor/autoload.php'; $app=require 'bootstrap/app.php'; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); $admin=App\\Models\\User::where('hr_username','CLIENT_TEST_SUPER_ADMIN')->firstOrFail(); $responsible=App\\Models\\User::where('hr_username','CLIENT_TEST_BRANCH_A')->firstOrFail(); $company=(int)$admin->companies_groups_id; Illuminate\\Support\\Facades\\DB::table('branches')->updateOrInsert(['id'=>(int)$responsible->branch_id],['companies_groups_id'=>$company,'name_ar'=>'الطوارئ','name_en'=>'Emergency']); foreach([['مختبر UAT','UAT Laboratory'],['شؤون قانونية UAT','UAT Legal Affairs']] as $names){ Illuminate\\Support\\Facades\\DB::table('branches')->updateOrInsert(['companies_groups_id'=>$company,'name_en'=>$names[1]],['name_ar'=>$names[0]]); } echo Illuminate\\Support\\Facades\\DB::table('branches')->where('companies_groups_id',$company)->orderByRaw('id = ? desc',[(int)$responsible->branch_id])->orderBy('id')->pluck('id')->take(3)->implode(',');`;
  return execFileSync('php', ['-r', php], { cwd: process.cwd(), encoding: 'utf8' }).trim().split(',').filter(Boolean);
}

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
  await page.goto('/lang/en');
}

async function logout(page) {
  await Promise.all([
    page.waitForURL((url) => url.pathname === '/', { timeout: 30_000 }),
    page.locator('form[data-hm-logout-form]').evaluate((form) => form.submit()),
  ]);
}

async function expectResponsive(page) {
  const metrics = await page.evaluate(() => ({
    overflow: Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) - document.documentElement.clientWidth,
    direction: document.documentElement.dir,
  }));
  expect(metrics.overflow).toBeLessThanOrEqual(3);
  expect(['ltr', 'rtl']).toContain(metrics.direction);
}

test('License Management final authenticated UAT workflow', async ({ page }, testInfo) => {
  test.setTimeout(300_000);
  test.skip(!password, 'CLIENT_TEST_PASSWORD is required for local UAT');
  const departmentIds = ensureDepartmentFixtures();
  expect(departmentIds).toHaveLength(3);
  const browserErrors = [];
  page.on('pageerror', (error) => browserErrors.push(`pageerror: ${error.message}`));
  page.on('console', (message) => {
    if (message.type() === 'error') browserErrors.push(`console: ${message.text()}`);
  });

  await login(page, 'CLIENT_TEST_SUPER_ADMIN');
  await page.goto('/dashboard');
  if (testInfo.project.name === 'mobile') await page.locator('main [data-hm-sidebar-toggle]').first().click();
  else await page.locator('#hmAppSidebar').hover();
  await page.locator('a[href="#sidebar-licenses"]').click();
  await expect(page.locator('#sidebar-menu a[href$="/modules/licenses"]')).toBeVisible();
  await expect(page.locator('#sidebar-corporate-communication #sidebar-licenses')).toHaveCount(0);
  await page.goto('/modules/licenses/dashboard');
  await expect(page.locator('body')).toContainText(/License dashboard|لوحة التراخيص/i);
  await expectResponsive(page);
  if (testInfo.project.name === 'desktop') {
    const cards = await page.locator('.lic-stat-grid--compact .lic-stat').evaluateAll((nodes) => nodes.map((node) => ({ top: Math.round(node.getBoundingClientRect().top), height: node.getBoundingClientRect().height })));
    expect(cards).toHaveLength(5);
    expect(new Set(cards.map((card) => card.top)).size).toBe(1);
    expect(Math.max(...cards.map((card) => card.height))).toBeLessThanOrEqual(96);
  }

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
  await page.locator('#authority_id').selectOption({ label: authority });
  await page.locator('#type_id').selectOption({ label: type });
  await page.locator('#title').fill(title);
  await page.locator('#license_number').fill(number);
  for (const departmentId of departmentIds) {
    await page.locator(`input[name="department_ids[]"][value="${departmentId}"]`).check();
  }
  await page.locator('#responsible_user_id').selectOption('7');
  await page.locator('#issue_date').fill('2026-01-01');
  await page.locator('#expiry_date').fill('2026-11-28');
  await page.locator('#renewal_stage_id').selectOption({ label: stage });
  await page.locator('#attachments').setInputFiles(pdf);
  await page.locator('main form button[type="submit"]').click();
  await page.waitForURL(/\/modules\/licenses\/\d+$/);
  const licenseUrl = page.url();
  const licenseId = licenseUrl.match(/licenses\/(\d+)$/)[1];
  await expect(page.locator('body')).toContainText(title);
  await expectResponsive(page);
  await expect(page.locator('.lic-chip-list--compact .lic-chip:not(.lic-chip--more)')).toHaveCount(1);
  await page.locator('.lic-chip--more').click();
  await expect(page.locator('#licenseDepartmentsModal')).toBeVisible();
  await expect(page.locator('#licenseDepartmentsModal [data-lic-departments-list] .lic-chip')).toHaveCount(2);
  await page.locator('#licenseDepartmentsModal [data-bs-dismiss="modal"]').click();

  await page.goto('/modules/licenses');
  const licenseRow = page.locator('tbody tr', { hasText: number }).first();
  await expect(licenseRow.locator('.lic-chip:not(.lic-chip--more)')).toHaveCount(1);
  await expect(licenseRow.locator('.lic-chip--more')).toContainText('+2');
  await licenseRow.getByRole('button', { name: /view|عرض/i }).click();
  await expect(page.locator('#licenseQuickViewModal')).toBeVisible();
  await expect(page.locator('#licenseQuickViewModal')).toContainText(title);
  await expect(page.locator('#licenseQuickViewModal [data-license-preview-open]')).toHaveAttribute('href', new RegExp(`/modules/licenses/${licenseId}$`));
  await page.locator('#licenseQuickViewModal [data-license-preview-open]').click();
  await page.waitForURL(new RegExp(`/modules/licenses/${licenseId}$`));

  await page.getByRole('button', { name: /reassign|إعادة إسناد/i }).click();
  await page.locator('#new_responsible_user_id').selectOption('8');
  await page.locator('form[action$="/assign"] button[type="submit"]').click();
  await page.waitForURL(new RegExp(`/modules/licenses/${licenseId}$`));
  await expect(page.locator('#responsibility')).toContainText(/Pending/i);
  await logout(page);

  await login(page, 'CLIENT_TEST_BRANCH_A');
  await page.goto(`/modules/licenses/${licenseId}`);
  await page.waitForURL(new RegExp(`/modules/licenses/${licenseId}/undertaking$`));
  await page.locator('#undertaking_confirm').check();
  await page.locator('form:has(#undertaking_confirm) button[type="submit"]').click();
  await page.waitForURL(new RegExp(`/modules/licenses/${licenseId}$`));
  await expect(page.locator('#responsibility')).toContainText(/Accepted/i);

  await page.getByRole('button', { name: /add.*comment|إضافة ملاحظة/i }).click();
  await page.locator('#comment_body').fill('UAT responsible comment');
  await page.locator('form[action$="/comments"] button[type="submit"]').click();
  await expect(page.locator('#comments')).toContainText('UAT responsible comment');
  await page.getByRole('button', { name: /upload attachment|رفع مرفق/i }).click();
  await page.locator('#attachment_file').setInputFiles(pdf);
  await page.locator('#attachment_description').fill('UAT private attachment');
  await page.locator('form[action$="/attachments"] button[type="submit"]').click();
  await expect(page.locator('#attachments')).toContainText('uat-proof.pdf');

  await page.getByRole('button', { name: /start renewal|بدء التجديد/i }).click();
  await page.locator('#renewal_notes').fill('UAT renewal start');
  await page.locator('form[action$="/renewal/start"] button[type="submit"]').click();
  await page.getByRole('button', { name: /update stage|تحديث المرحلة/i }).click();
  await page.locator('#renewal_stage_id').selectOption({ label: stage });
  await page.locator('form[action$="/stage"] button[type="submit"]').click();
  await page.getByRole('button', { name: /complete renewal|إكمال التجديد/i }).click();
  await page.locator('#new_expiry_date').fill('2027-11-28');
  await page.locator('#new_license_copy').setInputFiles(pdf);
  await page.locator('form[action$="/renewal/complete"] button[type="submit"]').click();
  await expect(page.locator('#history')).toContainText('2027-11-28');

  await page.getByRole('button', { name: /create payment request|إنشاء طلب سداد/i }).click();
  await page.locator('#payment_amount').fill('1250');
  await page.locator('#bank_name').fill('UAT Bank');
  await page.locator('#invoice_number').fill(`INV-${stamp}`);
  await page.locator('#payment_attachment').setInputFiles(pdf);
  await page.locator('form[action$="/payment-requests"] button[type="submit"]').click();
  await expect(page.locator('#payments')).toContainText('1,250.00');
  const paymentRow = page.locator('#payments tbody tr', { hasText: '1,250.00' }).first();
  const paymentId = (await paymentRow.locator('td').first().innerText()).replace(/\D/g, '');
  const financeHref = `/modules/licenses/finance/${paymentId}`;
  await logout(page);

  await login(page, 'CLIENT_TEST_PERM_ADMIN');
  await page.goto(financeHref);
  await expect(page.locator('.lic-action-hub .lic-action-button')).toHaveCount(4);
  await page.getByRole('button', { name: /update payment status|تحديث حالة السداد/i }).click();
  await expect(page.locator('#financeOperationStatus')).toBeVisible();
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
    const response = await page.request.get(`/modules/licenses/export/${suffix}`);
    expect(response.status()).toBe(200);
    expect(response.headers()['content-disposition']).toMatch(/attachment/);
  }
  const licensePdf = await page.request.get(`/modules/licenses/${licenseId}/pdf`);
  expect(licensePdf.status()).toBe(200);
  expect(licensePdf.headers()['content-type']).toContain('application/pdf');
  await page.request.get('/lang/ar', { maxRedirects: 0 });
  await page.goto('/modules/licenses/dashboard');
  await expect(page.locator('html')).toHaveAttribute('lang', /ar/);
  await page.request.get('/lang/en', { maxRedirects: 0 });
  await page.goto('/modules/licenses/dashboard');
  await expect(page.locator('body')).toContainText(/License dashboard/i);
  await expectResponsive(page);
  expect(browserErrors).toEqual([]);

  await testInfo.attach('license-uat.json', { body: JSON.stringify({ licenseId, licenseUrl, financeHref }), contentType: 'application/json' });
});
