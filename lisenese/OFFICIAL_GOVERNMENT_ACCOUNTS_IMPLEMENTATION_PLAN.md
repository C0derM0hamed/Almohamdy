# Official Government Accounts Platform — Implementation Plan

> **Scope:** Project 2 only (منصة الحسابات الرسمية). Sources: `lisenese/ملخص المشاريع.docx` (Project 2 section, raw client source) and `lisenese/OFFICIAL_GOVERNMENT_ACCOUNTS_REQUIREMENTS.md` (consolidated, authoritative on conflict). Project 1 (licenses) is out of scope.
> **Status:** Approved plan — implementation not started.

## 1. Context

The client needs a governance platform for employees' accounts on external government/semi-government platforms, integrated into the existing NewProject Laravel system under **Corporate Communication**. The system does **not** create accounts: Corporate Communication submits requests to the authority externally; the authority creates/modifies/suspends/closes the account; the platform governs requests/approvals, tracks account records, and documents everything (timeline/audit). No government API exists today. HR API is a future seam only. One employee can have many accounts, including several in the same authority.

## 2. Confirmed business decisions

1. **No password storage.** The system stores username + login URL + reference number only; credentials are delivered outside the system by Corporate Communication. This deliberately deviates from the original DOCX (which wanted the password stored and full credentials emailed) per the consolidated requirements' security recommendation. Client should acknowledge this deviation.
2. **Department-head mapping table.** An admin-managed table maps departments (`branches_departments`) → head users. Requests carry a department; heads see their departments' requests.
3. **Employee undertaking requires login.** The email deep-link leads to login, then an authenticated undertaking page (strong identity proof for a legally meaningful undertaking).
4. **Invitation view tracking uses public per-recipient tokens** (`bin2hex(random_bytes(32))`); opening the public page records Viewed At without requiring login.

## 3. What exists and is reused

The **Licenses module (already implemented)** is the architectural template; the new module registers under the Corporate Communication sidebar group and the `corporate` permission category.

| Concern | Reuse |
|---|---|
| Layering | Controller → FormRequest (typed `payload()`/`filters()`) → Service (transactions, business rules, `assert*()` authorization) → Repository (`scopedQuery()` + `App\Support\BranchScope`) → flat `app/Models` — as in `app/{Http/Controllers/Module,Services,Repositories,Support}/Licenses/` |
| Users | `App\Models\User` (`ra_users`, PK `hr_id`, `activated` enum scope, `displayName()`); session auth (`hr_user_id`, `companies_groups_id`, `hr_branch_id`) via `auth.session` middleware |
| Departments/branches | `App\Models\Branch`, `App\Models\BranchDepartment` (`branches_departments`), `CompanyGroup` tenancy |
| Permissions | `config/permissions.php` items (category `corporate`), `permission:` middleware, `App\Services\Auth\PermissionService`, constants class à la `app/Support/Licenses/LicensePermissions.php`, grants shipped as `updateOrInsert` migration into `user_permission` (see `2026_08_31_000014_...`) |
| Notifications | Per-module notifications table + service à la `license_notifications` + `app/Services/Licenses/LicenseNotificationService.php` (in-app row always; mail/SMS attempted, failures logged, never roll back business writes). Mailables like `app/Mail/LicenseNotificationMail.php` with HTML + `-text` views |
| Undertakings | `license_undertakings` pattern (status, accepted_at, ip, user_agent) + `AcceptLicenseUndertakingRequest` |
| Timeline/audit | `license_timeline` shape (`event_type` string(60), `status_id`, `notice`, json `meta`, `created_by`, `created_by_type`, `branch_id`, `date`) written via `recordTimeline()` in service |
| Attachments | `license_attachments` pattern: private `local` disk, `context` column, streamed download with tenant+permission checks (`LicenseService::downloadAttachment`), behavior contract in `tests/Feature/ProtectedCorporateDownloadsTest.php` |
| Reference admin | `LicenseAdminController` generic reference CRUD (`REFERENCE_MODELS` map, `->defaults('reference', …)` route loop, shared `_reference-index/_reference-form` blades, `SaveLicenseReferenceRequest`, idempotent seeder `LicenseReferenceSeeder`) |
| State machine | `LicensePaymentService::TRANSITIONS` const map; invalid transition → `ValidationException` |
| Exports/PDF | Hand-rolled CSV/XLS `StreamedResponse` à la `LicenseExportService` / `AbsenceNotificationExportService`; `ArabicPdfService` (dompdf) if PDF needed |
| Dashboard | Module dashboard service à la `LicenseDashboardService` / `ComplaintsDashboardController` |
| Public token links | Public route group `routes/web.php:71-110` + opaque token columns (`hash_equals` compare) — for invitation view links |
| Sidebar/UI | `config/hm.php` `navigation.sidebar` corporate group (~line 281) nested group; labels in `lang/{ar,en}/dashboard.php` `nav`; blades follow `resources/views/licenses/` style; RTL/locale via `SetLocale` + `layouts.app` |
| Scheduler | `bootstrap/app.php` `withSchedule()` — for the future HR-refresh command |
| Tests | PHPUnit, sqlite `:memory:`, hand-built legacy schema + module migrations, session-based auth faking, permission grants — copy `tests/Feature/LicenseModuleTestCase.php` |

