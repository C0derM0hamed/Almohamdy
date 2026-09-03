# Official Government Accounts — Execution Tracker

> Permanent handoff record for the Project 2 implementation. Update this file after every atomic task. Resume from the first item whose status is not `COMPLETE`.

## Current execution state

- Overall status: `COMPLETE — all five approved phases and final verification finished`
- Last updated: `2026-09-01` (Africa/Cairo)
- First unfinished task: `NONE`
- Exact next step: no implementation work remains; review the completed module and tracker, then deploy only through the client's separately approved release process.

## Source review

### P0.1 — Read authoritative requirements and original Project 2 brief

- Status: `COMPLETE`
- What changed: no application code changed; reviewed the full Project 2 requirements and extracted/read the Project 2 section from the original DOCX.
- Important files:
  - `lisenese/OFFICIAL_GOVERNMENT_ACCOUNTS_REQUIREMENTS.md`
  - `lisenese/ملخص المشاريع.docx` (Project 2 section only)
- Tests/results:
  - Confirmed the requirements file is the Project 2 source of truth.
  - Confirmed the system governs internal requests, approvals, external follow-up, accounts, notices, audit, and reporting; it must not simulate a government integration.
  - Confirmed one employee may have multiple independent accounts, including multiple accounts for the same authority.
  - Confirmed employee and department-head undertakings, rejection/resubmission, corporate review, HR closure/suspension requests, secure attachments, and notice view tracking are required.
  - Confirmed password storage is not approved by the source of truth and must not be implemented as a readable password vault.
- Remaining work: read the approved Project 2 implementation plan and use its approved decisions/phases as the implementation authority.
- Exact next step: complete P0.2.

### P0.2 — Read and baseline the approved Official Government Accounts implementation plan

- Status: `COMPLETE`
- What changed: located and read both approved-plan copies; verified they are byte-for-byte identical (`sha256 ae10b4406adff01a47434472a9b3210568db987813e510c3a6bc6368545816b3`). Recorded the five approved phases and confirmed business decisions below.
- Important files checked:
  - `AUTONOMOUS_IMPLEMENTATION_PLAN.md` — unrelated whole-project migration plan; not a Project 2 plan.
  - `FULL_MIGRATION_IMPLEMENTATION_PLAN.md` — unrelated migration plan.
  - `lisenese/license-regulatory-permit-management-implementation-plan.md` — Project 1 only and explicitly states Project 2 is out of scope.
  - `lisenese/OFFICIAL_GOVERNMENT_ACCOUNTS_REQUIREMENTS.md` — requirements only; section 50 requires a separate plan to be prepared, reviewed, and approved before implementation.
- Approved plan files:
  - `lisenese/OFFICIAL_GOVERNMENT_ACCOUNTS_IMPLEMENTATION_PLAN.md`
  - `docs/OFFICIAL_GOVERNMENT_ACCOUNTS_IMPLEMENTATION_PLAN.md`
- Tests/results: both copies contain 183 lines and have the same SHA-256 digest.
- Remaining work: phases P1–P5, final relevant test suite, and Playwright verification.
- Exact next step: complete P1.1.

## Implementation phases

### P1 — Foundation

- Status: `COMPLETE — accepted baseline exception recorded by user`
- Scope: migrations, reference seeder, models, permission constants/config, sidebar, bilingual language skeletons, reference admin for authorities/services/roles/department-heads, and `GovAccountAdminTest`.
- What changed:
  - Added four additive/idempotent/reversible migrations covering all approved reference, request, account, undertaking, timeline, attachment, notification, notice, and recipient tables. There is deliberately no password/secret column and no employee/authority uniqueness constraint.
  - Added the complete flat Eloquent model set and relationships.
  - Added an idempotent non-truncating reference seeder for starter authorities/services/roles plus request/account statuses.
  - Added `GovAccountPermissions`, all six permission catalog entries, Corporate Communication navigation, bilingual language skeletons, and Corporate Communication landing target.
  - Added company/branch-scoped reference administration for authorities, services, roles, and department-head mappings, including server-side direct-request scope checks and publish toggles.
  - Added 25 authenticated/admin-protected reference routes and responsive bilingual views reusing the License module shell/styles.
