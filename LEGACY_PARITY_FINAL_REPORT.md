# Legacy Parity Final Report

Report date: 2026-07-31

## Result

**NOT DELIVERY-READY.** The complete source-first inventory found 1,865 first-party legacy PHP surfaces and 179 directly matched menu targets, compared with 135 Laravel application routes. Static, conservative classification is:

| Classification | Count | Percent of legacy inventory |
|---|---:|---:|
| Fully migrated | 5 | 0.27% |
| Partially migrated | 827 | 44.34% |
| Missing | 75 | 4.02% |
| Broken | 0 | 0.00% |
| Duplicate/obsolete with evidence | 0 | 0.00% |
| Client decision required | 958 | 51.37% |
| **Total** | **1,865** | **100.00%** |

The parity percentage is **0.27%**, calculated strictly as individually proven Fully migrated rows divided by all first-party rows. This intentionally does not give partial credit. It is not a measure of Laravel code volume; it is the requested page/function parity gate.

## Implemented in this batch

No previously missing legacy business page was claimed complete. The verified navigation gap for the existing user/permission administration was fixed: authorized Super Administrators and scoped Permission Administrators now receive a sidebar link governed by the same `PermissionService::canManageUsers()` decision as server-side route protection. Standard users do not receive the link. A feature test covers both cases.

## Exact unresolved inventory

Every partial, missing, and decision-required page is individually recorded with tables, actions, workflow evidence, scope, files, print/PDF, priority, and evidence in `OLD_TO_NEW_COMPLETE_GAP_AUDIT.md`. The 75 active-menu Missing rows are:

+- `admin/action_taken.php` (Unclassified legacy surface)
- `admin/adm_brands.php` (Unclassified legacy surface)
- `admin/adm_country.php` (Unclassified legacy surface)
- `admin/adm_reg_branch.php` (Unclassified legacy surface)
- `admin/admission_nationality.php` (Unclassified legacy surface)
- `admin/admission_service_price.php` (Unclassified legacy surface)
- `admin/admission_status.php` (Unclassified legacy surface)
- `admin/approval_status.php` (Unclassified legacy surface)
- `admin/approvals_service.php` (Unclassified legacy surface)
- `admin/approving_bodies.php` (Unclassified legacy surface)
- `admin/birth_notification_id_type.php` (Unclassified legacy surface)
- `admin/birth_notification_obstetrics.php` (Unclassified legacy surface)
- `admin/booking_period.php` (Appointments)
- `admin/branches_area.php` (Unclassified legacy surface)
- `admin/branches_departments.php` (Unclassified legacy surface)
- `admin/branches_needs.php` (Unclassified legacy surface)
- `admin/branches_service_type.php` (Unclassified legacy surface)
- `admin/change_duty_time.php` (Unclassified legacy surface)
- `admin/city.php` (Unclassified legacy surface)
- `admin/codes_sections.php` (Unclassified legacy surface)
- `admin/death_reason.php` (Unclassified legacy surface)
- `admin/ehala_case_apology_type.php` (Unclassified legacy surface)
- `admin/ehala_non_approval_reason.php` (Unclassified legacy surface)
- `admin/gender.php` (Unclassified legacy surface)
- `admin/governmental_services_type.php` (Unclassified legacy surface)
- `admin/index.php` (Unclassified legacy surface)
- `admin/insurance_companies.php` (Unclassified legacy surface)
- `admin/job_titles.php` (Unclassified legacy surface)
- `admin/lawsuit.php` (Legal cases)
- `admin/mail_folder.php` (Unclassified legacy surface)
- `admin/mail_important.php` (Unclassified legacy surface)
- `admin/mail_inbox.php` (Unclassified legacy surface)
- `admin/mail_sent.php` (Unclassified legacy surface)
- `admin/mail_star.php` (Unclassified legacy surface)
- `admin/mail_trash.php` (Unclassified legacy surface)
- `admin/maintenance_departments.php` (Unclassified legacy surface)
- `admin/maintenance_type.php` (Unclassified legacy surface)
- `admin/medical_terminology.php` (Unclassified legacy surface)
- `admin/new_post.php` (Unclassified legacy surface)
- `admin/notice_type.php` (Unclassified legacy surface)
- `admin/onlinetody.php` (Unclassified legacy surface)
- `admin/post_type.php` (Unclassified legacy surface)
- `admin/receipt_case_via.php` (Unclassified legacy surface)
- `admin/relative_relation.php` (Unclassified legacy surface)
- `admin/resignations.php` (Unclassified legacy surface)
- `admin/room_type.php` (Unclassified legacy surface)
- `admin/services_codes.php` (Unclassified legacy surface)
- `admin/specialization.php` (Unclassified legacy surface)
- `admin/technical_failure_notice.php` (Unclassified legacy surface)
- `admin/technical_failure_notice_platform.php` (Unclassified legacy surface)
- `admin/technical_failure_notice_sections.php` (Unclassified legacy surface)
- `admin/technical_failure_notice_service_type.php` (Unclassified legacy surface)
- `admin/technical_failure_notice_type.php` (Unclassified legacy surface)
- `admin/third_party.php` (Unclassified legacy surface)
- `admin/transferal_reason.php` (Unclassified legacy surface)
- `branch/change_duty_time.php` (Unclassified legacy surface)
- `branch/death_reason.php` (Unclassified legacy surface)
- `branch/index.php` (Unclassified legacy surface)
- `branch/insurance_companies.php` (Unclassified legacy surface)
- `branch/lawsuit.php` (Legal cases)
- `branch/mail_folder.php` (Unclassified legacy surface)
- `branch/mail_important.php` (Unclassified legacy surface)
- `branch/mail_inbox.php` (Unclassified legacy surface)
- `branch/mail_sent.php` (Unclassified legacy surface)
- `branch/mail_star.php` (Unclassified legacy surface)
- `branch/mail_trash.php` (Unclassified legacy surface)
- `branch/new_post.php` (Unclassified legacy surface)
- `branch/technical_failure_notice.php` (Unclassified legacy surface)
- `branch/upload/index.php` (Unclassified legacy surface)
- `death_reason.php` (Unclassified legacy surface)
- `emdha/index.php` (Unclassified legacy surface)
- `index.php` (Unclassified legacy surface)
- `insurance_companies.php` (Unclassified legacy surface)
- `lawsuit.php` (Legal cases)
- `technical_failure_notice.php` (Unclassified legacy surface)

