# License / Regulatory Permit Management — Implementation Plan

## Execution step (what this plan actually does)

The deliverable of this task is **one documentation file** — no production code:

> Create `docs/LICENSE_MANAGEMENT_IMPLEMENTATION_PLAN.md` containing the full plan below (Context → Phases → Risks), formatted as a standalone implementation-plan document.

Everything below is the content/design for that document.

---

## Context

The client wants a centralized system to govern all regulatory licenses/permits (التصاريح والتراخيص النظامية) for the Al-Hammad hospitals group (Suwaidi / Nuzha / Olaya branches): track every license, its responsible employee, expiry dates, renewal journeys, payment requests to Finance, alerts/escalations, undertakings, attachments, timeline/audit, dashboard and exports — in Arabic/English, under the Corporate Communication department.

Sources of truth:
- `lisenese/ملخص المشاريع.docx` — **Project 1 section only** (raw client requirements). Project 2 (Official Government Accounts Platform) is **explicitly out of scope**.
- `lisenese/LICENSE_MANAGEMENT_REQUIREMENTS.md` — consolidated + later clarifications; **wins on conflict**. Notably it overrides the DOCX's "ASP.NET WebForms (VB.NET)" deliverable: the module is built **inside the existing Laravel NewProject**, reusing its architecture.

The module integrates into `/home/mohamed/Downloads/medicalLaravel/NewProject`: Laravel 12, Blade-only, custom session auth over legacy MySQL (`ra_users`, session keys `hr_user_id`/`hr_user_level`/`hr_branch_id`/`companies_groups_id`), config-driven permissions and sidebar, per-module Service/Repository/timeline/attachment conventions.

---

## 1. Reuse map — what exists vs. what must be built

### Reuse as-is
| Need | Existing asset |
|---|---|
| Users | `app/Models/User.php` (`ra_users`, PK `hr_id`); `ra_user_branches` for multi-branch users |
| Branches / company scoping | `app/Models/Branch.php`, `app/Support/BranchScope.php::apply()`, `companies_groups_id` filter in every `scopedQuery()` |
| Roles/permissions | `app/Services/Auth/PermissionService.php` + `config/permissions.php` (`modules` auto-registry + `items`), middleware alias `permission:<code>`; level 3 = super admin |
| Sidebar | `config/hm.php` → `navigation.sidebar` + `NavigationService` (config entry only, no code) |
| Email | SMTP + Mailable pattern (`app/Mail/DepartmentReplyLinkMail.php` style, HTML+text pair, synchronous send in try/catch) |
| SMS | `app/Services/Sms/SmsGateway.php` (log/mshastra/synapse, KSA number normalization, `isConfigured()` guard) |
| Multi-channel notify pattern | `app/Services/CorporateCommunications/DepartmentNotificationService.php` (feature-flagged mail+SMS, structured logging) — copy, don't extend |
| Secure downloads | `app/Support/ProtectedFileDownload.php` + ownership-by-traversal authorization (fetch scoped parent → `firstWhere` child) |
| Timeline pattern | `GovernmentInspectionVisitTimeline` shape + `createTimelineEntry()` repo idiom + `show.blade.php` rendering |
| Status lookup pattern | `GovernmentInspectionStatus` shape: `name_ar/name_en/info/publish/ranking`, `localizedName()`, `badgeColor()` |
| Escalation-recipient precedent | `GovernmentCircularSectionAdministrator` (name/email/mobile/publish) — model for escalation group members |
| Scheduler | `bootstrap/app.php` `->withSchedule()` + `hm:*` commands with `--dry-run` (template: `SendCorporateCommunicationDailyReminders` / `DailyReminderService`) |
| PDF export | `app/Services/Pdf/ArabicPdfService.php` (mpdf + ar-php Arabic shaping) |
| Excel/CSV export | hand-rolled streamed pattern from `app/Services/WorkAbsenceNotification/AbsenceNotificationExportService.php` (no maatwebsite) |
| UI | `layouts/app.blade.php` (RTL/LTR aware), `layouts/partials/figma-*` partials, `.gc-*` table/filter/badge classes, `pagination.hm`, Chart.js 4 CDN, Bootstrap 5 modals |
| i18n | `lang/{ar,en}/<module>.php` per module; `dashboard.php` `nav.*` keys; `app/Support/LocaleText.php` |
| Tests | PHPUnit feature-test pattern (sqlite `:memory:`, `Schema::create` legacy tables, `session([...])` fake login); Playwright audit crawls sidebar automatically |