- Important files:
  - `database/migrations/2026_09_01_000015_create_gov_account_reference_tables.php`
  - `database/migrations/2026_09_01_000016_create_gov_account_core_tables.php`
  - `database/migrations/2026_09_01_000017_create_gov_account_activity_tables.php`
  - `database/migrations/2026_09_01_000018_create_gov_account_notice_tables.php`
  - `database/seeders/GovAccountReferenceSeeder.php`
  - `app/Models/GovAccount*.php`
  - `app/Support/GovAccounts/GovAccountPermissions.php`
  - `app/Services/GovAccounts/GovAccountAdminService.php`
  - `app/Http/Controllers/Module/GovAccounts/GovAccountAdminController.php`
  - `app/Http/Requests/GovAccounts/SaveGovAccountReferenceRequest.php`
  - `routes/gov-accounts.php`, `routes/web.php`
  - `config/permissions.php`, `config/hm.php`
  - `resources/views/gov-accounts/admin/*`
  - `lang/{ar,en}/gov_accounts.php`, `lang/{ar,en}/dashboard.php`
  - `tests/Feature/GovAccountModuleTestCase.php`, `tests/Feature/GovAccountAdminTest.php`
- Tests/results:
  - Pre-change License baseline: `11 passed (215 assertions)`.
  - Focused Government Accounts admin suite: `4 passed (23 assertions)` including rendered pages, migration idempotency/rollback, no password/secret, seeder idempotency, CRUD, permission denial, and cross-scope rejection.
  - Government Accounts + both License suites: `15 passed (240 assertions)`.
  - Route inventory: `25` Government Accounts routes registered.
  - Full `php artisan test`: `135 passed, 1 failed (1069 assertions)`. Sole failure is `Tests\\Feature\\AdmissionCalculatorTest`: its SQLite fixture lacks `admission_rooms.name_en`, while pre-existing user-owned `AdmissionCalculatorService` selects that column. Running that test alone reproduces the same failure. No Government Accounts or License test failed.
- Remaining work: P2–P5 and final verification. The unrelated Admission Calculator fixture failure is an explicitly accepted pre-existing baseline exception and is not a Government Accounts regression.
- Exact next step: begin P2.1; do not modify Admission Calculator code or fixtures unless this module causes a regression there.

### P2 — Create-account workflow end-to-end

- Status: `COMPLETE`
- Scope: request CRUD/state machine, both undertakings, corporate review/rejection/resubmission, authority submission, completion/account creation, timeline, secure attachments, notifications, multi-account and authorization tests.
- What changed:
  - Added company/branch/department-scoped repositories and server-side permission re-assertion.
  - Added draft creation/editing, manager undertaking snapshot at submit, authenticated employee undertaking, corporate review, reasoned rejection, response/resubmission rounds, approval, external-authority submission, and completion into an independent active account record.
  - Added explicit transition guards, transaction boundaries, complete timeline events, protected PDF/image/Excel attachments on the private local disk, and parent/scope-checked downloads.
  - Added in-app notifications, best-effort email logging, direct/group processor recipient resolution, personal inbox/read tracking, and HTML/text emails that never include passwords.
  - Added bilingual responsive request/account/employee pages and Corporate Communication navigation.
- Important files:
  - `app/Repositories/GovAccounts/GovAccountRepository.php`
  - `app/Services/GovAccounts/GovAccountRequestService.php`
  - `app/Services/GovAccounts/GovAccountNotificationService.php`
  - `app/Http/Controllers/Module/GovAccounts/*`
  - `app/Http/Requests/GovAccounts/*`
  - `app/Mail/GovAccountNotificationMail.php`
  - `routes/gov-accounts.php`
  - `resources/views/gov-accounts/{requests,accounts,self}/*`
  - `resources/views/emails/gov-accounts/*`
  - `tests/Feature/GovAccountCreateWorkflowTest.php`
