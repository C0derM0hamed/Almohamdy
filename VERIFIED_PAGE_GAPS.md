# Verified Page Gaps

Audit date: 2026-08-02. The existing four-role crawl and classification were reused. PHP filenames are route identifiers, not page counts.

## Classification baseline

- 114 unique OldProject link targets were captured.
- After excluding shell, language, logout, mail, duplicate, and non-page targets: 105 page/child candidates.
- The existing classification remains 12 business modules, 42 main page templates, and 55 child/action pages.
- Duplicate URLs and handlers were grouped into their owning business workflow.

## P0 checkpoint

| Module | Result | NewProject owner |
|---|---|---|
| Transfer and reception | Complete | `modules.transferal.*` |
| Admission calculators | Complete | `modules.admission-calculator/{standard,manual}/*` |
| Employee requests | Complete | `modules.employee-requests/{permission,duty,resignation}/*` |
| Legal claims | Complete | `modules.legal-claims.*` |
| System and service management | Complete for verified P0 references | Existing package/user administration plus `modules.system-admin.reference.*` |

## Verified P0 coverage

- Transfer/reception: outgoing and incoming lists, filters, create, detail, confirm, approve, refuse, receive, timeline, attachments, and PDF.
- Admission calculators: standard and manual lists, filters, create, detail, delete, and PDF using their legacy tables and lookups.
- Employee requests: permission, duty-time, and resignation lists, create forms, branch/HR replies, detail, and PDF.
- Legal claims: filtered list, create, detail, status actions, sessions, attachments, protected downloads, statement requests, reconciliation installments, payment marking, suspension requests, claim PDF, and suspension PDF.
- System/service management: reused complete package and user-permission workflows and added verified user groups, job titles, governmental service types, company groups, branches, departments, and branch needs CRUD/publish flows.

## P1 result

| Module | Result | NewProject owner |
|---|---|---|
| Complaint references | Complete | `modules.system-admin.reference.*` |
| Publications and post types | Complete | `modules.publications.*` plus `modules.system-admin.reference.post-types.*` |
| Company settings | Complete for verified settings entry and administration | Existing company administration plus `modules.settings.index` |
| Branch settings | Complete for verified settings entry and administration | Existing branches, departments, needs, and service-type administration |
| Service settings | Complete for verified settings entry and administration | Existing packages, governmental services, and service-type administration |

Complaint references preserve the verified global and branch-scoped reference tables, publish state, CRUD actions, and admin authorization. Publications preserve branch/company scope, post types, filters, real post data, image downloads, and the verified create/detail workflow.

## Verified P2 result

- Medical terminology administration is complete through the existing reference administration owner.
- Service-code administration is complete through the existing reference administration owner.
- No additional rare report or low-usage utility is an active, reachable gap for the four audit roles. The targeted source checks below explain the remaining legacy report files.
- `branch/reports_absence.php` and `branch/report_absence.php` are duplicate report screens. Their menu entry is guarded by `barches_emp_array = [10, 5, 15, 9]`; audit branches 1 and 2 cannot reach it. `branch/reports_absences.php` has no active menu evidence.
- `branch/inquiresServicesReport.php` and `branch/inquiresServicesReport_export.php` are one report plus its export handler, and the report has an explicit `hr_branch_id != 15` redirect. No audit account is assigned to branch 15, so neither the report nor its export is reachable in the verified audit scope.
- Other rare report/utility candidates remain outside the verified delivery because they have no active audit-role menu reachability, no usable production grant evidence, or are handlers/PDF/AJAX endpoints attached to excluded legacy modules. They are not implemented as standalone pages.
- No page is counted as complete from a redirect alone; settings uses a real current-layout landing page and links to existing authorized owners.

## Blocked actions

- External SMS/email side effects in the legal claim source remain intentionally blocked because their production recipients and delivery contract are not safe to infer from the local audit database. The underlying in-app requests are implemented.

## Security status

- All P0 services use server-side authorization and scoped queries.
- Child records and downloads are parent-scoped to prevent IDOR.
- File downloads use `ProtectedFileDownload`; no direct storage URL is exposed.
- Arabic RTL and the existing Hope UI layout are retained.

Final P1 status: **COMPLETE**. Verified P2 subset status: **COMPLETE**. No verified active P2 page remains missing in the four-role audit scope.