### Must be built (greenfield — no existing equivalent)
1. **All license domain tables/models/services/views** (below).
2. **Finance payment-request workflow** — no finance module or finance role exists; only the `finance_reports` permission category exists in `config/permissions.php`.
3. **Notification Log + in-app notifications** — `hm.notifications` is a static disabled config; no notifications table exists anywhere. We build `license_notifications` as both the per-license Notification Log (required by spec §12) and the in-app store.
4. **Module-scoped audit** — no generic audit trail exists; `license_timeline` with typed events covers spec §19–20 (precedent: `permission_change_logs` for admin-side changes).
5. **Reference-data admin inside the module** — the existing `ReferenceAdminController` is behind `admin` middleware (level 3 only). The Corporate Communication supervisor is *not* necessarily level 3, so authorities/types/escalation-groups management gets module-local pages gated by a `licenses_admin` permission instead of extending `ReferenceAdminController`.

---

## 2. Data model (new tables; legacy naming style, additive migrations)

Migrations follow house rules: `2026_08_30_0000NN_*.php`, anonymous class, `Schema::hasTable()` guards, real `down()`, sqlite-compatible (tests run on sqlite).

**Reference data**
- `license_authorities` — id, companies_groups_id, name_ar, name_en, publish, ranking. (New table, not reuse of `GovernmentCircularIssuingAuthority`, to avoid coupling circulars and licenses.)
- `license_types` — same shape.
- `license_statuses` — lookup seeded: active ساري / near_expiry قريب الانتهاء / under_renewal تحت التجديد / expired منتهي / renewed تم التجديد; `info` = badge hex.
- `license_renewal_stages` — configurable internal renewal stages (seeded starter set: لم يبدأ، جارٍ التجهيز، بانتظار السداد، مقدم للجهة، مكتمل), publish/ranking.

**Core**
- `licenses` — id, companies_groups_id, license_authority_id, license_type_id, license_number (nullable), title (nullable free text), responsible_user_id (→ `ra_users.hr_id`), issue_date, expiry_date, status_id, renewal_stage_id (nullable), notes, publish, created_by, created_at/updated_at.
- `license_branches` — license_id, branch_id (composite unique). Multi-branch link per spec §3.
- `license_undertakings` — id, license_id, user_id, undertaking_text (snapshot of accepted text), status (pending/accepted/escalated), requested_at, accepted_at, escalated_at, ip, user_agent. New row on every (re)assignment; history preserved.
- `license_renewals` — id, license_id, previous_expiry_date, new_expiry_date (nullable until completion), started_at, completed_at, completed_by, notes. Preserves full renewal history (spec §18 "لا تفقد التجديدات السابقة").
- `license_comments` — id, license_id, user_id, body, created_at. No hard delete without audit (soft: publish flag + timeline event).
- `license_attachments` — id, license_id, renewal_id (nullable), payment_request_id (nullable), context (license/renewal/payment/payment_proof/comment/external), file_path, original_name, mime, size, description, uploaded_by, uploaded_at. Validation: pdf/images/xls/xlsx only (spec §13). Storage: disk `local` (private — these are sensitive regulatory documents), path `licenses/{license_id}/Y/m`.
- `license_timeline` — id, license_id (nullable for admin-settings events), event_type (string key: created/assigned/undertaking_accepted/status_changed/stage_changed/comment_added/attachment_uploaded/renewal_started/reminder_sent/yellow/red/escalated/payment_created/payment_status_changed/docs_requested/proof_uploaded/external_communication/renewal_completed/expiry_updated/settings_changed), status_id (nullable), notice, meta (JSON: reference numbers, old/new values), created_by, created_by_type, branch_id, date. This is both Timeline (§19) and the module Audit Trail (§20).
- External communications (§15) = timeline entries with `event_type=external_communication` and meta {reference_no, letter_date, authority, description} + optional attachment (context `external`). No separate table.

**Finance**
- `license_payment_request_statuses` — lookup seeded: received تم استلام الطلب / in_progress تم البدء بإجراءات السداد / needs_documents بحاجة لمرفقات / paid تم السداد.
- `license_payment_requests` — id, license_id, renewal_id (nullable), amount, currency (default SAR), bank_name, account_iban, transfer_details, invoice_number (فاتورة/رقم سداد), notes, status_id, requested_by, created_at, closed_at.
- `license_payment_events` — id, payment_request_id, status_id (nullable), comment, event_type (status_changed/docs_requested/comment/proof_uploaded), created_by, created_at. Payment status history + finance comments.

