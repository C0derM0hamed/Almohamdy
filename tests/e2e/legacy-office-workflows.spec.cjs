const { test, expect } = require('@playwright/test');

async function login(page) {
  const username = process.env.PW_AUDIT_BRANCH_A_USERNAME || process.env.PW_AUDIT_BRANCH_A;
  const password = process.env.PW_AUDIT_BRANCH_A_PASSWORD;
  test.skip(!username || !password, 'Branch A audit credentials are required');
  await page.goto('/login');
  await page.locator('input[name="username"]').fill(username);
  await page.locator('input[name="password"]').fill(password);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL(/\/otp/);
  const otp = process.env.PW_NEW_OTP || '111111';
  const digits = page.locator('#otp-form input[name^="n"]');
  for (let i = 0; i < await digits.count(); i += 1) await digits.nth(i).fill(otp[i]);
  await page.waitForURL(url => !url.pathname.endsWith('/otp'));
}

test('legacy office pages render their functional controls without server errors', async ({ page }) => {
  const serverErrors = [];
  page.on('response', response => { if (response.status() >= 500) serverErrors.push(`${response.status()} ${response.url()}`); });
  await login(page);
  const pages = [
    ['/holidays_inquiry.php', 'استفسار الإجازات', 'input[name="patient_name"]'],
    ['/medical_reports_approval.php', 'اعتماد التقارير الطبية', 'select[name="status"]'],
    ['/memo.php', 'المذكرات', 'select[name="memo_types_id"]'],
    ['/memo_me.php', 'المذكرات الواردة', 'table'],
    ['/service_coverage_memo.php', 'تغطية الخدمات الطبية', 'input[name="patient_mobile"]'],
    ['/signature.php', 'لوحة التوقيع', '#signature-pad'],
  ];
  for (const [path, title, control] of pages) {
    await page.goto(path);
    await expect(page.locator('body')).toContainText(title);
    await expect(page.locator(control).first()).toBeAttached();
  }
  expect(serverErrors).toEqual([]);
});

test('legacy branch restrictions are enforced server-side', async ({ page }) => {
  await login(page);
  const response = await page.goto('/modules/legacy-office/holidays/999999/timeline');
  expect(response.status()).toBe(404);
});
