const { test, expect } = require('@playwright/test');

const MODULES = [
  { key: 'government-circulars', path: '/modules/government-circulars', create: '/modules/government-circulars/create' },
  { key: 'inspection-visits', path: '/modules/inspection-visits', create: '/modules/inspection-visits/create' },
  { key: 'data-requests', path: '/modules/data-requests', create: '/modules/data-requests/create' },
  { key: 'correspondence', path: '/modules/correspondence', create: '/modules/correspondence/create' },
  { key: 'outgoing-correspondence', path: '/modules/outgoing-correspondence', create: '/modules/outgoing-correspondence/create' },
];

const ADMIN_ONLY = [
  { key: 'users', path: '/modules/system-administration/users' },
  { key: 'system-admin', path: '/modules/system-administration' },
];

const ROLES = [
  {
    key: 'PW_AUDIT_SUPER_ADMIN',
    allowed: [...MODULES, ...ADMIN_ONLY],
    denied: [],
  },
  {
    key: 'PW_AUDIT_PERMISSION_ADMIN',
    allowed: [ADMIN_ONLY[0]],
    denied: [ADMIN_ONLY[1]],
    optionalAllowedFromEnv: true,
  },
  {
    key: 'PW_AUDIT_BRANCH_A',
    allowed: MODULES,
    denied: ADMIN_ONLY,
    ownTag: process.env.PW_AUDIT_BRANCH_A_TAG || 'PW_AUDIT_BRANCH_A',
    otherTag: process.env.PW_AUDIT_BRANCH_B_TAG || 'PW_AUDIT_BRANCH_B',
  },
  {
    key: 'PW_AUDIT_BRANCH_B',
    allowed: MODULES,
    denied: ADMIN_ONLY,
    ownTag: process.env.PW_AUDIT_BRANCH_B_TAG || 'PW_AUDIT_BRANCH_B',
    otherTag: process.env.PW_AUDIT_BRANCH_A_TAG || 'PW_AUDIT_BRANCH_A',
  },
];

function credentialsFor(roleKey) {
  const username = process.env[`${roleKey}_USERNAME`] || process.env[roleKey] || '';
  const password = process.env[`${roleKey}_PASSWORD`] || '';

  return { username, password };
}

function allowedFor(role) {
  if (!role.optionalAllowedFromEnv) {
    return role.allowed;
  }

  const extra = (process.env[`${role.key}_ALLOWED_PATHS`] || '')
    .split(',')
    .map((value) => value.trim())
    .filter(Boolean)
    .map((path) => ({ key: path, path }));

  return [...role.allowed, ...extra];
}

function attachTelemetry(page, baseURL) {
  const events = [];
  const base = new URL(baseURL);

  function isFirstParty(url) {
    try {
      return new URL(url).origin === base.origin;
    } catch (_) {
      return false;
    }
  }

  page.on('console', (message) => {
    if (message.type() === 'error') {
      if (/Failed to load resource: the server responded with a status of (401|403|404)/.test(message.text())) {
        return;
      }

      events.push({ type: 'console', text: message.text(), url: page.url() });
    }
  });

  page.on('pageerror', (error) => {
    events.push({ type: 'pageerror', text: error.message, url: page.url() });
  });

  page.on('requestfailed', (request) => {
    if (!isFirstParty(request.url())) {
      return;
    }

    const failure = request.failure()?.errorText || 'unknown failure';
    if (!failure.includes('ERR_ABORTED')) {
      events.push({ type: 'requestfailed', text: failure, url: request.url() });
    }
  });

  page.on('response', (response) => {
    if (!isFirstParty(response.url())) {
      return;
    }

    if (response.status() >= 500) {
      events.push({ type: 'http', status: response.status(), url: response.url() });
    }
  });

  return events;
}

async function attachJson(testInfo, name, value) {
  await testInfo.attach(name, {
    body: Buffer.from(JSON.stringify(value, null, 2)),
    contentType: 'application/json',
  });
}

async function login(page, credentials) {
  await page.goto('/login', { waitUntil: 'domcontentloaded' });
  await page.locator('input[name="username"]').fill(credentials.username);
  await page.locator('input[name="password"]').fill(credentials.password);
  await Promise.all([
    page.waitForURL(/\/otp(?:$|\?)/, { timeout: 20_000 }),
    page.locator('button[type="submit"]').click(),
  ]);

  const inputs = page.locator('#otp-form input[name^="n"], input[name^="n"][maxlength="1"]');
  const count = await inputs.count();
  const otp = process.env.PW_NEW_OTP || '111111';
  expect(count, 'OTP digit inputs were not found').toBeGreaterThan(0);
  expect(otp.length, 'PW_NEW_OTP digit count must match the Laravel form').toBe(count);

  for (let index = 0; index < count; index += 1) {
    await inputs.nth(index).fill(otp[index]);
  }

  await page.waitForURL((url) => !url.pathname.endsWith('/otp'), { timeout: 20_000 });
  await expect(page.locator('body')).toBeVisible();
}

async function openMobileNavigationIfPresent(page) {
  const toggles = [
    '#hmSidebarToggle',
    '.navbar-toggler',
    '[data-toggle="main-sidebar"]',
  ];

  for (const selector of toggles) {
    const locator = page.locator(selector).first();
    if (await locator.count()) {
      await locator.click().catch(() => {});
    }
  }
}

async function countMenuLinksForPath(page, path) {
  const normalizedPath = path.replace(/\/+$/, '') || '/';

  return page.locator('a[href]').evaluateAll((links, target) => links.filter((link) => {
    try {
      const pathname = new URL(link.href).pathname.replace(/\/+$/, '') || '/';
      return pathname === target;
    } catch (_) {
      return false;
    }
  }).length, normalizedPath);
}

