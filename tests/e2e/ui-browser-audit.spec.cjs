const { test, expect } = require('@playwright/test');

const roles = [
  'PW_AUDIT_SUPER_ADMIN',
  'PW_AUDIT_PERMISSION_ADMIN',
  'PW_AUDIT_BRANCH_A',
  'PW_AUDIT_BRANCH_B',
];

const riskyPath = /(?:delete|destroy|logout|download|export|print|pdf|cancel|callback|reply)(?:[/.?_-]|$)/i;

async function login(page, role) {
  const username = process.env[`${role}_USERNAME`];
  const password = process.env[`${role}_PASSWORD`];
  test.skip(!username || !password, `Missing ${role} browser-audit credentials`);

  await page.goto('/login', { waitUntil: 'domcontentloaded', timeout: 60_000 });
  await page.locator('input[name="username"]').fill(username);
  await page.locator('input[name="password"]').fill(password);
  await page.locator('button[type="submit"]').click({ noWaitAfter: true });
  await page.waitForURL(/\/otp(?:$|\?)/, { timeout: 60_000 });

  const demoAlerts = await page.locator('.hm-auth-alert--success').allTextContents();
  const match = demoAlerts.join(' ').match(/(?:^|\D)(\d{6})(?:\D|$)/);
  expect(match, 'Local OTP demo code must be visible to browser tests').not.toBeNull();

  const inputs = page.locator('#otp-form input[name^="n"]');
  for (let index = 0; index < await inputs.count(); index += 1) {
    await inputs.nth(index).fill(match[1][index]);
  }
  await page.waitForURL((url) => !url.pathname.endsWith('/otp'));
}

async function visibleNavigationPaths(page) {
  return page.locator('#sidebar-menu a[href], .hm-page-root a[href]').evaluateAll((links) => {
    const origin = window.location.origin;
    return [...new Set(links.map((link) => {
      try {
        const url = new URL(link.href, origin);
        if (url.origin !== origin || url.hash || url.pathname === '/') return null;
        return `${url.pathname}${url.search}`;
      } catch (_) {
        return null;
      }
    }).filter(Boolean))];
  });
}

async function inspectLayout(page) {
  return page.evaluate(() => {
    const visible = (element) => {
      const style = getComputedStyle(element);
      const rect = element.getBoundingClientRect();
      const closedDetails = element.closest('details:not([open])');
      if (closedDetails && element !== closedDetails.querySelector(':scope > summary')) return false;
      if (innerWidth < 1200 && element.closest('#hmAppSidebar') && !document.body.classList.contains('hm-sidebar-mobile-open')) {
        return false;
      }
      return style.display !== 'none' && style.visibility !== 'hidden' && rect.width > 0 && rect.height > 0;
    };
    const label = (element) => {
      const id = element.id ? `#${element.id}` : '';
      const classes = [...element.classList].slice(0, 3).map((name) => `.${name}`).join('');
      return `${element.tagName.toLowerCase()}${id}${classes}`;
    };

    const viewportWidth = document.documentElement.clientWidth;
    const overflow = Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) - viewportWidth;
    const clipped = [...document.querySelectorAll('input, select, textarea, button, .card, .modal-dialog, .dropdown-menu.show')]
      .filter(visible)
      .filter((element) => {
        const rect = element.getBoundingClientRect();
        const scrollParent = element.closest('.table-responsive, .overflow-auto, .fm-table-scroll, .gc-table-wrap, [style*="overflow-x"]');
        if (scrollParent && scrollParent.scrollWidth > scrollParent.clientWidth) return false;
        return rect.left < -3 || rect.right > viewportWidth + 3;
      })
      .slice(0, 12)
      .map(label);

    const ids = [...document.querySelectorAll('[id]')].map((element) => element.id).filter(Boolean);
    const duplicateIds = [...new Set(ids.filter((id, index) => ids.indexOf(id) !== index))].slice(0, 12);
    const nestedMain = document.querySelectorAll('main main, .main-content .main-content').length;
    const deadLinks = [...document.querySelectorAll('a[href="#"]')]
      .filter(visible)
      .filter((link) => !link.matches('[data-bs-toggle], [data-toggle], .disabled, [role="button"]'))
      .map((link) => link.textContent.trim().replace(/\s+/g, ' '))
      .filter(Boolean)
      .slice(0, 12);

    let shellOverlap = 0;
    const sidebar = document.querySelector('#hmAppSidebar');
    const main = document.querySelector('main.main-content');
    if (sidebar && main && visible(sidebar) && visible(main) && innerWidth >= 1200) {
      const a = sidebar.getBoundingClientRect();
      const b = main.getBoundingClientRect();
      shellOverlap = Math.max(0, Math.min(a.right, b.right) - Math.max(a.left, b.left));
    }

    return { overflow, clipped, duplicateIds, nestedMain, deadLinks, shellOverlap };
  });
}