**Must be built new:** everything module-specific below. Nothing generic needs inventing.

## 4. Data model

New migrations `database/migrations/2026_09_01_0000NN_create_gov_account_*.php` following Licenses conventions: `increments('id')`, no FK constraints (legacy DB has none), `if (! Schema::hasTable(...))` idempotency, `publish` boolean instead of deletes, `companies_groups_id` scoping, string status codes + display lookup tables, named composite indexes. Plus `database/seeders/GovAccountReferenceSeeder.php` (sample authorities/services/roles + statuses; idempotent, never truncating).

### Reference tables (all: `name_ar`, `name_en`, `publish`, `ranking`, `companies_groups_id`, timestamps)

- `gov_account_authorities` — government/official authorities (manageable list, never hardcoded)
- `gov_account_services` (+ `authority_id`) — services per authority
- `gov_account_roles` — external account roles (مدقق/معتمد/مشرف…), extensible
- `gov_account_department_heads` (`department_id` → `branches_departments.id`, `user_id` → `ra_users.hr_id`, `publish`; unique `(department_id, user_id)`)
- `gov_account_request_statuses`, `gov_account_statuses` (`code`, `name_ar`, `name_en`, `ranking`, `info` = badge hex, `publish`) — display lookups; logic keys off string `code`

### Core tables

- **`gov_account_requests`**: `companies_groups_id`, `branch_id`, `type` string(30) (`create|modify|permission_change|suspend|close`), `status` string(30), `origin` string(20) (`department|hr|corporate`), `employee_user_id`, `department_id`, `authority_id`, `service_id`, `role_id`, `requested_role_id` nullable (permission change), `account_id` nullable (lifecycle requests), `justification` text, `notes`, `round` unsignedInteger default 1, `rejection_reason` text nullable, `reviewed_by`/`reviewed_at` nullable, `authority_submitted_at`/`authority_submitted_by`/`authority_reference` nullable, `created_by`, timestamps. Indexes on scope+status, employee, account. **No uniqueness across employee/authority** (multi-account requirement).
- **`gov_account_undertakings`**: `request_id`, `kind` string(20) (`manager|employee`), `user_id`, `undertaking_text` text (snapshot of wording at acceptance time), `status` (`pending|accepted`), `requested_at`, `accepted_at`, `ip`, `user_agent`.
- **`gov_accounts`**: `companies_groups_id`, `branch_id`, `employee_user_id`, `authority_id`, `service_id`, `role_id`, `username`, `login_url` nullable, `reference_no` nullable, `status` string(30), `created_from_request_id`, `managed_by` nullable, `account_created_at` date, `suspended_at`/`closed_at`/`closed_reason` nullable, `notes`, timestamps. **No password column.**
- **`gov_account_timeline`**: license_timeline shape + nullable `request_id`, `account_id`, `notice_id`.
- **`gov_account_attachments`**: nullable `request_id`/`account_id`/`notice_id`, `context` string(40), `file_path`, `original_name`, `mime` (PDF/images/Excel whitelist enforced in FormRequests), `size`, `description`, `uploaded_by`, `uploaded_at`. Private `local` disk.
- **`gov_account_notifications`**: mirrors `license_notifications` (nullable `request_id`/`account_id`/`notice_id`, `event_type`, recipient fields, `channel` in `inapp|mail|sms`, `status`, `error`, json `meta`, `read_at`).