async function assertStatusBelow500(response, label) {
  const status = response?.status() || 0;
  expect(status, `${label} did not return an HTTP response`).toBeGreaterThan(0);
  expect(status, `${label} returned a server error`).toBeLessThan(500);
  return status;
}

async function visit(page, path, options = {}) {
  const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
  const status = await assertStatusBelow500(response, path);

  if (!options.allowForbidden) {
    expect(status, `${path} unexpectedly rejected the role`).toBeLessThan(400);
  }

  await expect(page.locator('body')).toBeVisible();
  return status;
}

async function findFirstDetailUrl(page, modulePath) {
  await visit(page, modulePath);
  const hrefs = await page.locator('a[href]').evaluateAll((links) => links.map((link) => link.href));
  const pattern = new RegExp(`${modulePath.replace(/\//g, '\\/')}/\\d+$`);

  return hrefs.find((href) => pattern.test(new URL(href).pathname)) || null;
}

async function verifyProtectedDownload(page, modulePath) {
  const detailUrl = await findFirstDetailUrl(page, modulePath);
  expect(detailUrl, `${modulePath} has no detail link to inspect for downloads`).toBeTruthy();

  await page.goto(detailUrl, { waitUntil: 'domcontentloaded' });
  const hrefs = await page.locator('a[href*="/download"]').evaluateAll((links) => links.map((link) => link.href));
  const downloadUrl = hrefs.find((href) => new URL(href).pathname.includes('/download'));
  expect(downloadUrl, `${modulePath} detail page has no protected download link`).toBeTruthy();
  expect(new URL(downloadUrl).pathname).not.toContain('/storage/');
  expect(new URL(downloadUrl).pathname).not.toContain('/files/');

  const response = await page.context().request.get(downloadUrl);
  expect(response.status(), `${downloadUrl} did not return a successful protected download`).toBeLessThan(400);
  expect(response.headers()['content-disposition'] || '').toContain('attachment');
  expect(response.headers()['content-type'] || '').not.toContain('text/html');
}

for (const role of ROLES) {
  test.describe(`${role.key}`, () => {
    const credentials = credentialsFor(role.key);
    test.skip(!credentials.username || !credentials.password, `Set ${role.key}_USERNAME and ${role.key}_PASSWORD.`);

    test('login, menus, direct URLs, actions, isolation, and protected downloads', async ({ page, baseURL }, testInfo) => {
      const telemetry = attachTelemetry(page, baseURL);
      const allowed = allowedFor(role);
      const evidence = {
        role: role.key,
        project: testInfo.project.name,
        menu: [],
        directUrls: [],
        actions: [],
        downloads: [],
        isolation: [],
      };

      await login(page, credentials);
      await visit(page, '/dashboard');
      await openMobileNavigationIfPresent(page);

      for (const item of allowed) {
        const count = await countMenuLinksForPath(page, item.path);
        evidence.menu.push({ path: item.path, visible: count > 0 });
        expect(count, `${role.key} should see menu link ${item.path}`).toBeGreaterThan(0);
      }

      for (const item of role.denied || []) {
        const count = await countMenuLinksForPath(page, item.path);
        evidence.menu.push({ path: item.path, visible: count > 0, expected: false });
        expect(count, `${role.key} should not see menu link ${item.path}`).toBe(0);
      }

      for (const item of allowed) {
        const status = await visit(page, item.path, { allowForbidden: true });
        evidence.directUrls.push({ path: item.path, status });
        expect(status, `${role.key} direct URL ${item.path} should be allowed`).toBeLessThan(400);
      }

      for (const item of role.denied || []) {
        const status = await visit(page, item.path, { allowForbidden: true });
        evidence.directUrls.push({ path: item.path, status, expected: 'denied' });
        expect([401, 403, 404].includes(status), `${role.key} direct URL ${item.path} should be protected`).toBe(true);
      }

      for (const item of allowed.filter((entry) => entry.create)) {
        const status = await visit(page, item.create, { allowForbidden: true });
        evidence.actions.push({ path: item.create, status });
        expect(status, `${role.key} create action ${item.create} should be allowed`).toBeLessThan(400);
      }

      if (role.ownTag || role.otherTag) {
        for (const item of MODULES) {
          await visit(page, item.path);
          const body = await page.locator('body').innerText();
          evidence.isolation.push({
            path: item.path,
            ownTagVisible: role.ownTag ? body.includes(role.ownTag) : null,
            otherTagVisible: role.otherTag ? body.includes(role.otherTag) : null,
          });
          if (role.otherTag) {
            expect(body, `${role.key} saw another branch tag on ${item.path}`).not.toContain(role.otherTag);
          }
        }
      }

      const otherCompanyTag = process.env.PW_AUDIT_OTHER_COMPANY_TAG || 'PW_AUDIT_OTHER_COMPANY';
      for (const item of allowed.filter((entry) => MODULES.some((module) => module.path === entry.path))) {
        await visit(page, item.path);
        const body = await page.locator('body').innerText();
        expect(body, `${role.key} saw another company tag on ${item.path}`).not.toContain(otherCompanyTag);
      }

      for (const item of allowed.filter((entry) => MODULES.some((module) => module.path === entry.path))) {
        await verifyProtectedDownload(page, item.path);
        evidence.downloads.push({ path: item.path, status: 'verified' });
      }

      expect(telemetry, `Critical browser telemetry for ${role.key}`).toEqual([]);
      await attachJson(testInfo, `${role.key}-${testInfo.project.name}-evidence`, evidence);
    });
  });
}
