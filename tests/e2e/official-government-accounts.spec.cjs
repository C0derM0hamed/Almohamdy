const { test, expect } = require('@playwright/test');
const { execFileSync } = require('child_process');

const password = process.env.CLIENT_TEST_PASSWORD;

async function login(page) {
  test.skip(!password, 'CLIENT_TEST_PASSWORD is required for local Government Accounts verification');
  await page.goto('/login', { waitUntil: 'domcontentloaded' });
  await page.locator('input[name="username"]').fill('CLIENT_TEST_SUPER_ADMIN');
  await page.locator('input[name="password"]').fill(password);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL(/\/otp(?:$|\?)/, { timeout: 30_000 });
  const text = (await page.locator('.hm-auth-alert--success').allTextContents()).join(' ');
  const code = text.match(/\b(\d{6})\b/)?.[1];
  expect(code).toBeTruthy();
  const inputs = page.locator('#otp-form input[name^="n"]');
  for (let index = 0; index < 6; index += 1) await inputs.nth(index).fill(code[index]);
  await page.waitForURL((url) => !url.pathname.endsWith('/otp'), { timeout: 30_000 });
}

async function expectResponsive(page) {
  const result = await page.evaluate(() => ({
    overflow: Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) - document.documentElement.clientWidth,
    direction: document.documentElement.dir,
    nestedMain: document.querySelectorAll('main main, .main-content .main-content').length,
  }));
  expect(result.overflow).toBeLessThanOrEqual(3);
  expect(result.nestedMain).toBe(0);
  expect(['ltr', 'rtl']).toContain(result.direction);
}

function latestRecipientToken(noticeId) {
  const php = `require 'vendor/autoload.php'; $app=require 'bootstrap/app.php'; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); echo App\\Models\\GovAccountNoticeRecipient::where('notice_id',${noticeId})->latest('id')->value('token');`;
  return execFileSync('php', ['-r', php], { cwd: process.cwd(), encoding: 'utf8' }).trim();
}

test('Official Government Accounts desktop/mobile Arabic/English verification', async ({ page }, testInfo) => {
  test.setTimeout(240_000);
  const browserErrors = [];
  page.on('pageerror', (error) => browserErrors.push(`pageerror: ${error.message}`));
  page.on('console', (message) => {
    if (message.type() === 'error') browserErrors.push(`console: ${message.text()}`);
  });
  await login(page);
  await page.goto('/lang/en');
  await page.goto('/dashboard');
  if (testInfo.project.name === 'mobile') await page.locator('main [data-hm-sidebar-toggle]').first().click();
  else await page.locator('#hmAppSidebar').hover();
  await expect(page.locator('a[href="#sidebar-gov-accounts"]')).toBeVisible();
  await expect(page.locator('#sidebar-corporate-communication #sidebar-gov-accounts')).toHaveCount(0);

  for (const [path, text] of [
    ['/modules/gov-accounts/dashboard', 'Official Accounts Dashboard'],
    ['/modules/gov-accounts/accounts', 'Government accounts'],
    ['/modules/gov-accounts/requests', 'Official account requests'],
    ['/modules/gov-accounts/notices', 'Meetings and training notices'],
    ['/modules/gov-accounts/admin', 'Official Accounts Settings'],
    ['/modules/gov-accounts/requests/create', 'New account request'],
    ['/modules/gov-accounts/undertakings', 'Account undertakings'],
    ['/modules/gov-accounts/my-accounts', 'My government accounts'],
    ['/modules/gov-accounts/notifications', 'Official account notifications'],
    ['/modules/gov-accounts/hr/accounts', 'HR official account search'],
    ['/modules/gov-accounts/notices/create', 'New notice'],
    ['/modules/gov-accounts/admin/authorities', 'Authorities'],
    ['/modules/gov-accounts/admin/authorities/create', 'Authorities'],
    ['/modules/gov-accounts/admin/services', 'Services'],
    ['/modules/gov-accounts/admin/services/create', 'Services'],
    ['/modules/gov-accounts/admin/roles', 'External account roles'],
    ['/modules/gov-accounts/admin/roles/create', 'External account roles'],
    ['/modules/gov-accounts/admin/department-heads', 'Department heads'],
    ['/modules/gov-accounts/admin/department-heads/create', 'Department heads'],
  ]) {
    const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
    expect(response?.status(), path).toBe(200);
    await expect(page.locator('body')).toContainText(text);
    await expectResponsive(page);
  }

  await page.goto('/modules/gov-accounts/requests/create');
  await expect(page.locator('input[readonly]')).not.toHaveValue('');
  await expect(page.locator('select[name="department_id"] option').nth(1)).not.toHaveText('—');

  const title = `Playwright official-account training ${Date.now()} ${testInfo.project.name}`;
  await page.goto('/modules/gov-accounts/notices/create');
  await page.locator('input[name="title"]').fill(title);
  await page.locator('select[name="authority_id"]').selectOption({ index: 1 });
  await page.locator('textarea[name="description"]').fill('Tracked invitation browser verification.');
  await page.locator('input[name="event_date"]').fill('2026-12-15');
  await page.locator('input[name="event_time"]').fill('10:30');
  await page.locator('select[name="attendance_method"]').selectOption('online');
  await page.locator('input[name="meeting_url"]').fill('https://meet.example.test/playwright');
  await page.locator('select[name="targeting_mode"]').selectOption('users');
  await page.locator('input[name="user_ids[]"]').first().check();
  await page.locator('input[name="attachments[]"]').setInputFiles({ name: 'training-agenda.pdf', mimeType: 'application/pdf', buffer: Buffer.from('%PDF-1.4 browser attachment verification') });
  await page.locator('main form[action$="/notices"] button[type="submit"]').click();
  await page.waitForURL(/\/modules\/gov-accounts\/notices\/\d+$/);
  const noticeId = Number(page.url().match(/notices\/(\d+)$/)[1]);
  await expect(page.locator('body')).toContainText(title);
  await expect(page.locator('body')).toContainText('training-agenda.pdf');
  const attachmentResponse = await page.request.get(await page.locator('a', { hasText: 'training-agenda.pdf' }).getAttribute('href'));
  expect(attachmentResponse.status()).toBe(200);
  expect(attachmentResponse.headers()['content-disposition']).toContain('attachment');
  await page.locator(`form[action$="/notices/${noticeId}/send"] button[type="submit"]`).click();
  await expect(page.locator('body')).toContainText(/Not viewed|Sent/);

  const token = latestRecipientToken(noticeId);
  expect(token).toMatch(/^[a-f0-9]{64}$/);
  await page.goto(`/gov-account-notices/view/${token}`);
  await expect(page.locator('body')).toContainText(title);
  await expect(page.locator('body')).toContainText('Open meeting link');
  await expect(page.locator('body')).not.toContainText('Recipient status');
  await expectResponsive(page);
  await page.goto(`/modules/gov-accounts/notices/${noticeId}`);
  await expect(page.locator('body')).toContainText('Viewed');

  await page.goto('/lang/ar');
  await page.goto('/modules/gov-accounts/dashboard');
  await expect(page.locator('html')).toHaveAttribute('lang', 'ar');
  await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
  await expect(page.locator('body')).toContainText('لوحة الحسابات الرسمية');
  await expectResponsive(page);

  await page.goto('/lang/en');
  const exportResponse = await page.request.get('/modules/gov-accounts/export/csv?report=notices');
  expect(exportResponse.status()).toBe(200);
  expect(exportResponse.headers()['content-disposition']).toContain('attachment');
  expect(browserErrors).toEqual([]);
});