- Tests/results:
  - Focused Phase 2 suite: `7 passed (66 assertions)` across Government Accounts foundation/workflow.
  - Government Accounts + License + protected downloads + legacy navigation: `35 passed (474 assertions)`.
  - Full suite: `138 passed (1119 assertions)`, with only the accepted pre-existing `AdmissionCalculatorTest` exception.
  - Verified two independent accounts for the same employee in the same authority, rejection/resubmission history, invalid transitions, cross-company IDOR, attachment parent scope, notification inbox IDOR, and rendered AR/EN-backed pages.
- Remaining work: P3–P5 and final Playwright verification.
- Exact next step: start P3.1 lifecycle state machine.

### P3 — Lifecycle requests and HR

- Status: `COMPLETE`
- Scope: modify/permission-change/suspend/close flows, status/revert behavior, HR search and requests, focused tests.
- What changed:
  - Added request-driven modify, permission-change, suspend, and close workflows from account records.
  - Added explicit in-flight account statuses, prior-status metadata, reject/cancel reversion, resubmission restoration, and completion application with before/after timeline metadata.
  - Added branch/company/department authorization for heads/processors and HR-only branch/company-scoped open-account search.
  - Restricted HR to suspension/closure requests; no direct or automatic account closure is possible.
  - Added bilingual lifecycle and HR interfaces and permission/navigation wiring.
- Important files:
  - `app/Services/GovAccounts/GovAccountRequestService.php`
  - `app/Http/Requests/GovAccounts/StoreGovAccountLifecycleRequest.php`
  - `app/Http/Controllers/Module/GovAccounts/GovAccountLifecycleController.php`
  - `app/Http/Controllers/Module/GovAccounts/GovAccountHrController.php`
  - `resources/views/gov-accounts/hr/index.blade.php`
  - `tests/Feature/GovAccountLifecycleTest.php`
  - `tests/Feature/GovAccountHrTest.php`
- Tests/results:
  - Government Accounts + License + legacy navigation: `36 passed (518 assertions)`.
  - Full suite: `144 passed (1187 assertions)`, with only the accepted pre-existing `AdmissionCalculatorTest` exception.
  - Verified permission changes (including reject/revert/resubmit), modification, suspension, closure, timeline before/after metadata, HR type restrictions, search, and cross-company IDOR.
- Remaining work: P4–P5 and final Playwright verification.
- Exact next step: begin P4.1 notices and token tracking.

### P4 — Notices and view tracking

- Status: `COMPLETE`
- Scope: notice CRUD/targeting/mail, tokenized public viewing, recipient status board, duplicate-view behavior, focused tests.
- What changed:
  - Added processor-only, company-scoped notice list/create/store/show/send workflow with bilingual responsive pages and recipient status board.
  - Added server-side target resolution for all active company users, explicitly selected users, request-time departments, and employees holding an account on the selected service. Inactive and cross-company users are excluded.
  - Added recipient materialization with stable unique 64-character opaque tokens, in-app notification rows, HTML/text mailables, and best-effort mail logging that does not roll back notice delivery records.
  - Added the unauthenticated `/gov-account-notices/view/{token}` endpoint. It performs an indexed exact lookup plus `hash_equals`, exposes invitation details only, preserves the first `viewed_at`, and increments `view_count`/`last_viewed_at` on every open.
  - Added complete permission catalog, sidebar, and AR/EN label wiring for notices.
