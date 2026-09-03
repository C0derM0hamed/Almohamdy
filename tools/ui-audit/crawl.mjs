// Crawl every sidebar-reachable page per role, per locale, per viewport.
// Usage: node crawl.mjs [roleKey] [--shots] [--only=/path]
import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';

const BASE = 'http://127.0.0.1:8012';
const OUT = process.env.UI_AUDIT_OUT || path.join(path.dirname(new URL(import.meta.url).pathname), 'out', 'shots');
const env = Object.fromEntries(fs.readFileSync(new URL('../../.env.audit', import.meta.url)).toString().split('\n').filter(l => l.includes('=') && !l.startsWith('#')).map(l => { const i = l.indexOf('='); return [l.slice(0, i).trim(), l.slice(i + 1).trim().replace(/^"|"$/g, '')]; }));
const ROLES = {
  SUPER: { u: env.PW_AUDIT_SUPER_ADMIN_USERNAME, p: env.PW_AUDIT_SUPER_ADMIN_PASSWORD },
  PERM: { u: env.PW_AUDIT_PERMISSION_ADMIN_USERNAME, p: env.PW_AUDIT_PERMISSION_ADMIN_PASSWORD },
  BRA: { u: env.PW_AUDIT_BRANCH_A_USERNAME, p: env.PW_AUDIT_BRANCH_A_PASSWORD },
  BRB: { u: env.PW_AUDIT_BRANCH_B_USERNAME, p: env.PW_AUDIT_BRANCH_B_PASSWORD },
};
const args = process.argv.slice(2);
const roleKeys = args.filter(a => ROLES[a]);
const shots = args.includes('--shots');
const only = (args.find(a => a.startsWith('--only=')) || '').slice(7);
const locales = (args.find(a => a.startsWith('--locales=')) || '--locales=ar,en').slice(10).split(',');
const viewports = (args.find(a => a.startsWith('--vp=')) || '--vp=desktop,mobile').slice(5).split(',');
const VP = { desktop: { width: 1440, height: 900 }, mobile: { width: 390, height: 844 } };
const risky = /(?:delete|destroy|logout|download|export|print|pdf|cancel|callback|reply|lang\/)(?:[/.?_-]|$)/i;

async function login(page, role) {
  await page.goto(BASE + '/login', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="username"]', role.u);
  await page.fill('input[name="password"]', role.p);
  await page.click('button[type="submit"]');
  await page.waitForURL(/\/otp/, { timeout: 30000 });
  const txt = (await page.locator('.hm-auth-alert--success').allTextContents()).join(' ');
  const m = txt.match(/(\d{6})/);
  if (!m) throw new Error('no demo otp');
  const inputs = page.locator('#otp-form input[name^="n"]');
  const n = await inputs.count();
  for (let i = 0; i < n; i++) await inputs.nth(i).fill(m[1][i]);
  await page.waitForURL(u => !u.pathname.endsWith('/otp'), { timeout: 30000 });
}

async function inspect(page) {
  return page.evaluate(() => {
    const docW = document.documentElement.clientWidth;
    const overflowX = document.documentElement.scrollWidth - docW;
    const issues = [];
    const vis = el => { const s = getComputedStyle(el); const r = el.getBoundingClientRect(); return s.display !== 'none' && s.visibility !== 'hidden' && r.width > 0 && r.height > 0; };
    for (const el of document.querySelectorAll('.main-content *')) {
      if (!vis(el)) continue;
      const r = el.getBoundingClientRect();
      if (r.right > docW + 2 && r.width > 20) { issues.push('overflow:' + (el.className || el.tagName).toString().slice(0, 60)); if (issues.length > 6) break; }
    }
    // small tap targets on mobile
    let tinyTargets = 0;
    if (innerWidth < 500) {
      for (const el of document.querySelectorAll('.main-content a, .main-content button')) {
        if (!vis(el)) continue; const r = el.getBoundingClientRect(); if (r.height < 28 && r.width < 28) tinyTargets++;
      }
    }
    const h1 = document.querySelectorAll('.main-content h1').length;
    const title = document.title;
    const errors = [...document.querySelectorAll('.alert-danger, .hm-alert--danger')].map(e => e.textContent.trim().slice(0, 80));
    const raw = /Exception|Undefined|SQLSTATE|ErrorException/.test(document.body.innerText) ? 'PHP-ERROR' : '';
    return { overflowX, issues: [...new Set(issues)], tinyTargets, h1, title, errors, raw, text: document.body.innerText.length };
  });
}