**Escalation & notifications**
- `license_escalation_groups` — id, companies_groups_id, name, publish.
- `license_escalation_group_members` — id, group_id, user_id (→ `ra_users`; email/mobile read from the user record). Confirmed requirement (§11): members chosen **from inside the system**, receive Email on escalation.
- `license_notifications` — id, license_id (nullable), payment_request_id (nullable), event_type, recipient_user_id (nullable), recipient_email, recipient_mobile, channel (inapp/mail/sms), status (sent/failed/logged), error, reason, read_at (in-app), created_at. Serves spec §12's Notification Log and dedupe source for the scheduler (unique-ish check on license_id+event_type+expiry snapshot in `meta`).

---

## 3. Permissions & roles

`app/Support/Licenses/LicensePermissions.php` constants + `config/permissions.php`:

- `modules` auto-entry: `'modules.licenses.' => ['code' => 'licenses', 'actions' => ['view','create','process','export'], category 'corporate']` → yields `licenses.view/create/process/export` from route names automatically.
- Explicit `items`:
  - `licenses_admin` (category `corporate`) — authorities, types, renewal stages, escalation groups, assignment/reassignment, alert settings. = مشرف التواصل المؤسسي.
  - `licenses_finance` (category `finance_reports`) — finance inbox, payment status changes, doc requests, proof upload. = مدير المالية.

Role mapping (via existing user/group permission admin UI; no new role system):
| Role | Grants |
|---|---|
| مشرف التواصل المؤسسي | licenses.view/create/process/export + licenses_admin |
| المسؤول عن الترخيص | licenses.view/process (record-level: only licenses where `responsible_user_id = session('hr_user_id')` unless licenses_admin) |
| مدير المالية | licenses_finance (+ licenses.view read-only of linked license header) |
| Super Admin (level 3) | everything, automatically |

Scoping in `LicenseRepository::scopedQuery()`: `companies_groups_id` + `BranchScope` **via the `license_branches` pivot** (`whereHas('branches', ...)` — note deviation from the usual single `branch_id` column) + "assigned-to-me" restriction for non-admin users.

---

## 4. Routes & pages

All inside `Route::middleware('auth.session')` → `Route::prefix('modules')->name('modules.')`:

```
Route::prefix('licenses')->name('licenses.')->group():
  GET    /                         index        (list + filters: branch/authority/type/responsible/status/expiry window/search; permission licenses.view)
  GET    /dashboard                dashboard
  GET    /create , POST /          create/store                        (licenses_admin)
  GET    /export/{format}          export (xls|csv|pdf)                (licenses.export)
  GET    /{license}                show   (details hub: data, branches, undertaking, comments, attachments, payments, timeline, notification log)
  GET/PUT /{license}/edit          edit core fields                    (licenses_admin)
  POST   /{license}/assign         reassign responsible                (licenses_admin)
  GET/POST /{license}/undertaking  undertaking gate view + accept
  POST   /{license}/stage          update renewal stage                (assigned user / admin)
  POST   /{license}/comments       add comment
  POST   /{license}/attachments    upload;  GET .../{attachment}/download  (ProtectedFileDownload, ownership-by-traversal)
  POST   /{license}/external-communications   log external contact
  POST   /{license}/renewal/start  begin renewal cycle
  POST   /{license}/renewal/complete  complete renewal (new expiry + new copy upload)
  POST   /{license}/payment-requests  create payment request
  GET    /{license}/pdf            license record PDF
  — admin (licenses_admin):
  resource /admin/authorities, /admin/types, /admin/stages   (module-local CRUD + publish toggle)
  resource /admin/escalation-groups (+ member add/remove)
  — finance (licenses_finance):
  GET  /finance                    finance inbox (payment requests list + filters)
  GET  /finance/{paymentRequest}   detail
  POST /finance/{paymentRequest}/status      (paid ⇒ proof attachment REQUIRED, validated server-side)
  POST /finance/{paymentRequest}/request-documents
  POST /finance/{paymentRequest}/comments
  POST /finance/{paymentRequest}/attachments
```
All id params `->whereNumber()`, catch-all `show` declared last (house rule).