- Important files:
  - `app/Services/GovAccounts/GovAccountNoticeService.php`
  - `app/Http/Requests/GovAccounts/SaveGovAccountNoticeRequest.php`
  - `app/Http/Controllers/Module/GovAccounts/GovAccountNoticeController.php`
  - `app/Http/Controllers/PublicForms/GovAccountNoticePublicController.php`
  - `app/Mail/GovAccountNoticeMail.php`
  - `routes/gov-accounts.php`, `routes/web.php`
  - `resources/views/gov-accounts/notices/*`
  - `resources/views/emails/gov-accounts/notice*.blade.php`
  - `config/permissions.php`, `config/hm.php`
  - `lang/{ar,en}/gov_accounts.php`, `lang/{ar,en}/dashboard.php`
  - `tests/Feature/GovAccountNoticeTest.php`
- Tests/results:
  - Phase 4 Government Accounts + License + legacy navigation regression set: `40 passed (555 assertions)`.
  - Full suite: `148 passed, 1 failed (1224 assertions)`. The sole failure remains the user-accepted pre-existing `AdmissionCalculatorTest` SQLite fixture exception (`admission_rooms.name_en` absent); no Government Accounts, License, or navigation test failed.
  - Verified all four targeting modes, recipient uniqueness, token length/uniqueness, mail dispatch, in-app records, permission denial, cross-company IDOR, public-link privacy, invalid-token 404, first-view timestamp preservation, and duplicate-view increments.
- Remaining work: P5 dashboard, reports/exports, disabled HR-provider seam/command, permission-grant migration, bilingual/route polish, and final Playwright verification.
- Exact next step: implement P5.1 scoped dashboard KPIs and page.

### P5 — Dashboard, reports/exports, HR seam, polish

- Status: `COMPLETE`
- Scope: dashboard, CSV/XLS exports, disabled future HR provider seam and command, permission-grant migration, AR/EN and route-smoke sweep, final documentation.
- Completed tasks:
  - `P5.1 COMPLETE` — Added branch/company-scoped account and request KPIs, status/type breakdowns, multi-account employee count, and company-scoped notice send/view metrics and view rate. Added the bilingual dashboard page, controller, route, permission catalog, and sidebar entry.
  - `P5.2 COMPLETE` — Added permission-gated UTF-8 CSV and SpreadsheetML XLS reports for scoped accounts, requests, and notice/view recipients. Added report/type/status/reference/employee/date filters, private scope reuse, bilingual headers, and dashboard download links.
  - `P5.3 COMPLETE` — Added the future HR provider interface with a null default binding, a config-disabled scheduled review command, deduplicated processor/HR action-required notifications, and an explicit no-auto-change contract. No government/HR integration is simulated.
  - `P5.4 COMPLETE` — Added the approved idempotent/non-destructive permission grant migration; completed operational-route administrator coverage while preserving auth-only employee self-service; corrected module navigation targets; completed missing lifecycle/action AR/EN strings; added recursive translation parity, route coverage, bilingual rendering, and module-admin smoke tests.
- Important files for P5.1:
  - `app/Services/GovAccounts/GovAccountDashboardService.php`
  - `app/Http/Controllers/Module/GovAccounts/GovAccountDashboardController.php`
  - `resources/views/gov-accounts/dashboard.blade.php`
  - `tests/Feature/GovAccountDashboardTest.php`
  - `app/Services/GovAccounts/GovAccountExportService.php`
  - `app/Http/Requests/GovAccounts/GovAccountExportRequest.php`
  - `app/Http/Controllers/Module/GovAccounts/GovAccountExportController.php`
  - `tests/Feature/GovAccountExportTest.php`
  - `app/Services/GovAccounts/{EmployeeStatusProviderInterface,NullEmployeeStatusProvider,GovAccountEmployeeStatusReviewService}.php`
  - `app/Console/Commands/ReviewGovAccountEmployeeStatus.php`
  - `app/Providers/AppServiceProvider.php`, `bootstrap/app.php`, `config/hm.php`
  - `tests/Feature/GovAccountEmployeeStatusReviewTest.php`
  - `database/migrations/2026_09_01_000019_grant_gov_account_permissions_to_approved_test_users.php`
  - `tests/Feature/GovAccountPolishTest.php`