### Invitations (meetings/training)

- **`gov_account_notices`**: `companies_groups_id`, `title`, `authority_id`, `service_id` nullable, `description`, `event_date`, `event_time`, `meeting_url`, `attendance_method` string(20) (`online|in_person|hybrid`), `location`, `notes`, json `targeting` (mode + ids), `created_by`, `sent_at`, `publish`, timestamps.
- **`gov_account_notice_recipients`**: `notice_id`, `user_id`, `email`, `token` string(64) unique, `sent_at`, `viewed_at` nullable, `view_count`, `last_viewed_at`. Duplicate opens update `last_viewed_at`/count; never rewrite the first `viewed_at`.

Also: a permission-grant migration (like the Licenses one) granting all module codes to the existing test users.

## 5. Permission matrix

Constants in `app/Support/GovAccounts/GovAccountPermissions.php`; entries in `config/permissions.php` under category `corporate` with full `routes` lists.

| Code | Who | Grants |
|---|---|---|
| `gov_accounts.view` | Corporate Comm staff | Read module lists/dashboards/accounts within company/branch scope |
| `gov_accounts.request` | Department heads | Create/edit/resubmit requests **only for departments where they appear in `gov_account_department_heads`**; see those departments' requests |
| `gov_accounts.process` | Corporate Comm reviewers | Approve/reject, mark submitted-to-authority, complete (record account), manage notices |
| `gov_accounts.hr` | HR | HR search screen + raise close/suspend requests |
| `gov_accounts.export` | Reporting | Exports |
| `gov_accounts_admin` | Module admin | Reference data (authorities/services/roles/department-heads) + everything above |

Employee self-service pages (my undertakings / my accounts) need only `auth.session`; every query hard-scoped to `session('hr_user_id')`. Security rules (per requirements §45): services re-assert authorization (`assertCanProcess()` etc.) and scope (companies_groups_id + BranchScope + department-head check) server-side, never UI-only; GET never mutates; all state changes are POST/PATCH with CSRF; every transition validated against a `TRANSITIONS` const map; IDOR guarded by loading through scoped queries (tests cover cross-user/department access). Super Admin follows the existing `hm.permissions.admin_levels` behavior.

## 6. Workflows / state machines

### Create request (`type=create`)

```
draft → awaiting_employee        (head submits; manager undertaking accepted at submit — mandatory, snapshot stored)
awaiting_employee → under_review (employee logs in via email deep-link, accepts employee undertaking)
under_review → approved | rejected   (Corporate Comm; reject requires a reason)
rejected → under_review          (head reads reason, edits data, writes response, resubmits; round++)
draft|rejected → cancelled (head) ; under_review → cancelled (processor)
approved → submitted_to_authority    (records date, authority_reference, notes, attachments, actor)
submitted_to_authority → completed   (processor records actual account: username, login_url, role, dates
                                      → creates gov_accounts row, status=active)
```

On `completed`: notify the employee (account ready + username + login URL — **no credentials**) and the department head (request executed, no sensitive data). Every submission/rejection/response/resubmission round is preserved in the timeline.