The 827 Partially migrated rows are not collapsed into a false “module complete” claim; their exact page/function rows are the authoritative unresolved list in the complete matrix. Major verified gaps include complaint create/reply/status/report/PDF actions, inquiry create/detail/public output, password reset completion, profile password/signature, training/coordinator, appointment booking, legal workflows, operational branch forms, administration reference data, report families, dedicated output layouts, and secured downloads.

The 958 Client decision required rows cannot be classified obsolete, duplicate, inactive, or required from source alone. Resolution requires current production permission/page records, runtime reachability evidence, or a documented client decision.

## Required external evidence

- Sanitized current legacy schema/data and representative role/company/branch accounts.
- Current `page`, direct/group permission, and branch-permission rows.
- Production rewrite/menu configuration and access evidence for unknown/backup-looking endpoints.
- Production attachment inventory and filesystem mapping.
- Production-like PHP extensions/fonts for Arabic PDF acceptance.
- Approved Saudi environment for SMS, Yakeen, Sadq, and Emdha tests.

No migrations or destructive database commands were run. `.env` and `OldProject` were not modified.

## Changed files

- `FULL_MIGRATION_IMPLEMENTATION_PLAN.md`
- `LEGACY_INVENTORY.tsv`
- `LEGACY_INVENTORY_DRAFT.md`
- `NAV_PERMISSION_PARITY_DRAFT.md`
- `NEWPROJECT_INVENTORY_DRAFT.md`
- `OLD_TO_NEW_COMPLETE_GAP_AUDIT.md`
- `LEGACY_PARITY_FINAL_REPORT.md`
- `tools/generate_parity_audit.php`
- `app/Services/Dashboard/NavigationService.php`
- `config/hm.php`
- `lang/ar/dashboard.php`
- `lang/en/dashboard.php`
- `tests/Feature/UserPermissionManagementTest.php`

## Verification

- `php artisan optimize:clear`: passed.
- `php artisan route:list --except-vendor`: passed; 135 application routes.
- `php artisan test`: passed; 13 tests and 24 assertions.
- `npm run build`: passed; 56 modules transformed.
- `git diff --check`: passed.
- Focused user-navigation authorization test: passed.
- Local HTTP smoke: `/` 200 with `lang="ar" dir="rtl"`; unauthenticated user administration redirects; proven-missing training URL returns 404.
- Real browser automation: not run because no Chromium/Playwright browser is installed in the workspace; authenticated responsive smoke also requires representative legacy credentials/data.
- Authenticated browser/database/file/PDF parity across 1,865 legacy surfaces: not possible without the required sanitized production inputs.

## Git

- Branch: `autonomous-delivery-sprint`
- Inventory commit: `34ff893 docs: inventory complete legacy migration surface`
- Navigation implementation commit: `600f948 fix: expose scoped permission administration navigation`

## Delivery decision

The application cannot be marked complete while any active legacy row is Missing or Partially migrated. It therefore remains **NOT DELIVERY-READY**. Broad implementation is blocked at the permission/status/file evidence gate; guessing those rules would violate schema compatibility, authorization, and no-invented-business-logic requirements.