- Tests/results for P5.1:
  - Dashboard + legacy navigation: `14 passed (177 assertions)`.
  - Verified branch exclusion, company notice exclusion, multi-account counting, request status/type breakdown, view rate, rendered English page, and permission denial.
  - Export + dashboard + License reporting regression: `9 passed (100 assertions)`.
  - Verified UTF-8 CSV, valid SpreadsheetML workbook output, request filters, branch/company exclusion, notice view status/count, unsupported formats, and export permission denial.
  - HR seam + create workflow + License scheduler regression: `10 passed (128 assertions)`.
  - Verified disabled-by-default behavior, fake-provider substitution, one-time action-required notices to processor and HR holders, repeated-run deduplication, and unchanged active account state.
  - Complete Government Accounts + License + protected-download + legacy-navigation regression set: `54 passed (697 assertions)`.
  - Full suite after Phase 5: `157 passed, 1 failed (1342 assertions)`. The sole failure remains the accepted pre-existing `AdmissionCalculatorTest` fixture exception; Government Accounts, License Management, protected downloads, and navigation are green.
- Remaining work: final route inventory and Playwright desktop/mobile AR/EN verification, then final tracker closure.
- Exact next step: begin FV.1 Playwright verification.

### Final verification

- Status: `COMPLETE`
- Scope: final relevant PHPUnit tests, route audit, approved acceptance workflow, Playwright desktop/mobile Arabic/English verification.
- What changed/results:
  - Added `tests/e2e/official-government-accounts.spec.cjs` and verified authenticated dashboard/accounts/requests/notices/admin pages, notice create/send, opaque public recipient link, Viewed status reflection, CSV download, English/LTR and Arabic/RTL, and horizontal-overflow/nested-shell checks.
  - First Playwright pass exposed a real deployed legacy-schema difference: `branches_departments` may omit `branch_id` and `publish`. Updated notice option/validation queries to conditionally use those columns, matching existing NewProject patterns, and added a dedicated regression test for the three-column legacy table shape.
  - Final focused/relevant regression after the compatibility fix: `55 passed (701 assertions)`.
  - Final Playwright: desktop Chromium `PASS`; Pixel 7 mobile Chromium `PASS` (`2 passed`, Arabic and English exercised in both).
  - Final Government Accounts route inventory: `59` authenticated module routes plus the public token view route.
  - Latest full suite: `157 passed, 1 failed (1342 assertions)` before the final narrowly scoped compatibility fix; the post-fix relevant suite is fully green. The only full-suite failure is still the accepted pre-existing `AdmissionCalculatorTest` fixture exception.
  - Applied only migrations `000015`–`000019` and the idempotent Government Accounts reference seeder to the local development database to support browser verification. No deployment was performed.
- Important files:
  - `tests/e2e/official-government-accounts.spec.cjs`
  - `tests/Feature/GovAccountNoticeTest.php`
  - `app/Services/GovAccounts/GovAccountNoticeService.php`
  - `playwright.config.cjs` (existing harness, unchanged)
- Remaining work: none within the approved implementation plan. Production rollout, mail enablement, and any future real HR provider binding remain separate operational/client decisions.
- Exact next step: client review/release planning only; do not deploy automatically.

## Safety and workspace notes

- The worktree already contains a large set of modified and untracked files, including the License Management implementation. Treat all pre-existing changes as user-owned and do not overwrite or clean them.
- No destructive Git operation has been run.
- No deployment has been run.
- No government integration has been created or simulated.
- Project 2 application/schema/routes/tests now contain the completed Phase 1 foundation described above.
- Accepted baseline exception (user-approved, 2026-08-31): `Tests\\Feature\\AdmissionCalculatorTest` fails because its existing SQLite fixture lacks `admission_rooms.name_en`; do not change unrelated Admission Calculator code/fixtures unless Government Accounts causes a regression.
