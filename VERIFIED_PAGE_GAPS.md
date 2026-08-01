# Verified Page Gaps

Audit date: 2026-08-01. Evidence sources: `ACTIVE_PAGE_SCOPE.md`, OldProject leave workflow files, imported audit database, route/controller inspection, Laravel feature tests, Vite build, and Playwright desktop/mobile harness execution.

## Delivery-scope findings

| Page/workflow | Status | Evidence-backed result | Priority |
|---|---|---|---|
| Login, OTP, dashboards, doctors, service locations, hospital services | Verified | Desktop/mobile Playwright completed for `PW_AUDIT_SUPER_ADMIN`, `PW_AUDIT_PERMISSION_ADMIN`, `PW_AUDIT_BRANCH_A`, and `PW_AUDIT_BRANCH_B` with login, OTP, menus, direct URL protection, permissions, isolation, downloads, and telemetry checks passing. | P0 |
| Password recovery/reset | Implemented | Username/mobile generic recovery, hashed expiring OTP, throttling, one-time reset authorization, and legacy SHA-256 write are implemented and feature-tested. | P0 |
| Complaints list/dashboard | Implemented | Scoped list/detail/timeline has authenticated create, reply/status transitions, protected attachments, and PDF output. | P0 |
| Complaint create/request | Implemented | Authenticated direct create/store preserves dump-backed complaint and number fields, validation, branch/company ownership, and optional attachment. | P0 |
| Complaint detail/workflow | Implemented | Sequential statuses 1-4, terminal 5/6, reply timeline, dump-backed fields, protected download, and PDF are implemented. | P0 |
| Leave requests and approvals | Implemented for verified legacy parity | OldProject branch approvals are company+branch scoped and reject self-approval; Laravel now applies both. No unverified leave statuses or fields were added. | P0 |
| Absence notifications | Implemented | Self-service request/list/create, five verified type paths, duplicate guard, supervisor recipients, private upload, and scoped download are implemented. | P0 |
| Circulars, inspection visits, data requests, correspondence | Implemented for protected downloads | Authenticated, permission-gated, company/branch scoped protected download endpoints exist for primary and related files; public circular links now route through protected module downloads rather than direct file paths. | P0 |
| Outgoing correspondence | Implemented for protected downloads | Authenticated, permission-gated, company/branch scoped attachment download endpoint exists where outgoing correspondence has stored files. | P0 |
| Inquiries | Implemented | Verified outgoing create/detail and incoming scoped detail are present; reply/status writes use dump columns and include completion status 6 and transfer semantics. | P0 |
| Training management | Client decision required | Full legacy workflow was not verifiable across code, database, menus, permissions, and linked pages. | P1 |
| Training coordination | Client decision required | Full legacy workflow was not verifiable across code, database, menus, permissions, and linked pages. | P1 |
| Medical appointment requests | Client decision required | The seven-table legacy family is present, but the complete workflow/status path was not verifiable across code, database, menus, permissions, and linked pages. | P1 |

## Authorization and infrastructure blockers

- Complaint, inquiry, absence, circular, inspection visit, data request, correspondence, and outgoing correspondence module routes now fail closed through authentication plus page/action permission middleware where implemented.
- Protected corporate download tests cover Arabic filenames/MIME type, missing permission, branch scope, attachment IDOR, and path traversal rejection.
- Four-role browser verification is complete. The ignored local `.env.audit` now contains the recovered/reset `PW_AUDIT_*` credentials and deterministic audit OTP settings; passwords were not committed.
- The configured `.env` remains unchanged. The audit server was run with `.env.audit`; no database import was repeated.

## Client questions required

1. Training management: Which legacy page is the source of truth for request creation, approvals, attendance/completion, certificates, and cancellation, and which permission names gate each action?
2. Training coordination: Which coordinator roles can assign, reschedule, approve, reject, close, and report training, and what database status values represent each step?
3. Medical appointment requests: Which of the seven related legacy tables define the canonical request lifecycle, who acts at each status, and which linked pages/menus must be considered in scope?

## Automated evidence

- `php artisan optimize:clear`: passed.
- `php artisan route:list`: passed, 162 routes listed.
- `php artisan test`: passed, 23 tests and 83 assertions.
- `npm run build`: passed.
- NewProject Playwright role suite: 8 passed, 0 failed, 0 skipped across desktop/mobile and all four `PW_AUDIT_*` roles.
- OldProject/NewProject parity suite: 16 passed, 0 failed, 0 skipped across desktop/mobile.