Sidebar (`config/hm.php` `navigation.sidebar`): a `licenses` group under the corporate section — children: التراخيص (index), لوحة التراخيص (dashboard), طلبات السداد (finance, permission `licenses_finance`), إعدادات التراخيص (admin, permission `licenses_admin`). Lang keys in `lang/{ar,en}/dashboard.php` `nav.*` + new `lang/{ar,en}/licenses.php`.

Views: `resources/views/licenses/{index,create,edit,show,undertaking,dashboard,finance/index,finance/show,admin/*,pdf}.blade.php` + `public/css/hm-licenses.css`, copying the `inspection-visits` page skeleton and `.gc-*` components.

---

## 5. Workflows & state transitions

**License status machine** (enforced in `LicenseService`, constants on the update FormRequest per house pattern; scheduler owns date-driven transitions, users own renewal transitions):

```
active ──(≤90d, scheduler)──▶ near_expiry ──(user starts renewal)──▶ under_renewal
active ──(user starts early renewal)──▶ under_renewal
near_expiry/under_renewal ──(expiry passes, scheduler)──▶ expired
under_renewal/expired ──(renewal completed: new expiry saved)──▶ active   (timeline event "renewal_completed"; renewed badge shown from last completed renewal)
```
Alert color (Green/Yellow/Red/Expired) is **derived** from days-to-expiry at render time (>90 / ≤90 / ≤30 / <0), independent of workflow status — matching the spec's traffic-light indicators.

**Renewal stage** is a separate configurable progress field (`license_renewal_stages`) the responsible user advances; every change → timeline + audit.

**Undertaking flow (§8)**
1. Create/reassign ⇒ `license_undertakings` row (pending, snapshot of undertaking text from lang/config) + notify responsible (in-app + mail + SMS).
2. Responsible opens any license page ⇒ `LicenseService::requiresUndertaking()` check in controller ⇒ redirect to `undertaking` view; all write actions on that license blocked until accepted (server-side, not just UI).
3. Accept ⇒ store accepted_at, user, ip/user_agent, text snapshot ⇒ timeline + audit ⇒ unblock.
4. Not accepted within configurable window (`hm.licenses.undertaking_escalation_days`, default 3 **business** days, weekend Fri–Sat) ⇒ scheduler escalates to supervisor(s) + super admin, marks undertaking escalated, logs notification.

**Renewal lifecycle (§9, §18)**
- Start renewal ⇒ open `license_renewals` cycle (previous_expiry snapshot), status → under_renewal.
- During: stage updates, comments, attachments, payment request(s), external communications — all on the open cycle.
- Complete ⇒ form requires new expiry date (> old) and encourages new license copy upload; closes cycle (new_expiry, completed_at/by), updates `licenses.expiry_date` + status → active, resets alert/dedupe state so the 90/60/30 clock restarts from the new date, notifies responsible + supervisor, timeline events (renewal_completed, expiry_updated).

**Alerts & escalation (§10, §11)**
Daily scheduled command `hm:licenses-send-expiry-alerts` (in `bootstrap/app.php` `->withSchedule()`, `dailyAt(config)`, `timezone('Asia/Riyadh')`, `->withoutOverlapping()`, supports `--dry-run`), delegating to `LicenseExpiryAlertService`:
- crossed 90d ⇒ yellow: status → near_expiry (if active), notify responsible + supervisor.
- crossed 60d ⇒ follow-up reminder with remaining time.
- crossed 30d ⇒ red: re-notify + **escalation Email to escalation-group members** + timeline "escalated".
- crossed expiry ⇒ expired: status → expired, notify + escalate; repeat escalation weekly while expired (configurable `hm.licenses.expired_reescalate_days`, default 7 — spec left post-expiry cadence to policy).
- pending undertakings past deadline ⇒ escalate (see above).
- **Dedupe**: before sending, check `license_notifications` for same (license_id, event_type, current expiry_date in meta); renewal completion resets the cycle because expiry_date changes. Thresholds configurable in `config/hm.php` `'licenses' => ['alert_days' => [90, 60, 30], ...]` — not hardcoded (spec §31).

**Finance workflow (§16–17)**
1. Responsible creates payment request (amount, bank/transfer/invoice data, attachments) ⇒ status `received`, notify all `licenses_finance` holders (in-app + mail), timeline on license.
2. Finance opens inbox ⇒ detail ⇒ status transitions: received → in_progress → paid; any state → needs_documents and back. `paid` **requires proof attachment in the same request** (FormRequest rule + service assertion) stored with context `payment_proof`.
3. Finance may request documents / comment / upload ⇒ `license_payment_events` + notification to responsible on every change (spec: إشعار عند كل تغيير) + license timeline entry.
4. Request closes (`closed_at`) on paid; dashboard finance KPIs computed from statuses + avg(closed_at − created_at).

