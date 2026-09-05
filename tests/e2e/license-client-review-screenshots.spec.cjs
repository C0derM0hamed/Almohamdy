const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');

const password = process.env.CLIENT_TEST_PASSWORD;
const screenshotDir = path.join(__dirname, '..', '..', 'docs', 'client-review', 'screenshots');

const REVIEW_LICENSE_ID = process.env.REVIEW_LICENSE_ID || '7';
const PENDING_UNDERTAKING_ID = process.env.PENDING_UNDERTAKING_ID || '13';

const ACCOUNTS = {
  ccSuper: 'lic_cc_super_mohamed',
  responsible: 'lic_responsible_mohamed',
  finance: 'lic_finance_bader',
  superAdmin: 'lic_super_bader',
};

async function login(page, username) {
  await page.context().clearCookies();
  await page.goto('/login', { waitUntil: 'networkidle' });
  await page.locator('input[name="username"]').fill(username);
  await page.locator('input[name="password"]').fill(password);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL(/\/otp(?:$|\?)/, { timeout: 30_000 });
  const text = (await page.locator('body').textContent()) || '';
  const code = text.match(/\b(\d{6})\b/)?.[1];
  if (!code) {
    throw new Error(`OTP code not found on page for ${username}. Page text: ${text.slice(0, 300)}`);
  }
  await page.locator('.hm-hope-otp-digit').first().fill(code);
  await page.waitForURL((url) => !url.pathname.endsWith('/otp'), { timeout: 30_000 });
}

async function logout(page) {
  await page.context().clearCookies();
  await page.goto('/login', { waitUntil: 'networkidle' });
}

/** Pin and expand the sidebar; open License Management nav groups. */
async function ensureSidebarExpanded(page) {
  await page.evaluate(() => {
    try {
      sessionStorage.setItem('hm-sidebar-collapsed', '0');
      sessionStorage.setItem('hm-sidebar-pinned', '1');
    } catch (_) { /* ignore */ }

    document.documentElement.classList.remove('hm-sidebar-is-collapsed');
    document.body.classList.remove('hm-sidebar-collapsed');

    const sidebar = document.getElementById('hmAppSidebar');
    if (!sidebar) return;

    sidebar.classList.remove('sidebar-mini', 'on-resize');

    ['#sidebar-licenses'].forEach((selector) => {
      const group = document.querySelector(selector);
      if (!group) return;
      group.classList.add('show');
      const toggle = group.parentElement?.querySelector(':scope > a.nav-link[data-bs-toggle="collapse"]');
      if (toggle) {
        toggle.classList.add('active');
        toggle.setAttribute('aria-expanded', 'true');
      }
    });
  });
  await page.waitForTimeout(350);
}

async function snap(page, name, { expandSidebar = false, fullPage = false } = {}) {
  if (expandSidebar) {
    await ensureSidebarExpanded(page);
  }
  fs.mkdirSync(screenshotDir, { recursive: true });
  const filePath = path.join(screenshotDir, `${name}.png`);
  await page.screenshot({ path: filePath, fullPage });
  return filePath;
}