**Manager undertaking content** (per §11): employee trained, aware of the service, questions answered; head responsible for raising suspend/modify requests when the employee's department/role/employment changes. **Employee undertaking content** (per §12): training received, questions answered, confidentiality, work-purpose-only use, no credential sharing. Both store: text snapshot, user, timestamp, IP/user-agent.

### Lifecycle requests (`modify | permission_change | suspend | close`)

Created from an account's show page by head, HR (`close|suspend`), or processor:

```
under_review → approved | rejected ; rejected → under_review (respond + resubmit) ; → cancelled
approved → submitted_to_authority → completed
```

No employee undertaking for lifecycle requests. On submission the linked account gets an in-flight status (`modification_requested|suspension_requested|closure_requested`); on `completed` the service applies the change (role old→new stored in timeline `meta`; `suspended_at`; or `closed_at` + reason) → account `active|suspended|closed`; on reject/cancel the account reverts to its prior status (stored in request meta). Account statuses: `pending, active, modification_requested, suspension_requested, suspended, closure_requested, closed`. **Accounts are never edited silently** — every change flows through a request + timeline.

### HR workflow

Screen to search employees (name/username) → list open accounts (authority, service, status) → raise close/suspend request with reason (`origin=hr`) → goes to Corporate Communication like any lifecycle request.

### Future HR API seam (no fake API, per §26/§40)

`app/Services/GovAccounts/EmployeeStatusProviderInterface` with a `NullEmployeeStatusProvider` binding + a scheduled command skeleton `hm:gov-accounts-review-employee-status` that, when a real provider is bound, flags accounts of non-active employees by creating action-required notifications for Corporate Comm/HR — **never auto-closing**. Ship interface + command + config flag (disabled) + unit test with a fake provider.

### Invitations (notices, §28–§33)