---

## 6. Notifications (§12)

`app/Services/Licenses/LicenseNotificationService` — modeled on `DepartmentNotificationService`:
- Channels: **in-app** (insert `license_notifications` row with recipient_user_id, shown in module "إشعاراتي" panel + unread badge on sidebar/show page), **mail** (one generic `app/Mail/LicenseNotificationMail.php`, HTML+text pair, synchronous try/catch send per house convention), **SMS** (`SmsGateway`; skips gracefully when `!isConfigured()` and logs the failure — spec §27: system must not break when a provider is off).
- Feature flags: `config('hm.licenses.notifications.{enabled,mail,sms}')` from env.
- Every send (success or failure) writes a `license_notifications` row = the required per-license **Notification Log**, rendered as a tab on the show page.
- Global navbar bell: **out of scope for the core phases** (shared component used by all modules; touching it risks regressions). Optional Phase 8 enhancement behind a flag.
- Events covered: create+undertaking request, yellow/60d/red/expired transitions, escalation, undertaking overdue, payment created/status changed/docs requested/proof uploaded, renewal completed/expiry updated — exactly the §12 list.

---

## 7. Dashboard & reports (§23–24)

`LicensesDashboardController` + `LicenseDashboardService` (pattern: `ComplaintsDashboardController`):
- KPI cards (house card contract): total, by-status counts (active/near/under_renewal/expired).
- Chart.js (CDN, canvases): by branch, by authority, by type; expiring in 30/60/90 buckets (bar/trend).
- Top-critical table: nearest expiry + stagnant stage first.
- Finance tiles: open/in-progress/paid/needs-docs counts + avg close time.
- Filters: branch/authority/type/status/date window; all queries via `scopedQuery()` so branch scope and permissions hold.

Exports (`LicenseExportService`, permission `licenses.export`, always through `scopedQuery()`):
- Excel/CSV: streamed `.xls`/`.csv` per `AbsenceNotificationExportService` pattern (licenses by branch/authority/status, near-expiry, expired, under-renewal, payment requests, escalations/notifications report, responsibilities report).
- PDF: `ArabicPdfService` for the license record sheet + list reports.

---

## 8. Tests (§36)

PHPUnit feature tests (house pattern: sqlite `:memory:`, `Schema::create` the new tables in `setUp`, `session([...])` fake login) — `tests/Feature/`:
- `LicenseManagementTest` — create/edit, validation, branch+company scoping, assigned-only visibility, permission denials (403), cross-branch/cross-role access.
- `LicenseUndertakingTest` — pending gate blocks show/write, acceptance snapshot persisted, reassignment creates new pending undertaking, overdue escalation.
- `LicenseLifecycleTest` — scheduler transitions at 90/60/30/expiry (`Carbon::setTestNow`), **duplicate-notification prevention**, renewal start/complete, expiry-date update restarts clock, renewal history preserved.
- `LicensePaymentWorkflowTest` — request creation, finance-only access, status transitions, `paid` without proof rejected, events + notifications recorded.
- `LicenseAttachmentTest` — allowed types only, private storage, download authorization (unauthorized user 404), no path traversal.
- `LicenseDashboardExportTest` — counts correct from seeded data, export respects permission + branch scope.
- Unit: timeline event recording, alert-window calculator, business-day calculator.
- E2E: the existing Playwright `ui-browser-audit.spec.cjs` auto-crawls the new sidebar entries; add a small `licenses.spec.cjs` happy-path (create → undertaking → stage → payment) if time allows.

---

## 9. Implementation phases (safest order — each phase leaves the app shippable)

