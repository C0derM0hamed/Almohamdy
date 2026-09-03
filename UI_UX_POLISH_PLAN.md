# UI/UX Polish Plan — branch `ui-ux-polish`

Status: **audit complete, implementation not started** (2026-09-03).
Owner of next step: implementation model (Opus). Everything needed to continue
without repeating the audit is in this file plus `tools/ui-audit/`.

Ground rules (from the task brief):
- Preserve brand identity (Al Hammadi Hospitals: indigo `#6366f1`, deep navy
  `#302a58`, Noto Kufi Arabic, white 16px-radius cards on `#f7f8fc`). This is
  NOT a rebrand.
- Keep all functionality, workflows, permissions, routes and backend behaviour
  intact. Views/CSS/JS/lang files only, plus the two P0 bug fixes below.
- Verify with Playwright: ar/en, RTL/LTR, desktop 1440 / mobile 390.
- Do not deploy.

---

## 0. How to resume (environment)

```bash
# DB: MariaDB is a systemd service, socket auth, no password for local user
mariadb --skip-ssl -u mohamed test_hms -e 'select 1'

# App server (php artisan serve loses the ini → pdo_mysql missing). Use:
cd public && php -c ~/.local/php-sys/etc/php/php.ini -S 127.0.0.1:8012

# Test accounts (usernames/passwords in .env.audit; OTP demo mode is ON in .env,
# the 6-digit code is printed in the green alert on /otp):
#   PW_AUDIT_SUPER_ADMIN (level 3, admin) · PW_AUDIT_PERMISSION_ADMIN (level 1)
#   PW_AUDIT_BRANCH_A (level 2) · PW_AUDIT_BRANCH_B (level 4)

# Crawler (vendored from the audit). Screenshots + overflow/JS/500 flags:
node tools/ui-audit/crawl.mjs SUPER --shots --locales=ar,en --vp=desktop,mobile
node tools/ui-audit/crawl.mjs BRA BRB PERM --locales=ar --vp=desktop
#   output: tools/ui-audit/out/{shots,report-*.json}; flags printed to stdout
#   flags: HTTP5xx, PHP-ERROR, scrollX+N, ovf[element], tiny<N> (tap targets), noH1
# Existing e2e suites (must still pass after changes):
npx playwright test tests/e2e/ui-browser-audit.spec.cjs tests/e2e/audit-roles.spec.cjs
```

Locale switch: `/lang/ar`, `/lang/en`. Language toggle lives in
`layouts/partials/figma-header-tools.blade.php`.

---

## 1. Audit summary

Crawled 260 authenticated pages per role (4 roles), AR + EN, desktop + mobile
(~1,000 screenshots), plus login/OTP/recovery/portal. Visual review found the
product is **already ~40% on a polished “Figma module” design** and the rest
is split across three older shells. The job is mostly *convergence*, not
invention.

### 1.1 Shell dialects found

| Dialect | Look | Where | Verdict |
|---|---|---|---|
| **A — Figma module** (target) | breadcrumb row → hero (round indigo icon + h1 + subtitle + primary action) → indigo/navy KPI cards → white search card → white table card with count chip, toolbar, tinted status pills, Arabic pagination | complaints, inquiries, hospital-services, doctors-directory, service-locations (index/opd), corporate-communication family (circulars, inspection visits, data requests, correspondence, outgoing), licenses index/dashboard/finance, publications, technical-failures, transferal, settings, system-administration dashboard + users, legacy-sidebar (partially: `wf-*` classes), employee-requests, legal-claims, work-absence dashboard | keep; fix details |
| **B — title card** | white card with title + icon on the right, no breadcrumb, native selects/dates, white KPI cards with coloured numbers, solid-colour pills, English `Showing 1 to 15 of N results` | gov-accounts (all 7), admission-inpatient (all 23), licenses create/edit/show, medical-referrals (4), legacy-office (coverage, memos, memos-received, holidays, medical-reports), medical-agreements, health-service-purchase, admission-calculator index | migrate to A |
| **C — legacy Bootstrap** | red field labels, `#1d4ed8` buttons, native controls, bordered tables, English pagination | medical-appointments index/show, emergency-reception (5 lists + guides), admission report-9 | migrate to A |
| **D — split cards** | separate breadcrumb card + separate title card + bright-blue rectangular CTA, bordered table | employee-leave (all), service-locations floors (9 pages), work-absence notifications list, training management/coordination | migrate to A |

