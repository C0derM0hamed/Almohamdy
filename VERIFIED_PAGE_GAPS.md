# Verified Page Gaps

Audit date: 2026-08-01. This audit starts from runtime OldProject navigation, not from the previous 28-page scope.

## Runtime crawl

- OldProject was started locally on http://127.0.0.1:8011.
- NewProject was started locally on http://127.0.0.1:8012; the ignored .env.audit configuration was loaded for the audit process without exposing secrets.
- The local OldProject database contained five named users (admin, manager, branch, supervisor, user1) but did not contain the four PW_AUDIT_* usernames. The audit passwords were loaded without printing them; local account mapping was required for the navigation crawl and is an environment-side audit change, not a NewProject commit.
- OTP verification used the existing local OldProject demo code.
- Crawled roles: PW_AUDIT_SUPER_ADMIN mapped to the local level-3 user supervisor; PW_AUDIT_PERMISSION_ADMIN mapped to the local level-1 user manager; PW_AUDIT_BRANCH_A mapped to branch; PW_AUDIT_BRANCH_B mapped to user1.
- The two branch accounts had no visible navigation entries under the current OldProject permission data.
- The crawl found 39 unique OldProject navigation URLs across the two accounts with visible navigation. PHP handlers and static asset files are not counted as pages.

## Classification

| Classification | Count | Result |
|---|---:|---|
| Existing and complete, reused | 5 | Dashboard, complaints, inquiries, leave requests, and incoming inquiry detail use existing NewProject controllers and views. |
| Existing but partial / compatibility entry point added | 13 routes | Legacy entry points now redirect into existing Laravel modules, but this does not prove legacy field/action parity for every old shell. Five of these correspond to crawled complete pages. |
| Previously missing, now implemented | 1 | Technical failure notices now have scoped list/create/detail/status/timeline/attachment/PDF behavior. |
| Missing or blocked | 26 | Legal workflow, emergency operations, admission calculators, reference administration, posts, medical terminology, service codes, reports, settings, password profile, and branch setup pages remain unimplemented. |
| Duplicate/merged | 0 | No NewProject implementation was duplicated or replaced. |
| Still blocked | 26 plus safe child pages | Full verification needs legacy credentials/permission data and implementation of the listed workflows. |

The 13 compatibility entry points are protected routes for dashboard, complaints, inquiries, incoming inquiry detail, leave requests, users, government circulars, inspection visits, corporate communications, outgoing correspondence, absence notifications, medical appointments, and technical failure notices. They intentionally reuse existing NewProject modules or the new scoped technical failure module; they are not counted as duplicate pages.

## Verified reused pages

| OldProject URL | NewProject equivalent | Status |
|---|---|---|
| index.php | /dashboard | Reused |
| complaints.php | modules.complaints.index | Reused |
| inquiries.php | modules.inquiries.outgoing.index | Reused |
| vacations.php | modules.leave.requests.index | Reused |
| inquiries_and_services_receiver1.php?id={id} | modules.inquiries.show with direction=incoming | Reused and ID validated |
| technical_failure_notice.php | modules.technical-failures.index | Implemented with scoped workflow |

Existing module detail, timeline, attachment, protected download, PDF, print, and permission behavior remains owned by the existing NewProject implementations.

## Missing or blocked runtime pages

change_my_pass.php, lawsuit.php, complaint_closing_reasons.php, complaint_letter_receiver.php, new_post.php, post_type.php, medical_terminology.php, services_codes.php, permissions.php, change_duty_time.php, resignations.php, users.php field/action parity, job_titles.php, governmental_services_type.php, companies_groups.php, adm_reg_branch.php, branches_departments.php, branches_needs.php, branches_area.php, branches_service_type.php, rep_1.php, emergency_follow_up.php, transferal_home.php, manual_admission_calculator.php, emergency_cases_process.php, settings.php, and emergency_reception_mechanism.php.

These pages are not represented by placeholders. Each needs its old handler, schema, statuses, permissions, linked children, file behavior, and output formats reviewed before implementation.

## Authorization and isolation

- New compatibility routes are inside auth.session.
- Permission-gated legacy entries use the same named permissions as their target NewProject module.
- Inquiry compatibility IDs are integers and are resolved through the existing scoped inquiry service.
- Technical failure notice records, statuses, timelines, PDFs, and attachments are resolved through company/branch-scoped service queries.
- Existing completed modules retain server-side authorization, branch/company scope, and protected file download behavior.
- Full role parity is not proven because the local OldProject permission dataset did not expose the PW_AUDIT_* identities and the branch accounts had no visible menus.

## Verification evidence

Existing NewProject feature coverage remains green before this audit batch. Final commands must be rerun after the compatibility routes are committed.
