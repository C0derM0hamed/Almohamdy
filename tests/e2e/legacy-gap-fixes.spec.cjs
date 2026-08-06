const { test, expect } = require('@playwright/test');

const roles = {
  PW_AUDIT_SUPER_ADMIN: {
    required: [
      '/modules/system-administration/reference/work-areas',
      '/modules/system-administration/reference/inquiries',
    ],
    direct: [
      '/modules/system-administration/reference/work-areas',
      '/modules/system-administration/reference/inquiries',
    ],
  },
  PW_AUDIT_PERMISSION_ADMIN: {
    required: [
      '/modules/outgoing-correspondence',
      '/modules/work-absence-notification',
      '/modules/work-absence-notification/notifications',
      '/modules/training/management',
      '/modules/training/coordination',
      '/modules/technical-failures',
      '/modules/medical-agreements/sadq',
      '/modules/medical-agreements/sadq-manual',
    ],
  },
  PW_AUDIT_BRANCH_A: {
    required: [
      '/modules/outgoing-correspondence',
      '/modules/work-absence-notification',
      '/modules/work-absence-notification/notifications',
      '/modules/training/management',
      '/modules/training/coordination',
      '/modules/technical-failures',
      '/modules/medical-agreements/sadq',
      '/modules/medical-agreements/sadq-manual',
    ],
  },
  PW_AUDIT_BRANCH_B: {
    required: [
      '/modules/outgoing-correspondence',
      '/modules/work-absence-notification',
      '/modules/work-absence-notification/notifications',
      '/modules/training/management',
      '/modules/training/coordination',
    ],
    forbidden: [
      '/modules/technical-failures',
      '/modules/medical-agreements/sadq',
      '/modules/medical-agreements/sadq-manual',
    ],
  },
};

async function login(page, role) {
  const username = process.env[`${role}_USERNAME`] || '';
  const password = process.env[`${role}_PASSWORD`] || '';
  test.skip(!username || !password, `Missing ${role} audit credentials`);

  await page.goto('/login', { waitUntil: 'domcontentloaded' });
  await page.locator('input[name="username"]').fill(username);
  await page.locator('input[name="password"]').fill(password);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL(/\/otp(?:$|\?)/);

  const inputs = page.locator('#otp-form input[name^="n"]');
  const otp = process.env.PW_NEW_OTP || '111111';
  for (let index = 0; index < await inputs.count(); index += 1) {
    await inputs.nth(index).fill(otp[index]);
  }

  await page.waitForURL((url) => !url.pathname.endsWith('/otp'));
}

async function menuPaths(page) {
  return page.locator('a[href]').evaluateAll((links) => links.map((link) => {
    try {
      return new URL(link.href).pathname.replace(/\/+$/, '') || '/';
    } catch (_) {
      return null;
    }
  }).filter(Boolean));
}

for (const [role, contract] of Object.entries(roles)) {
  test(`${role} exposes only the verified legacy gap fixes`, async ({ page }) => {
    await login(page, role);
    await page.goto('/dashboard', { waitUntil: 'domcontentloaded' });

    const paths = await menuPaths(page);
    for (const path of contract.required || []) {
      expect(paths, `${role} missing fixed legacy menu ${path}`).toContain(path);
    }
    for (const path of contract.forbidden || []) {
      expect(paths, `${role} received out-of-scope menu ${path}`).not.toContain(path);
    }

    for (const path of contract.direct || []) {
      const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
      expect(response.status(), `${role} cannot open ${path}`).toBeLessThan(400);
    }
  });
}

test('super admin reference forms expose the legacy fields without external integrations', async ({ page }) => {
  await login(page, 'PW_AUDIT_SUPER_ADMIN');

  for (const type of ['work-areas', 'inquiries']) {
    const response = await page.goto(`/modules/system-administration/reference/${type}/create`, { waitUntil: 'domcontentloaded' });
    expect(response.status(), type).toBeLessThan(400);
    await expect(page.locator('select[name="branch_id"]')).toBeVisible();
    await expect(page.locator('input[name="name_en"]')).toBeVisible();
    await expect(page.locator('input[name="name_ar"]')).toBeVisible();
  }
});