### 1.2 Systemic issues (fix once, benefits every page)

| # | Issue | Evidence | Root cause / file |
|---|---|---|---|
| S1 | **Mobile tables clip at the left edge with no horizontal scroll**; action buttons and status columns become unreachable on ~every list page. Some tables clip even at 1440 (`rep9-actions`, `rep9-notices`, complaints EN, archives, medical-appointments). | crawler `ovf[...TABLE]` flags; all mobile shots | No shared `overflow-x:auto` wrapper; `.main-content{overflow-x:hidden}` <1200px masks it (`public/css/hm-hope-ui-bridge.css`). |
| S2 | **English pagination on Arabic pages** (`Showing 1 to 25 of 95 results`) | 32 views call `->links()` with the Bootstrap 5 default | `AppServiceProvider::boot()` uses `Paginator::useBootstrapFive()`; 27 views already pass `'pagination.hm'` (translated). |
| S3 | **Segmented single-character digit boxes** for any input whose name matches id/number/mobile/code/… (19 boxes for a license number, 10 for mobile, wraps to two rows) | licenses/create, complaints/create, inquiries create, health-service-purchase, medical-agreements | `public/js/hm-number-boxes.js` auto-enhances inputs by name regex; opt-out `data-number-boxes="false"`. Keep the component only for OTP-style codes (≤6 chars). |
| S4 | **Native browser controls**: `mm/dd/yyyy` date inputs, native `<select>` chevrons, English `Choose File / No file chosen` | everywhere outside dialect A | No shared form-control skin for `input[type=date]`, `select`, `input[type=file]`. |
| S5 | **Untranslated / raw data columns**: DB column names as headers (`Lawsuit Date`, `status.Payment`, `Becuse`, `No Id`, `Onid`), numeric status codes (`1`, `2`, `12`), raw user ids (`126`), `active`, `PDF`, `Pre exiting`, `5 dayas` | legacy-sidebar (all 20 pages), admission-inpatient reference, report-9, work-absence notifications (`نوع التغيب` = `0/1/test`) | `resources/views/legacy-sidebar/index.blade.php` `$labels` map falls back to `str($column)->headline()`; page specs in `app/Services/LegacySidebarPageService.php`. |
| S6 | **Generic subtitle** “إدارة البيانات والإجراءات ضمن نطاق الفرع” on all 20 legacy-sidebar pages; breadcrumb parent “الخدمات التشغيلية” hard-coded Arabic (not translated in EN). | legacy-sidebar | same view |
| S7 | **Status pills inconsistent**: solid green/red/navy blocks (B/C/D) vs tinted-with-dot (A); some A pages render solid navy pill (`اسم` in correspondence, inspection visits). | many | no shared pill partial; `layouts/partials/figma/badge.blade.php` exists but is unused outside a few modules. |
| S8 | **Empty states are bare text** (“لا توجد سجلات.”) without icon/CTA (A has a good one on `service-locations/opd/7`). | ~25 lists | no shared empty-state partial (`work-absence-notification/partials/wan-empty.blade.php` is a good local one). |
| S9 | **Pagination density**: complaints shows 5 rows/page → 164 page links; service-prices 15/page → 337 links. | complaints, service-prices, birth_notification | per-page defaults + `hm.blade.php` renders all `elements`. |
| S10 | **Header toolbar placement on mobile** (settings/bell/lang) floats between hero CTA and search on dialect-A pages; on B/C/D it sits above the title card. Hamburger exists on all pages (OK). | mobile shots | `figma-header-tools` positioned absolutely per dialect. |
| S11 | Mixed numerals: charts use Arabic-Indic digits, KPI cards Latin. | dashboard, work-absence dashboard, licenses dashboard | Chart.js locale; decide Latin everywhere (matches KPI cards and data tables). |
| S12 | Duplicate/competing primary CTAs (e.g. calculator pages have 3 top actions; package-catalog outlined vs filled inconsistently; OPD first card solid vs others tinted). | admission calculator, package-catalog, opd/* | per-view |
| S13 | Two identical pages: `/modules/training/management` and `/modules/training/coordination`; `/modules/admission/calculator/standard` = `/modules/admission-inpatient/calculator/standard`. Data columns all “—”. | training | leave routes; style once (shared view). |

### 1.3 Functional bugs found (must fix, tiny)

| # | Route | Error | Fix |
|---|---|---|---|
| P0-1 | `/modules/system-administration/reference/{branches,companies,departments,…}` (16 admin reference pages) | HTTP 500 `Class "AppSupportLocaleText" not found` | `resources/views/system-administration/reference/index.blade.php:17` — the backslashes were stripped: use `\App\Support\LocaleText::localizedValue(...)` (class exists at `app/Support/LocaleText.php`). |
| P0-2 | `/modules/legacy-sidebar/onlinetody` | HTTP 500 `Undefined property: stdClass::$id` at `legacy-sidebar/index.blade.php:132` | spec table is `ra_users` whose PK is `hr_id` (see `app/Services/LegacySidebarPageService.php:43`). Read the id via the spec’s key column (add `'key' => 'hr_id'` to the spec and use `$row->{$spec['key'] ?? 'id'}` in the view/controller) — no schema change. |
| P0-3 | `/modules/medical-appointments/{id}/documents/*` triggers a download from a sidebar-reachable link (crawler `Download is starting`). Not a UI bug; exclude from crawler with `risky` regex (already excluded `download`). No action. | | |

### 1.4 Pages already good (dialect A, keep, minor only)

dashboard · complaints (index) · inquiries incoming/outgoing · hospital-services + sections · doctors-directory + specialities · clinics-directory · service-locations index + opd/* · government-circulars · inspection-visits · data-requests · correspondence · outgoing-correspondence · publications · technical-failures · transferal · settings · system-administration dashboard + users · licenses index/dashboard/finance · legal-claims · employee-requests (duty/permission/resignation) · work-absence dashboard · login / OTP / password recovery / portal (`/`) · profile/password.

---

## 2. Design decisions (the standard every page converges to)

Source of truth: existing tokens in `public/css/hm-figma-tokens.css` and the
module shell in `public/css/hm-figma-module.css` + `layouts/partials/figma-module-header.blade.php`.
Reference pages to copy from: `/modules/complaints`, `/modules/hospital-services/sections/1`,
`/modules/licenses/finance`, `/modules/system-administration/users`.

1. **Page header** = `@section('figma_page_header','true')` +
   `@include('layouts.partials.figma-subpage-header', [...])` (breadcrumb: Services › Module › Page;
   hero icon circle 48px indigo; h1 22–24px/700; subtitle `--hm-muted`; one primary
   `fm-btn fm-btn--primary` action on the opposite side; secondary actions as
   `fm-btn fm-btn--ghost`). No page may have two filled primary buttons in the header.
2. **KPI strip** = `fm-kpi` cards, alternating `#6366f1` / `#302a58`, white text,
   icon in 40px translucent circle, 20px radius. Max 4 per row (desktop), 2 per
   row at ≥768, 1 below.
3. **Filter card** = white card, 16px radius, labelled fields in a 12-col grid
   (`fm-field`), primary “بحث/Search” + ghost “إعادة تعيين/Reset”. Filters never
   use number boxes (already excluded by `isFilterContext`).
4. **Table card** = `fm-table-card`: header row with title + count chip
   (`fm-chip`) + toolbar (sort/export/refresh icon buttons); table inside
   **`.fm-table-scroll { overflow-x:auto; -webkit-overflow-scrolling:touch }`**
   with a right/left fade hint on mobile; `thead` `#fafafd`; row height 52px;
   first column sticky on mobile where the row has an identifier.
   Actions column: outlined `fm-btn--outline sm` with label on desktop, icon-only
   ≥44px tap target on mobile via `.fm-btn__label{display:none}` <768.
5. **Status pill** = `fm-pill fm-pill--{success|warning|danger|info|neutral}`:
   tinted background (`--hm-chip`, `#ecfdf5`, `#fff7ed`, `#fef2f2`, `#eff6ff`),
   700-weight 12px text, leading 6px dot. Never solid blocks. Map raw codes to
   labels in the view (lang files), never print numeric codes.
6. **Empty state** = shared partial: 56px tinted icon circle, title, one-line
   hint, optional CTA. Used inside the table card (colspan row) and card grids.
7. **Forms** = `fm-form` card: 2-column grid at ≥992 (1 column below), labels
   13px/600 above inputs, required marker `*` in `--hm-danger`, help text
   `--hm-muted-2`, section headings with 32px icon square; sticky footer bar with
   primary “حفظ” + ghost “إلغاء” (RTL: primary on the right).
   Native controls get a shared skin (see S4): date inputs show the calendar icon
   and placeholder in locale; selects get the indigo chevron (`appearance:none` +
   inline SVG); file inputs are replaced visually by a dashed drop-zone label
   (`fm-file`) wrapping the real `<input type=file>` (keep name/attrs).
8. **Digit boxes**: keep only for OTP/PIN (≤6 chars). Everything else becomes a
   single `fm-input fm-input--numeric` (LTR, tabular numerals, letter-spacing
   .08em). Implement by tightening `NUMBER_NAME_PATTERN` in
   `public/js/hm-number-boxes.js` to `otp|pin|code` and honouring `maxlength ≤ 6`,
   plus removing `inputmode` auto-trigger. Inputs keep their names → backend untouched.
9. **Pagination** = `pagination.hm` everywhere (translated), compact window
   (`onEachSide(1)`), summary “عرض X–Y من أصل N” localized; default 25/page on
   lists that currently show 5 (`complaints`) — change only the view-level
   `->paginate()` argument if it is a constant in the controller, otherwise leave.
10. **Typography/numerals**: Latin digits everywhere including Chart.js
    (`locale: 'en'` on the chart config), Arabic UI text via `__()`.
11. **Mobile header** (<1200): one 56px top bar: hamburger (start), brand mark
    (centre), tools (settings/bell/lang) at the end; page hero below. Remove the
    floating absolute toolbar in mobile media query.
12. **Accessibility floor**: visible `:focus-visible` ring (`2px solid #6366f1`,
    offset 2px) on all interactive elements; icon-only buttons need `aria-label`;
    ≥44×44 tap targets on mobile; contrast ≥4.5:1 for text (muted `#64748b` on
    white = 4.6:1 OK; `#8c8a98` only for ≥14px/600 or decorative); `<h1>` on
    every page (dashboard currently has none — make the greeting name an h1).

---

## 3. Implementation phases and tasks

Mark `[x]` as you go. Every task lists the files it touches. Keep each phase
committed separately with the required trailer.

### Phase 0 — blockers (30 min)
- [ ] 0.1 Fix `AppSupportLocaleText` → `\App\Support\LocaleText` in `resources/views/system-administration/reference/index.blade.php:17`.
- [ ] 0.2 Fix `onlinetody` 500 (`ra_users` PK `hr_id`) in `app/Services/LegacySidebarPageService.php` + `resources/views/legacy-sidebar/index.blade.php` (id access) — verify `/modules/legacy-sidebar/onlinetody` renders as SUPER.
- [ ] 0.3 Re-run `node tools/ui-audit/crawl.mjs SUPER --locales=ar --vp=desktop` → zero `HTTP5xx/PHP-ERROR`.

### Phase 1 — shared foundation (the multiplier; do before any page)
Create `public/css/hm-fm-shared.css` (loaded in `layouts/app.blade.php` after
`hm-figma-module.css`) and partials under `resources/views/layouts/partials/fm/`.
- [ ] 1.1 `fm/table-scroll` behaviour: global CSS `.fm-table-card table`, `.wf-table`, `.hm-ma-table`, `.lic-table`, `.cp-fm-table`, `.gc-table` and any `.table-responsive` → wrap by CSS where possible (`display:block; overflow-x:auto` on the existing scroll containers) and add `.fm-table-scroll` div in views that lack one. Add mobile fade hint. Removes S1.
- [ ] 1.2 Pagination: switch `Paginator::defaultView('pagination.hm')` (and `defaultSimpleView`) in `app/Providers/AppServiceProvider.php`; keep `useBootstrapFive()` removed. Update `resources/views/pagination/hm.blade.php` to show localized summary (`عرض :from–:to من أصل :total` / `Showing :from–:to of :total`) and compact page window. Add `pagination.summary` keys to `lang/ar/pagination.php` + `lang/en/pagination.php`. Removes S2, S9.
- [ ] 1.3 Number boxes policy (S3): edit `public/js/hm-number-boxes.js` `NUMBER_NAME_PATTERN` → only `otp|pin|verification|code` **and** `maxlength ≤ 6`; drop the `inputMode` trigger. Add `.fm-input--numeric` skin in `hm-fm-shared.css`; add `dir="ltr" class="fm-input--numeric"` to mobile/id/file-number/license-number inputs in: `licenses/_form.blade.php`, `complaints/*create*`, `inquiries/*create*`, `modules/emergency-reception/health-index.blade.php`, `legacy-workflows/medical-agreements/_digit-input.blade.php` (keep the OTP-style behaviour only where it is a verification code).
- [ ] 1.4 Native control skin (S4): in `hm-fm-shared.css` style `select.form-select, select.fm-select` (appearance none + indigo chevron SVG, RTL-aware background-position), `input[type=date]` (height 46px, calendar-picker-indicator tint, locale placeholder via `:lang(ar)` `::before` trick is not reliable → accept native text but style the box), `input[type=file]` → add `fm/file-input.blade.php` partial (dashed drop zone, localized “اختر ملفاً / Choose a file”, shows selected filename via 6-line JS in `public/js/hm-fm-shared.js`). Apply partial in Phase 2 forms.
- [ ] 1.5 Status pill partial `fm/pill.blade.php` (`tone`, `label`) + CSS (S7). Provide a tiny helper `App\Support\StatusTone::for($key)`? — **no**: keep it view-only; each module passes `tone` explicitly.
- [ ] 1.6 Empty-state partial `fm/empty.blade.php` (`icon`, `title`, `hint`, `actionUrl`, `actionLabel`) + CSS (S8).
- [ ] 1.7 Form kit CSS (`fm-form`, `fm-field`, `fm-form__footer` sticky) + section heading partial `fm/form-section.blade.php`.
- [ ] 1.8 Mobile header bar (S10): in `hm-figma-header.css`/`hm-figma-sidebar.css` media `<1200px`: make `.hm-figma-crumb-row` a flex bar with hamburger + brand + tools; hide the absolutely positioned `figma-header-tools` copy. Verify on `/modules/complaints`, `/modules/gov-accounts/dashboard`, `/modules/medical-appointments` at 390px.
- [ ] 1.9 Focus ring + tap targets (A11y): `:focus-visible` rule in `hm-ui-global.css`; `.fm-btn--icon{min-width:44px;min-height:44px}` <768. Add `aria-label` to icon-only buttons in shared partials (`figma-header-tools`, table toolbars).
- [ ] 1.10 Chart numerals (S11): `public/js/hm-dashboard-analytics.js`, licenses dashboard and work-absence dashboard chart configs → `options.locale='en'`.
- [ ] 1.11 Dashboard h1: `resources/views/dashboard/home.blade.php` greeting → `<h1>`; chevrons in “بحاجة إلى متابعة” point in reading direction (`bi-chevron-left` in RTL is correct; verify EN flips).

### Phase 2 — module migrations (ordered by impact; each = one commit)

Pattern for every list page: replace the local header with
`figma-subpage-header` (breadcrumb + hero + primary action), KPI strip if the
page has counters, filter card, table card (`fm-table-scroll`, count chip,
toolbar, pills, empty state), `pagination.hm`. Pattern for every form: `fm-form`
card, sectioned, sticky footer, skinned controls. Do not change field names,
routes, or validation.

- [ ] 2.1 **admission-inpatient** (23 list pages + 13 forms; dialect B) — views `resources/views/admission-inpatient/**`, css `public/css/hm-detail-stat-cards.css` (KPI white cards → `fm-kpi`). Reference pages share `reference/index.blade.php` + `reference/form.blade.php` → fixing those two fixes 12 routes. Map raw codes: `حالة التنويم` 1/2, `القسم` 1, `الفترة` 2/3, `active` → `__()` labels (add keys to `lang/*/admission_inpatient.php`). Hide always-empty columns (الاسم بالصينية, التفاصيل) unless data exists. `service-prices` import card → `fm/file-input`, actions column, paginate 50. `consents` status pills, `templates` truncation → tooltip + view link. `approvals` quick-links become `fm-tabs` (partial `layouts/partials/figma/tabs.blade.php` exists).
- [ ] 2.2 **gov-accounts** (7 pages; B) — `resources/views/gov-accounts/**`, css `hm-gov-accounts-admin.css`. Dashboard: add breadcrumb/hero, move the six export buttons into the table-card toolbar (“تصدير” dropdown), 5 KPI → 4 + 1 secondary row, real empty-state cards for the two charts. Lists: labelled filters, pills, empty states, `fm-table-scroll`.
- [ ] 2.3 **legacy-sidebar** (20 pages; A-ish `wf-*`) — `resources/views/legacy-sidebar/{index,form,show}.blade.php`, `app/Services/LegacySidebarPageService.php` (add per-page `subtitle` + `columns_labels` where missing), css `hm-figma-workflows.css`. Complete `$labels` for every column that currently falls back to `headline()` (list from screenshots: `Lawsuit Date, status.Payment, Becuse, No Lawsuit, No Id, Onid, Send Section, Dates, C, Details, Answer, name.Patient, Service, No File, Date In, Date Out, Countries, Branches Departments, Period, Location, Code, Code Reason, Caller Name, Caller Section, Ext Number, Branches Id, Subsection, C Key, Icon, Disapproval Reason, Lawsuit Approval Status Id, Newborn Status/Type, Gender, Room Number, Mother/Newborn File Number, Newborn Name, Mother/Father Full Name`). Translate breadcrumb parent “الخدمات التشغيلية” via `__()`. Status columns: render `caseStatuses` label instead of numeric. Actions: outlined buttons with labels on desktop; delete stays `is-danger`. Per-page subtitles from the spec (fallback to module description, not the generic sentence).
- [ ] 2.4 **medical-appointments** (index/show/timeline; C) — `resources/views/medical-appointments/*.blade.php`, css `hm-medical-appointments.css`: remove red labels & `#1d4ed8` buttons, 12-KPI grid → 4 primary KPI + “more statuses” collapsible strip, filter card, table pills (already tinted), pagination.hm, actions (“تحديث الحالة” outlined + file icon with label).
- [ ] 2.5 **emergency-reception** (5 lists, guides, create/show; C) — `resources/views/modules/emergency-reception/*.blade.php`: header, filter card, status codes 1/2/4/6/7 → labels (`lang/*/emergency_reception.php`), nationality code → name if available in `countries` table via existing model, else hide; pagination.hm; guides page (`guide.blade.php`) → hero + numbered step cards (this *is* a sequence) with the orange notice as `fm-callout--warning`.
- [ ] 2.6 **licenses create/edit/show** (B) — `resources/views/licenses/{_form,create,edit,show}.blade.php`, `licenses/partials/page-header.blade.php` → use `figma-subpage-header`; license number → single numeric input (Phase 1.3 covers); branches checkbox list → `fm-checkbox-grid`; file input partial; show page: tab strip scrolls horizontally, green “Complete renewal” → primary indigo (success tone only on the confirmation toast), sidebars stack under main at <1200.
- [ ] 2.7 **service-locations floors** (9 pages; D) — `resources/views/service-locations/{floors,floor-show}.blade.php`, `partials/sl-breadcrumb.blade.php`, css `hm-service-locations.css`: reuse the OPD hero/KPI/cards style; floors grid cards same component as OPD cards; floor order Ground→Basement top-down in both directions; table → `fm-table-scroll`, visit hours column wraps.
- [ ] 2.8 **employee-leave** (dashboard, requests index/create/show; D) — `resources/views/employee-leave/**`, `partials/el-breadcrumb`, `el-page-hero`, css `hm-employee-leave.css`: single hero with primary action, filter card, table pills (`قيد الانتظار` warning), pagination.
- [ ] 2.9 **work-absence notifications list** (D/C) — `resources/views/work-absence-notification/notifications/index.blade.php`, `requests/*`: header via `wan-breadcrumb`→`figma-subpage-header`, native select skin, `نوع التغيب` raw `0/1` → label map from the types table used by the dashboard, date format `Y-m-d` not `May-2026-02`, blue button → `fm-btn--primary`.
- [ ] 2.10 **medical-referrals** (4 lists + create; B) — `resources/views/medical-referrals/{index,create}.blade.php`: hero + breadcrumb per type, filter card, hide empty `مصدر الإشعار` column when all null, labelled actions, empty state, pagination.
- [ ] 2.11 **legacy-office** (coverage, memos, memos-received, holidays, medical-reports, signature; B) — `resources/views/legacy-office/*.blade.php`, `partials/filters.blade.php`: header/filter/table kit; “أرسل إلى” count → chip; “PDF” button → icon+label localized; approvals column → pill + outlined action.
- [ ] 2.12 **health-service-purchase** (`modules/emergency-reception/health-index.blade.php`) — hero, mobile number single input, per-row file inputs → `fm/file-input` compact variant, status select skin.
- [ ] 2.13 **medical-agreements / governmental-services** (`legacy-workflows/**`) — header, KPI, filter, table kit; `_digit-input` keep only for verification codes.
- [ ] 2.14 **training** (`training/management/index.blade.php` shared by both routes) — breadcrumb with parent, count chip, toolbar, pagination; if name/title columns are always empty in data, show `—` with muted style (data issue, note in report).
- [ ] 2.15 **admission-calculator** (`admission-calculator/*`, `admission-inpatient/calculator/*`) — one filled primary in header; native date/select skin; empty state.
- [ ] 2.16 **corporate-communication family** minor: KPI card label `اسم` comes from seeded status names (data) — but the *fallback* label should be the module’s status label when the status name is empty; solid navy pill → tinted (`inspection-visits`, `correspondence`, `government-circulars`, `data-requests`, `outgoing-correspondence`).
- [ ] 2.17 **complaints create / inquiries create** — form kit + numeric inputs (S3) + file partial; complaints index paginate 25 (check `ComplaintsController` for constant).
- [ ] 2.18 **department/emergency performance reports** (`department-performance-reports`, `emergency-performance-reports`) — hero icon renders as a stray glyph above the title (see `_modules_department_reports_collection` shot): fix icon include; empty results area → empty state with “اختر فترة ثم اضغط بحث”.
- [ ] 2.19 **system-administration reference/packages/users forms** — after P0-1, migrate `reference/index` + `form` to the kit (they use `wf-*` already), users `Unknown branch` chip → neutral pill + tooltip.
- [ ] 2.20 **settings** — duplicated section heading “إعدادات الفروع” (copy bug) → check `lang/*/settings.php` keys.

### Phase 3 — polish sweep (after Phase 2)
- [ ] 3.1 Breadcrumb wrapping on mobile (3 lines with long page names): truncate middle crumbs with ellipsis, keep last crumb full.
- [ ] 3.2 OPD cards: all card buttons same tint (first card currently solid).
- [ ] 3.3 Hospital-services section 7 mobile: price row hidden behind image — check `.hs-card__meta` z-index.
- [ ] 3.4 Dashboard: `0% منذ بداية الشهر` on every KPI when growth is null → show “—” muted instead of 0%.
- [ ] 3.5 Sidebar: active item indicator visible in collapsed rail (present) — ensure `aria-current="page"`.
- [ ] 3.6 Footer copy in EN: “Al Hammadi Hospitals — Hospital Management System” OK; AR uses “مستشفيات الحمادي” consistently (login says “لمجموعة مستشفيات الحمادي”) — align to brand name key.
- [ ] 3.7 Reduced motion: `@media (prefers-reduced-motion: reduce)` for `hm-page-transitions.css`.

### Phase 4 — verification (required before reporting)
- [ ] 4.1 `node tools/ui-audit/crawl.mjs SUPER --shots --locales=ar,en --vp=desktop,mobile` → no `HTTP5xx`, no `PHP-ERROR`, no `ovf[` on desktop, `scrollX` = 0 everywhere; review `tools/ui-audit/out/shots` for the migrated modules (spot-check 1 page per module AR+EN, desktop+mobile).
- [ ] 4.2 `node tools/ui-audit/crawl.mjs BRA BRB PERM --locales=ar --vp=desktop,mobile` → same criteria (role-specific pages: employee-services, consents timelines, medical-appointments show).
- [ ] 4.3 `npx playwright test tests/e2e/ui-browser-audit.spec.cjs tests/e2e/audit-roles.spec.cjs` (needs `.env.audit` vars exported) → pass.
- [ ] 4.4 `php artisan test` → pass (views compile; feature tests use sqlite).
- [ ] 4.5 Keyboard pass on login → OTP → dashboard → one list → one form (Tab order, visible focus, Esc closes drawer).
- [ ] 4.6 Final report: what was redesigned per module, what remains (see §5), with screenshot paths.

---

## 4. Progress log
| Date | Phase/Task | Note |
|---|---|---|
| 2026-09-03 | Audit | Complete. 4 roles × ar/en × desktop/mobile crawled; findings above. No product files changed yet except this plan + `tools/ui-audit/`. |

---

## 5. Known remaining issues that are NOT UI work (report them, do not fix)
- Test/seed data leaks into UI: complainant `text`/`test`, subjects `اسم`, hospital `أ1`, absence reasons `test/erewr`, `Pre exiting`, `5 dayas`, branch codes `#17/#6/1l` on the dashboard bar chart, `Playwright official-account training …` notices. These are database rows, not templates.
- `/modules/training/*` rows have empty name/title/ID columns (data mapping in the training repository, backend).
- `hospital_admission_consent_contract_approval.php?id=…` legacy-style public links are reachable from consents lists (intended, legacy parity).
- Duplicate routes (training management/coordination; calculator standard) are by design (permission parity).