async function links(page) {
  return page.locator('#sidebar-menu a[href], .hm-page-root a[href]').evaluateAll((ls) => {
    const o = location.origin;
    return [...new Set(ls.map(l => { try { const u = new URL(l.href, o); if (u.origin !== o || u.hash || u.pathname === '/') return null; return u.pathname + u.search; } catch { return null; } }).filter(Boolean))];
  });
}

const browser = await chromium.launch();
const report = [];
for (const rk of roleKeys.length ? roleKeys : Object.keys(ROLES)) {
  for (const loc of locales) {
    const ctx = await browser.newContext({ viewport: VP.desktop, locale: loc === 'ar' ? 'ar-SA' : 'en-US' });
    const page = await ctx.newPage();
    const consoleErrs = [];
    page.on('pageerror', e => consoleErrs.push(e.message.slice(0, 120)));
    page.on('response', r => { if (r.status() >= 500) consoleErrs.push('HTTP' + r.status() + ' ' + r.url().slice(0, 80)); });
    await login(page, ROLES[rk]);
    await page.goto(BASE + '/lang/' + loc, { waitUntil: 'domcontentloaded' });
    const seen = new Set(); const queue = [new URL(page.url()).pathname];
    const sidebar = await links(page); sidebar.forEach(l => queue.push(l));
    let depth = 0;
    while (queue.length && seen.size < 260) {
      const p = queue.shift();
      if (seen.has(p) || risky.test(p)) continue;
      if (only && !p.startsWith(only)) continue;
      seen.add(p);
      for (const vpk of viewports) {
        await page.setViewportSize(VP[vpk]);
        consoleErrs.length = 0;
        let status = 0;
        try {
          const resp = await page.goto(BASE + p, { waitUntil: 'networkidle', timeout: 30000 });
          status = resp ? resp.status() : 0;
        } catch (e) { report.push({ role: rk, loc, vp: vpk, p, err: e.message.slice(0, 80) }); continue; }
        await page.waitForTimeout(250);
        const info = await inspect(page);
        const row = { role: rk, loc, vp: vpk, p, status, ...info, js: [...consoleErrs] };
        report.push(row);
        if (shots) {
          const f = path.join(OUT, rk, loc + '-' + vpk, p.replace(/[^a-z0-9]+/gi, '_').slice(0, 80) + '.png');
          fs.mkdirSync(path.dirname(f), { recursive: true });
          await page.screenshot({ path: f, fullPage: true });
        }
        if (vpk === 'desktop' && depth < 2) {
          const more = await links(page);
          for (const l of more) if (!seen.has(l) && !queue.includes(l) && !risky.test(l) && queue.length < 200) queue.push(l);
        }
      }
      depth = Math.floor(seen.size / 40);
    }
    await ctx.close();
    console.error(`${rk}/${loc}: ${seen.size} pages`);
  }
}
await browser.close();
fs.mkdirSync(path.join(OUT, '..'), { recursive: true }); fs.writeFileSync(path.join(OUT, '..', `report-${roleKeys.join('') || 'all'}.json`), JSON.stringify(report, null, 1));
for (const r of report) {
  const flags = [];
  if (r.err) flags.push('ERR ' + r.err);
  if (r.status && r.status !== 200) flags.push('HTTP' + r.status);
  if (r.overflowX > 2) flags.push('scrollX+' + r.overflowX);
  if (r.issues?.length) flags.push('ovf[' + r.issues.slice(0, 3).join('|') + ']');
  if (r.tinyTargets > 3) flags.push('tiny' + r.tinyTargets);
  if (r.raw) flags.push(r.raw);
  if (r.js?.length) flags.push('JS[' + r.js.slice(0, 2).join('|') + ']');
  if (r.h1 === 0) flags.push('noH1');
  if (flags.length) console.log(`${r.role} ${r.loc} ${r.vp} ${r.p} :: ${flags.join(' ')}`);
}
