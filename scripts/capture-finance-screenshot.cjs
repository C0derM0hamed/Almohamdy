/** One-off: capture finance payment detail screenshot */
const { chromium } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const password = process.env.CLIENT_TEST_PASSWORD || 'ClientTest#2026!';
const baseURL = (process.env.PW_BASE_URL || 'https://c0derm0hamed.duckdns.org').replace(/\/$/, '');
const outDir = path.join(__dirname, '..', 'docs', 'client-review', 'screenshots');
const paymentId = process.env.PAYMENT_ID || '2';

async function ensureSidebarExpanded(page) {
  await page.evaluate(() => {
    try {
      sessionStorage.setItem('hm-sidebar-collapsed', '0');
      sessionStorage.setItem('hm-sidebar-pinned', '1');
    } catch (_) {}
    document.body.classList.remove('hm-sidebar-collapsed');
    ['#sidebar-corporate-communication', '#sidebar-licenses'].forEach((sel) => {
      const g = document.querySelector(sel);
      if (g) {
        g.classList.add('show');
        const t = g.parentElement?.querySelector(':scope > a.nav-link[data-bs-toggle="collapse"]');
        if (t) { t.classList.add('active'); t.setAttribute('aria-expanded', 'true'); }
      }
    });
  });
  await page.waitForTimeout(400);
}

(async () => {
  fs.mkdirSync(outDir, { recursive: true });
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  await page.addInitScript(() => {
    try {
      sessionStorage.setItem('hm-sidebar-collapsed', '0');
      sessionStorage.setItem('hm-sidebar-pinned', '1');
    } catch (_) {}
  });

  await page.goto(`${baseURL}/lang/ar`);
  await page.context().clearCookies();
  await page.goto(`${baseURL}/login`, { waitUntil: 'networkidle' });
  await page.locator('input[name="username"]').fill('lic_finance_bader');
  await page.locator('input[name="password"]').fill(password);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL(/\/otp/, { timeout: 30000 });
  const otp = ((await page.locator('body').textContent()) || '').match(/\b(\d{6})\b/)?.[1];
  if (!otp) throw new Error('OTP not found — enable HM_OTP_DEMO_MODE on server');
  await page.locator('.hm-hope-otp-digit').first().fill(otp);
  await page.waitForURL((u) => !u.pathname.endsWith('/otp'), { timeout: 30000 });

  // Finance payment detail
  await page.goto(`${baseURL}/modules/licenses/finance/${paymentId}`, { waitUntil: 'networkidle' });
  await page.waitForSelector('.lic-panel__title', { timeout: 15000 });
  await page.waitForSelector('#payment_status', { timeout: 15000 });
  await ensureSidebarExpanded(page);
  const detailPath = path.join(outDir, '22-finance-payment-detail.png');
  await page.screenshot({ path: detailPath, fullPage: false });

  // License page — payment requests section (for step 7)
  await page.goto(`${baseURL}/modules/licenses/10`, { waitUntil: 'networkidle' });
  const payments = page.locator('#payments');
  if (await payments.count()) {
    await payments.scrollIntoViewIfNeeded();
    await ensureSidebarExpanded(page);
    await page.screenshot({
      path: path.join(outDir, '07-license-payments-section.png'),
      fullPage: false,
    });
  }

  await browser.close();

  // Verify detail screenshot has content
  const stats = fs.statSync(detailPath);
  if (stats.size < 50000) throw new Error(`Screenshot too small (${stats.size} bytes) — likely empty`);
  console.log(`OK: ${detailPath} (${Math.round(stats.size / 1024)} KB)`);
})();
