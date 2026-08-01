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

## Remaining P1/P2

- P1: complaint reference administration, publications/post types, and remaining operational submenu workflows whose old handlers still need source-level parity.
- P2: medical terminology/service-code references, rare reports, and low-use utilities.
- No page in P1/P2 is being counted as complete from a redirect alone.

## Blocked actions

- External SMS/email side effects in the legal claim source remain intentionally blocked because their production recipients and delivery contract are not safe to infer from the local audit database. The underlying in-app requests are implemented.

## Security status

- All P0 services use server-side authorization and scoped queries.
- Child records and downloads are parent-scoped to prevent IDOR.
- File downloads use `ProtectedFileDownload`; no direct storage URL is exposed.
- Arabic RTL and the existing Hope UI layout are retained.

Final P0 status: **COMPLETE**. Overall migration status remains **IN PROGRESS** while P1/P2 are queued.