for (const role of roles) {
  test(`${role} visible browser flows stay usable`, async ({ page }, testInfo) => {
    test.setTimeout(600_000);
    const consoleErrors = [];
    page.on('pageerror', (error) => consoleErrors.push(`pageerror: ${error.message}`));
    page.on('console', (message) => {
      if (message.type() === 'error') consoleErrors.push(`console: ${message.text()}`);
    });

    await login(page, role);
    const dashboardResponse = await page.goto('/dashboard', { waitUntil: 'networkidle' });
    expect(dashboardResponse.status()).toBeLessThan(400);

    const discovered = await visibleNavigationPaths(page);
    const requestedPaths = (process.env.PW_UI_PATHS || '').split(',').map((path) => path.trim()).filter(Boolean);
    const pathLimit = testInfo.project.name === 'mobile' ? 60 : 120;
    const paths = (requestedPaths.length ? requestedPaths : [...new Set(['/dashboard', ...discovered])])
      .filter((path) => !riskyPath.test(path))
      .slice(0, pathLimit);
    const failures = [];

    for (const path of paths) {
      consoleErrors.length = 0;
      const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
      await page.waitForTimeout(120);
      const status = response ? response.status() : 0;
      const layout = await inspectLayout(page);

      if (status >= 400 || status === 0) failures.push(`${path}: HTTP ${status}`);
      if (layout.overflow > 4) failures.push(`${path}: page overflow ${layout.overflow}px`);
      if (layout.clipped.length) failures.push(`${path}: clipped ${layout.clipped.join(', ')}`);
      if (layout.duplicateIds.length) failures.push(`${path}: duplicate ids ${layout.duplicateIds.join(', ')}`);
      if (layout.nestedMain) failures.push(`${path}: nested main layout (${layout.nestedMain})`);
      if (layout.deadLinks.length) failures.push(`${path}: dead links ${layout.deadLinks.join(' | ')}`);
      if (layout.shellOverlap > 3) failures.push(`${path}: sidebar/main overlap ${layout.shellOverlap}px`);
      if (consoleErrors.length) failures.push(`${path}: ${consoleErrors.join(' | ')}`);
    }

    await testInfo.attach(`${role}-ui-audit.json`, {
      body: JSON.stringify({ paths, failures }, null, 2),
      contentType: 'application/json',
    });
    expect(failures, failures.join('\n')).toEqual([]);
  });
}

for (const role of roles.slice(1)) {
  test(`${role} does not see links its role cannot open`, async ({ page }) => {
    test.setTimeout(120_000);
    await login(page, role);
    await page.goto('/dashboard', { waitUntil: 'networkidle' });
    const paths = await visibleNavigationPaths(page);

    expect(paths).not.toContain('/modules/complaints');
    expect(paths).not.toContain('/modules/legacy-sidebar/medical_approval_notifications');
    if (role === 'PW_AUDIT_BRANCH_B') {
      expect(paths).not.toContain('/modules/medical-agreements/sadq');
    }
  });
}

test('public landing page has no dead actions or horizontal clipping', async ({ page }) => {
  const errors = [];
  page.on('pageerror', (error) => errors.push(error.message));
  const response = await page.goto('/', { waitUntil: 'networkidle' });
  expect(response.status()).toBeLessThan(400);
  const layout = await inspectLayout(page);
  expect(layout.overflow).toBeLessThanOrEqual(4);
  expect(layout.clipped).toEqual([]);
  expect(layout.deadLinks).toEqual([]);
  expect(errors).toEqual([]);
});

test('super admin shell, dropdowns, tables and modals remain interactive', async ({ page }, testInfo) => {
  test.setTimeout(180_000);
  await login(page, 'PW_AUDIT_SUPER_ADMIN');
  await page.goto('/dashboard', { waitUntil: 'networkidle' });
  const adminPaths = await visibleNavigationPaths(page);
  expect(adminPaths).toContain('/modules/complaints');
  expect(adminPaths).toContain('/modules/medical-agreements/sadq');
  expect(adminPaths).toContain('/modules/admission-inpatient/approvals');

  if (testInfo.project.name === 'mobile') {
    const sidebarToggle = page.locator('.hm-figma-tools__mobile-sidebar:visible').first();
    await expect(sidebarToggle).toBeVisible();
    await sidebarToggle.click();
    await expect(page.locator('body')).toHaveClass(/hm-sidebar-mobile-open/);
    await expect(page.locator('#hmAppSidebar')).toBeInViewport();
    await page.locator('.hm-sidebar-mobile-backdrop').click({ position: { x: 2, y: 2 } });
    await expect(page.locator('body')).not.toHaveClass(/hm-sidebar-mobile-open/);
  } else {
    await page.locator('#hmAppSidebar').hover();
    const sidebarToggle = page.locator('#hmSidebarToggle');
    await expect(sidebarToggle).toBeVisible();
    await sidebarToggle.click();
    await page.locator('main.main-content').hover();
    await expect(page.locator('body')).not.toHaveClass(/hm-sidebar-collapsed/);
  }

  await page.locator('#hmFigmaSettings').click();
  const settingsMenu = page.locator('[aria-labelledby="hmFigmaSettings"]');
  await expect(settingsMenu).toBeVisible();
  await expect(settingsMenu).toBeInViewport();
  await page.keyboard.press('Escape');

  await page.goto('/modules/government-circulars', { waitUntil: 'networkidle' });
  const columnsTool = page.locator('details.gc-tool').first();
  await columnsTool.locator('summary').click();
  await expect(columnsTool.locator('[data-gc-columns]')).toBeVisible();
  await expect(columnsTool.locator('[data-gc-columns]')).toBeInViewport();
  await page.keyboard.press('Escape');

  const rowMenuToggle = page.locator('.gc-actions__toggle').first();
  if (await rowMenuToggle.count()) {
    await rowMenuToggle.scrollIntoViewIfNeeded();
    await page.waitForTimeout(150);
    await rowMenuToggle.click();
    const rowMenu = page.locator('body > .gc-actions__menu.is-open');
    await expect(rowMenu).toBeVisible();
    await rowMenu.locator('[data-gc-status-modal]').click();
    await expect(page.locator('.modal.show')).toBeVisible();
    await expect(page.locator('.modal.show .modal-dialog')).toBeInViewport();
    await page.locator('.modal.show [data-bs-dismiss="modal"]').first().click();
    await expect(page.locator('.modal.show')).toHaveCount(0);
  }
});