async function gotoLicensePage(page, url) {
  await page.goto(url, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle');
  await ensureSidebarExpanded(page);
}

test.describe('License Management Client Review Screenshots', () => {
  test.use({ viewport: { width: 1440, height: 900 } });

  test('capture all role screenshots', async ({ page }, testInfo) => {
    test.setTimeout(600_000);
    test.skip(!password, 'CLIENT_TEST_PASSWORD is required');

    // Pre-pin sidebar for the whole authenticated session
    await page.addInitScript(() => {
      try {
        sessionStorage.setItem('hm-sidebar-collapsed', '0');
        sessionStorage.setItem('hm-sidebar-pinned', '1');
      } catch (_) { /* ignore */ }
    });

    await page.goto('/lang/ar');
    const meta = { screenshots: [], licenseId: null, paymentHref: null };

    // ── 1. Login + OTP ──
    await page.goto('/login');
    await snap(page, '01-login-page');
    await page.locator('input[name="username"]').fill(ACCOUNTS.ccSuper);
    await page.locator('input[name="password"]').fill(password);
    await snap(page, '02-login-filled');
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/\/otp/, { timeout: 30_000 });
    await snap(page, '03-otp-page');
    const otpText = (await page.locator('body').textContent()) || '';
    const otpCode = otpText.match(/\b(\d{6})\b/)?.[1];
    expect(otpCode, 'OTP demo code must appear on page').toBeTruthy();
    await page.locator('.hm-hope-otp-digit').first().fill(otpCode);
    await page.waitForURL((url) => !url.pathname.endsWith('/otp'), { timeout: 30_000 });
    await gotoLicensePage(page, '/dashboard');
    await snap(page, '04-dashboard-after-login', { expandSidebar: true });
    meta.screenshots.push('01-login-page', '02-login-filled', '03-otp-page', '04-dashboard-after-login');

    // ── 2. CC Supervisor ──
    await gotoLicensePage(page, '/modules/licenses/dashboard');
    await snap(page, '05-licenses-dashboard', { expandSidebar: true });
    await gotoLicensePage(page, '/modules/licenses');
    await snap(page, '06-licenses-list', { expandSidebar: true });
    meta.screenshots.push('05-licenses-dashboard', '06-licenses-list');

    await gotoLicensePage(page, '/modules/licenses/create');
    await snap(page, '07-create-license-form', { expandSidebar: true });
    meta.screenshots.push('07-create-license-form');

    await gotoLicensePage(page, '/modules/licenses?status=near_expiry');
    await snap(page, '08-licenses-filtered', { expandSidebar: true });

    await gotoLicensePage(page, '/modules/licenses/admin');
    await snap(page, '09-admin-settings', { expandSidebar: true });
    await gotoLicensePage(page, '/modules/licenses/admin/escalation-groups');
    await snap(page, '10-escalation-groups', { expandSidebar: true });
    meta.screenshots.push('08-licenses-filtered', '09-admin-settings', '10-escalation-groups');

    await gotoLicensePage(page, `/modules/licenses/${REVIEW_LICENSE_ID}`);
    meta.licenseId = REVIEW_LICENSE_ID;
    await snap(page, '11-license-detail-cc-super', { expandSidebar: true });
    meta.screenshots.push('11-license-detail-cc-super');
    await logout(page);

    // ── 3. Responsible User ──
    await login(page, ACCOUNTS.responsible);
    await gotoLicensePage(page, '/modules/licenses');
    await snap(page, '12-responsible-list', { expandSidebar: true });
    meta.screenshots.push('12-responsible-list');

    await gotoLicensePage(page, `/modules/licenses/${PENDING_UNDERTAKING_ID}`);
    const currentUrl = page.url();
    if (currentUrl.includes('/undertaking')) {
      await snap(page, '13-undertaking-page', { expandSidebar: true });
      const attachmentsBlock = page.locator('#undertakingAttachmentsTitle, .lic-panel__title:has-text("مرفقات")').first();
      if (await attachmentsBlock.count()) {
        await attachmentsBlock.scrollIntoViewIfNeeded();
        await page.waitForTimeout(200);
        await snap(page, '13b-undertaking-attachments', { expandSidebar: true });
        meta.screenshots.push('13b-undertaking-attachments');
      }
      await page.locator('#undertaking_confirm').check();
      await snap(page, '14-undertaking-checked', { expandSidebar: true });
      await page.locator('#undertaking_confirm').uncheck();
      meta.screenshots.push('13-undertaking-page', '14-undertaking-checked');
    } else {
      await snap(page, '13-license-detail-responsible', { expandSidebar: true });
      meta.screenshots.push('13-license-detail-responsible');
    }

    await gotoLicensePage(page, `/modules/licenses/${REVIEW_LICENSE_ID}`);
    const responsibility = page.locator('#responsibility');
    if (await responsibility.count()) {
      await responsibility.scrollIntoViewIfNeeded();
      await ensureSidebarExpanded(page);
      await snap(page, '15-undertaking-accepted', { expandSidebar: true });
      meta.screenshots.push('15-undertaking-accepted');
    }

    await gotoLicensePage(page, `/modules/licenses/${REVIEW_LICENSE_ID}`);
    await snap(page, '16-license-processing', { expandSidebar: true });
    meta.screenshots.push('16-license-processing');

    const commentSection = page.locator('#comments');
    if (await commentSection.count()) {
      await commentSection.scrollIntoViewIfNeeded();
      await ensureSidebarExpanded(page);
      await snap(page, '17-comments-section', { expandSidebar: true });
      meta.screenshots.push('17-comments-section');
    }

    const attachSection = page.locator('#attachments');
    if (await attachSection.count()) {
      await attachSection.scrollIntoViewIfNeeded();
      await ensureSidebarExpanded(page);
      await snap(page, '18-attachments-section', { expandSidebar: true });
      meta.screenshots.push('18-attachments-section');
    }

    const timeline = page.locator('#timeline');
    if (await timeline.count()) {
      await timeline.scrollIntoViewIfNeeded();
      await ensureSidebarExpanded(page);
      await snap(page, '19-timeline-section', { expandSidebar: true });
      meta.screenshots.push('19-timeline-section');
    }

    await gotoLicensePage(page, '/modules/licenses/notifications');
    await snap(page, '20-notifications', { expandSidebar: true });
    meta.screenshots.push('20-notifications');
    await logout(page);

    // ── 4. Finance Manager ──
    await login(page, ACCOUNTS.finance);
    await gotoLicensePage(page, '/modules/licenses/finance');
    await snap(page, '21-finance-queue', { expandSidebar: true });
    meta.screenshots.push('21-finance-queue');

    const financePreview = page.locator('main [data-bs-target="#licenseFinanceQuickViewModal"]').first();
    if (await financePreview.count()) {
      await financePreview.click();
      await expect(page.locator('#licenseFinanceQuickViewModal')).toBeVisible({ timeout: 15_000 });
      const moreDetails = page.locator('#licenseFinanceQuickViewModal [data-license-preview-open]');
      meta.paymentHref = await moreDetails.getAttribute('href');
      await moreDetails.click();
      await page.waitForURL(/\/modules\/licenses\/finance\/\d+/, { timeout: 15_000 });
      await page.waitForSelector('.lic-panel__title', { timeout: 15_000 });
      await page.waitForSelector('#payment_status', { timeout: 15_000 });
      await snap(page, '22-finance-payment-detail', { expandSidebar: true });
      meta.screenshots.push('22-finance-payment-detail');
    }
    await logout(page);

    // ── 5. Super Admin ──
    await login(page, ACCOUNTS.superAdmin);
    await gotoLicensePage(page, '/modules/licenses/dashboard');
    await snap(page, '23-super-admin-dashboard', { expandSidebar: true });
    meta.screenshots.push('23-super-admin-dashboard');

    await gotoLicensePage(page, '/modules/licenses');
    await snap(page, '24-export-options', { expandSidebar: true });
    meta.screenshots.push('24-export-options');
    await logout(page);

    await testInfo.attach('screenshot-meta.json', {
      body: JSON.stringify(meta, null, 2),
      contentType: 'application/json',
    });
  });
});
