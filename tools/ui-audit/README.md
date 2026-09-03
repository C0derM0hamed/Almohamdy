# UI audit crawler

Logs in as each Playwright audit role (credentials from `.env.audit`, OTP demo
mode must be on), collects every sidebar/in-page link, visits each page per
locale and viewport, screenshots it and prints layout flags
(`HTTP5xx`, `PHP-ERROR`, `scrollX+N`, `ovf[element]`, `tiny<N>` tap targets, `noH1`).

```bash
cd public && php -c ~/.local/php-sys/etc/php/php.ini -S 127.0.0.1:8012   # app
node tools/ui-audit/crawl.mjs SUPER --shots --locales=ar,en --vp=desktop,mobile
node tools/ui-audit/crawl.mjs BRA BRB PERM --locales=ar --vp=desktop
```

Roles: `SUPER` `PERM` `BRA` `BRB`. Options: `--shots`, `--locales=ar,en`,
`--vp=desktop,mobile`, `--only=/modules/x`. Output under `tools/ui-audit/out/`
(git-ignored). See `UI_UX_POLISH_PLAN.md` at the repo root for the findings.
