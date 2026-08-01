# Verified Page Gaps

Audit date: 2026-08-01. This report uses the existing crawl artifact and a fresh four-role OldProject crawl. PHP filenames are route identifiers, not page counts.

## Crawl evidence

- OldProject: `http://127.0.0.1:8011`
- NewProject: `http://127.0.0.1:8012`
- All four `PW_AUDIT_*` accounts reached OldProject OTP and completed the authenticated crawl.
- The crawl captured 114 unique link targets. After removing language variants, logout, `void(0)`, mail links, static account templates, and other non-page targets, 105 page/child URL candidates remain.
- Per-role captured link targets: super admin 45, permission admin 87, branch A 87, branch B 65. These are link-target counts and include repeated shell links and child URLs.
- Existing crawl artifacts: `/tmp/old-nav-audit.json` and the fresh local evidence file `/tmp/current-old-nav-audit.json` (not committed).

## Classification

| Classification | Result |
|---|---:|
| Existing and complete, reused | 5 verified page families |
| Existing but partial or compatibility entry point | 13 protected routes; parity still needs old child-workflow evidence |
| Previously missing, implemented in this continuation | 3 page families |
| Duplicate implementations | 0 |
| Missing, broken, or still unverified | 97 URL-level candidates, including safe child pages |

## Business-module queue

The 97 remaining URL candidates were grouped by workflow rather than filename:

- 12 real business modules
- 42 deduplicated main page templates
- 55 child/action pages, including detail, create, edit, timeline, attachment, print, and PDF targets

### P0

- Emergency follow-up, transferal, reception, and emergency process: approximately 9 main pages.
- Admission calculators: approximately 2 main pages.
- Employee request workflows: permissions, duty changes, and resignations, approximately 3 main pages.
- Legal claims/lawsuits: approximately 1 main page plus child actions.
- User and system administration residuals: approximately 3 main pages.
- Hospital service and directory residuals: approximately 5 main pages.

### P1

- Complaint reference administration.
- Publications and post types.
- Company, branch, department, and governmental-service setup.
- Operational submenu workflows not required by the P0 shell.

### P2

- Medical terminology and service-code references.
- Rare reports and low-use branch utilities.
- Duplicate/merged compatibility targets after verified parity.

Client decisions are only required for externally delivered SMS/email behavior and whether low-use clinical utilities should remain in the migration scope. No client decision is required for the verified P0 workflows.

The counts are URL-level for the fresh 105-candidate crawl. Compatibility routes are not counted as duplicate pages and are not treated as complete when they only redirect without verified field/action parity.

## Completed in this continuation

| OldProject page | NewProject equivalent | Result |
|---|---|---|
| `technical_failure_notice.php` | `modules.technical-failures.*` | Scoped list, create, status history, detail, PDF, protected attachment |
| `rep_1.php` | `modules.emergency-reports.*` | Real legacy report tables, date/employee/period filters, summaries, child sections, PDF, protected attachments |
| `change_my_pass.php` | `profile.password.*` | Current-password verification and SHA-256 legacy password update |
| `emergency_follow_up.php`, `emergency_follow_up_notice_record.php`, `emergency_follow_up_print.php` | `modules.emergency-follow-up.*` | Scoped list, create, detail, notice history, close action, and print |

The current Hope UI, Arabic RTL layout, server-side authorization, company/branch scope, and protected download services remain in use.

## Reused verified families

- Dashboard and legacy dashboard entry points.
- Complaints list/detail/create/reply/timeline/PDF and protected attachments.
- Inquiry outgoing/incoming list, scoped detail, timeline, status, and PDF behavior.
- Employee leave list/create/detail/approval workflow.
- Existing corporate correspondence, outgoing correspondence, absence notification, medical appointment, user administration, and training modules were reused where their NewProject routes already own the workflow.

## Remaining verified gaps

The fresh crawl still exposes legacy families requiring source-level parity work: lawsuits, complaint reference administration, posts and post types, medical terminology, service codes, permissions, duty changes, resignations, job titles, governmental service types, company/branch/department/reference administration, transferal, admission calculators, emergency case processing/reception, settings, and their linked detail/action/print/PDF pages. Several branch dashboard links also require separate verification against their old handlers before they can be mapped safely.

Emergency follow-up is no longer in the gap list: its verified branch-1 workflow is implemented and browser-tested in commit `89ee7e9`. The legacy source exposes no attachment or PDF behavior for this module; the verified print page is preserved.

## Security status

- NewProject role tests verify server-side permissions, IDOR protection, company isolation, branch isolation, protected downloads, and PDF responses for completed modules.
- The OldProject crawl confirms all four audit identities can authenticate and reach their role-specific shells. OldProject's current permission data exposes the same branch shell to branch A and the permission-admin mapping; this is recorded as evidence, not broadened in NewProject.
- No page is marked complete solely because it has a route or redirect.

Final parity status: **BLOCKED** until the remaining verified legacy families and child workflows are implemented and browser-verified.