1. **Foundation** — migrations + seeders (statuses/stages), models, `LicensePermissions`, `config/permissions.php` + `config/hm.php` (settings + sidebar), lang files, module-local reference admin (authorities/types/stages). *No behavior change to any existing module; nav entries invisible without grants.*
2. **Core CRUD** — repository (`scopedQuery` with pivot branch scope), list/filters/create/show/edit, comments, attachments + secure download, timeline recording + rendering.
3. **Undertaking flow** — undertaking table/gate/accept + assignment notifications (in-app + mail + SMS via new `LicenseNotificationService`).
4. **Renewal lifecycle** — renewal cycles, stage updates, complete-renewal flow, status machine, history.
5. **Scheduler & escalation** — escalation groups admin, `hm:licenses-send-expiry-alerts` (+ `--dry-run`), 90/60/30/expired transitions, dedupe, undertaking escalation, schedule registration.
6. **Finance workflow** — payment requests, finance inbox, status transitions with mandatory proof, events, notifications both directions.
7. **Dashboard & reports** — dashboard, Chart.js, exports (xls/csv/pdf).
8. **Hardening & polish** — full test suite green, route-smoke + Playwright audit pass, notification-log tab polish, optional navbar bell integration (flagged), docs.

Each phase ends with `php artisan test` + `php artisan migrate` on a scratch DB + manual smoke on `composer dev`.

---

## 10. Ambiguities, risky assumptions, conflicts (resolve before/during Phase 1)

**Resolved conflicts (per "MD wins" rule)**
- DOCX deliverable says ASP.NET WebForms (VB.NET) + separate MySQL platform → superseded: build **inside NewProject (Laravel)**, existing DB, existing identity/design system.
- "3 أشهر" (DOCX) vs "90 يومًا" (MD scheduler section) → treat 3 months = 90-day window; 2 months = 60 days.

**Assumptions (recommended defaults — flag to client)**
1. **Business days** for the undertaking deadline assume KSA weekend Fri–Sat; window configurable (default 3 business days).
2. **Post-expiry escalation cadence** left "وفق السياسة" in the spec → default weekly re-escalation, configurable.
3. **Escalation-group binding**: spec allows per-license/per-type/policy binding "حسب التصميم النهائي"; confirmed minimum is a system-managed group emailed on escalation → implement company-level group(s) applied to all licenses, schema leaves room for a later per-type/per-license override column.
4. **"تم التجديد" status**: after completion the license returns to **active** with a renewal-completed timeline event and "آخر تجديد" shown; `renewed` exists as a lookup for display/history rather than a resting state. (Alternative: rest at `renewed` until next yellow window — needs client preference.)
5. **Edit rights**: core fields (authority/type/dates/responsible) editable by `licenses_admin` only; responsible user updates stage/comments/attachments/renewal. Spec doesn't say who edits core data.
6. **Currency** defaults to SAR; free-text bank fields (no bank master table).
7. **In-app channel** = module notification panel + log, not the global navbar bell (shared component; deferred/flagged).
8. **New reference tables** (`license_authorities`) rather than reusing `government_circulars` authority table — avoids cross-module coupling; confirm the client doesn't expect one shared authority list.

**Risks**
- **Multi-branch pivot vs. house single-`branch_id` scoping**: `BranchScope` everywhere else filters one column; licenses need `whereHas` on the pivot. Must be watertight in `scopedQuery()` and covered by cross-branch tests, or IDOR/branch leakage follows.
- **No queue infrastructure in use**: notifications send synchronously like the rest of the app; a red-day burst (many licenses × mail+SMS) runs inside the scheduler tick — acceptable at this data volume, revisit queueing only if send counts grow.
- **SMS provider** is `log` by default; real SMS needs env config (mshastra/synapse) — behavior degrades gracefully but the client should know SMS is env-dependent (spec §27 anticipates this).
- **Legacy DB drift**: production schema is a legacy dump; migrations must stay additive + guarded (`Schema::hasTable/hasColumn`) like existing ones, and never touch existing tables.
- **Permission enforcement mode**: default `compat`; verify new codes behave under `strict` mode too (`PermissionRegistry::codesForRoute`).
- **Scope discipline**: nothing from Project 2 (Official Government Accounts Platform) — the DOCX's second half — appears anywhere in this plan or the doc.

---

## Verification (for the doc-writing task itself)

1. `docs/LICENSE_MANAGEMENT_IMPLEMENTATION_PLAN.md` exists, is valid Markdown, covers every section the user listed (reuse / build / data model / permissions / routes / workflows / undertaking / renewal / alerts / finance / attachments / timeline / dashboard / jobs / notifications / tests / phases / ambiguities).
2. It contains no Project-2 content and no production code.
3. Spot-check that every referenced existing file path in the doc actually exists (`app/Support/BranchScope.php`, `app/Services/Sms/SmsGateway.php`, etc.).
