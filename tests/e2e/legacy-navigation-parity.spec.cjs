const { test, expect } = require('@playwright/test');

const roles = {
  PW_AUDIT_SUPER_ADMIN: {
    required: ['/modules/legacy-office/signature'],
    forbidden: ['/modules/emergency-reception/corpse', '/modules/medical-referrals/pulse-status', '/modules/medical-agreements/standard', '/modules/legacy-office/memos'],
  },
  PW_AUDIT_PERMISSION_ADMIN: {
    required: ['/modules/emergency-reception/corpse', '/modules/medical-referrals/pulse-status', '/modules/medical-agreements/standard', '/modules/medical-agreements/sadq', '/modules/medical-agreements/sadq-manual', '/modules/governmental-services', '/modules/legacy-office/holidays', '/modules/legacy-office/medical-reports', '/modules/legacy-office/memos', '/modules/legacy-office/memos/received', '/modules/legacy-office/coverage', '/modules/legacy-office/signature'],
    forbidden: [],
  },
  PW_AUDIT_BRANCH_A: {
    required: ['/modules/emergency-reception/corpse', '/modules/medical-referrals/pulse-status', '/modules/medical-agreements/standard', '/modules/medical-agreements/sadq', '/modules/medical-agreements/sadq-manual', '/modules/governmental-services', '/modules/legacy-office/holidays', '/modules/legacy-office/medical-reports', '/modules/legacy-office/memos', '/modules/legacy-office/memos/received', '/modules/legacy-office/coverage', '/modules/legacy-office/signature'],
    forbidden: [],
  },
  PW_AUDIT_BRANCH_B: {
    required: ['/modules/health-service-purchase', '/modules/legacy-office/memos', '/modules/legacy-office/memos/received', '/modules/legacy-office/coverage', '/modules/legacy-office/signature'],
    forbidden: ['/modules/emergency-reception/corpse', '/modules/medical-referrals/pulse-status', '/modules/medical-agreements/standard', '/modules/governmental-services', '/modules/legacy-office/holidays', '/modules/legacy-office/medical-reports'],
  },
};

async function login(page, role) {
  await page.goto('/login');
  await page.locator('input[name="username"]').fill(process.env[`${role}_USERNAME`]);
  await page.locator('input[name="password"]').fill(process.env[`${role}_PASSWORD`]);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL(/\/otp(?:$|\?)/);
  const inputs = page.locator('#otp-form input[name^="n"]');
  const otp = process.env.PW_NEW_OTP || '111111';
  for (let index = 0; index < await inputs.count(); index += 1) await inputs.nth(index).fill(otp[index]);
  await page.waitForURL((url) => !url.pathname.endsWith('/otp'));
}

for (const [role, contract] of Object.entries(roles)) {
  test(`${role} receives only its verified legacy navigation`, async ({ page }) => {
    test.skip(!process.env[`${role}_USERNAME`] || !process.env[`${role}_PASSWORD`]);
    await login(page, role);
    const hrefs = await page.locator('a[href]').evaluateAll((links) => links.map((link) => new URL(link.href).pathname));

    for (const path of contract.required) expect(hrefs, `${role} missing ${path}`).toContain(path);
    for (const path of contract.forbidden) expect(hrefs, `${role} must not see ${path}`).not.toContain(path);
  });
}