Processor creates notice → picks targeting (all module users / specific users / departments / users holding accounts on a service) → send resolves recipients into rows with unique tokens → emails each (Mailable with HTML+text views) with public link `/gov-account-notices/view/{token}` → public controller (pattern of `app/Http/Controllers/PublicForms/`) `hash_equals`-matches the token, records the view, shows invitation details only (no other recipients' data). Recipient status board on the notice show page: Sent / Viewed (+timestamp) / Not Viewed. View-only — no accept/reject workflow.

## 7. Routes & pages

`routes/gov-accounts.php`, `require`d in `routes/web.php` (~line 118); prefix `modules/gov-accounts`, names `modules.gov-accounts.*`; `whereNumber` on params; static segments before catch-alls.

- **Dashboard** `/dashboard` — KPI cards per §36 (totals by account status, requests by state/type, multi-account employees, notices sent/view rate) via `GovAccountDashboardService`.
- **Accounts** — index (filters: employee/department/authority/service/role/status), show (details + lifecycle request buttons + sub-requests + timeline + attachments), export.
- **Requests** — index (filters: type/status/requester/employee/authority/service/date), create (create-type), show (timeline, rounds, per-state per-permission actions), edit (draft/rejected); POST actions: submit, cancel, approve, reject, resubmit, mark-authority-submission, complete (with account payload); attachment upload/download.
- **Undertakings** — `/undertakings` (my pending), accept action (authenticated).
- **My accounts** — `/my-accounts` (employee self-service).
- **HR** — `/hr/accounts` search + close/suspend request creation.
- **Admin** — reference CRUD route loop for `authorities|services|roles|department-heads` (LicenseAdmin pattern).
- **Notices** — index/create/store/show/send + recipient statuses; public `GET /gov-account-notices/view/{token}` in the public route group.
- **Notifications** — inbox + mark-read (license pattern).
- **Sidebar** — nested group under the corporate group in `config/hm.php` (children: dashboard, accounts, requests, my area, HR, notices, admin) gated by permission codes; nav labels in `lang/{ar,en}/dashboard.php`; add the module code to `CorporateCommunicationDashboardController::targets()`.
- **Views** in `resources/views/gov-accounts/…`; **lang** files `lang/{ar,en}/gov_accounts.php` — all UI bilingual, RTL/LTR, responsive within the existing shell.

## 8. Notifications (events → recipients)

Via `GovAccountNotificationService` (in-app row always; mail best-effort; SMS off by default via a `config/hm.php` `gov_accounts` block):

| Event | Recipients |
|---|---|
| Request submitted | Employee (undertaking deep-link) |
| Employee confirmed undertaking | Processors |
| Rejected (with reason) | Department head |
| Resubmitted | Processors |
| Approved / submitted to authority | Department head |
| Completed (account created) | Employee (no credentials) + head (no sensitive data) |
| Lifecycle request created | Processors (+ head when HR-originated) |
| Lifecycle executed (modify/suspend/close) | Head + employee |
| Notice send | Recipients (tokenized link) |

Processor pool = users holding `gov_accounts.process` (queried from permission tables, as the licenses escalation notifications do). Transport failures are logged per-channel and never roll back business writes.

## 9. Implementation phases (each ends green: `php artisan test`)

1. **Foundation** — migrations + seeder + models + `GovAccountPermissions` + `config/permissions.php` + sidebar + lang skeletons + reference admin (authorities/services/roles/department-heads) + `GovAccountAdminTest`. Additive only; no workflow yet.
2. **Create-account workflow end-to-end** — request CRUD, manager undertaking, employee undertaking page, review approve/reject, respond/resubmit rounds, authority submission, completion → account record; timeline, attachments (secure download), notifications; `GovAccountModuleTestCase` + `GovAccountCreateWorkflowTest` (incl. multi-account, same-authority multi-account, IDOR/scope, transition-guard tests).
3. **Lifecycle requests + HR** — modify/permission-change/suspend/close flows, account status transitions incl. revert-on-reject, HR search screen + close request; `GovAccountLifecycleTest`, `GovAccountHrTest`.
4. **Notices & view tracking** — notice CRUD, targeting resolution, mail send, public token view endpoint, recipient status board, duplicate-view handling; `GovAccountNoticeTest`.
5. **Dashboard, reports/exports, HR seam, polish** — dashboard service + view, CSV/XLS exports with export-permission FormRequests, `EmployeeStatusProviderInterface` + command skeleton + test, AR/EN sweep, route-smoke additions, permission-grant migration, final doc update.

## 10. Risks / ambiguities

- **Employee↔department link doesn't exist on `ra_users`** — the request form captures the department explicitly (head picks from departments they head); "by department" reports reflect request-time department, not live org data. Flag to client.
- **Credential delivery happens outside the system** (decided) — client must acknowledge the deviation from the original DOCX.
- Account role list starts minimal (seeded مدقق/معتمد/مشرف) and is extensible via admin — per client clarification.
- Status names simplified vs the §19 list — mapping documented above.
- Legacy DB (MyISAM, no FKs): integrity is application-level; deployment needs a DB backup window (same caveat as the Licenses rollout).
- "Service users" notice targeting interpreted as employees holding an account on that service.
- Email deliverability depends on production mail config; failures logged per-channel, never block workflow.

## 11. Verification

- `php artisan test --testsuite=Feature` — new `GovAccount*` suites + existing suites stay green; tests follow the `LicenseModuleTestCase` harness (sqlite `:memory:`, session auth faking, permission grants, `Mail::fake`, `Storage::fake`).
- `php artisan route:list | grep gov-accounts` + route smoke audit.
- Manual E2E on the dev MySQL DB against the acceptance checklist (§48): admin creates authority/service → head creates request → employee undertaking → reject → resubmit → approve → authority submission → complete → account visible under employee (×2 accounts, same authority) → modify/suspend/close flows → HR search/close → notice send → open token link → Viewed recorded → AR/EN + mobile smoke (existing Playwright 6-viewport harness optional).
