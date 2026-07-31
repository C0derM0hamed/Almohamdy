# Verified Page Gaps

Audit date: 2026-07-31. Evidence sources: `ACTIVE_PAGE_SCOPE.md`, route/controller inspection, non-destructive Laravel tests, and local database connectivity checks. No legacy code or database was modified.

## Delivery-scope findings

| Page/workflow | Status | Evidence-backed gap | Priority |
|---|---|---|---|
| Login, OTP, dashboards, doctors, service locations, hospital services | Needs verification | Routes/views exist, but role/data parity requires the safe legacy database and browser crawl. | P1 |
| Password recovery/reset | Implemented | Username/mobile generic recovery, hashed expiring OTP, throttling, one-time reset authorization, and legacy SHA-256 write are implemented and feature-tested. | P0 |
| Complaints list/dashboard | Implemented | Scoped list/detail/timeline now has authenticated create, reply/status transitions, protected attachments, and PDF output. | P0 |
| Complaint create/request | Implemented | Authenticated direct create/store preserves dump-backed complaint and number fields, validation, branch/company ownership, and optional attachment. | P0 |
| Complaint detail/workflow | Implemented | Sequential statuses 1–4, terminal 5/6, reply timeline, dump-backed fields, protected download, and PDF are implemented. | P0 |
| Leave requests and approvals | Needs verification | Main routes exist; legacy field/status/approval parity is unverified without data. | P1 |
| Absence notifications | Implemented | Self-service request/list/create, five verified type paths, duplicate guard, supervisor recipients, private upload, and scoped download are implemented. | P0 |
| Circulars, inspection visits, data requests, correspondence | Needs verification | Main screens and public reply/receipt routes exist; role, scope, attachment, and print parity is unverified. | P1 |
| Outgoing correspondence | Needs verification | Main list/print/revise routes exist; complete legacy status and file behavior is unverified. | P1 |
| Inquiries | Implemented | Verified outgoing create/detail and incoming scoped detail are present; reply/status writes use dump columns and include completion status 6 and transfer semantics. | P0 |
| Training management | Client decision required | Legacy tables/permissions are present, but complete declarations/approvals were not sufficiently verified to implement safely. | P1 |
| Training coordination | Client decision required | Legacy tables/permissions are present, but complete coordinator actions were not sufficiently verified to implement safely. | P1 |
| Medical appointment requests | Client decision required | The seven-table legacy family is present, but no verified NewProject workflow/status evidence was available. | P1 |

## Authorization and infrastructure blockers

- Selected complaint, inquiry, and absence routes now fail closed through page/action permission middleware; repositories enforce company and branch/user scope, with explicit deny precedence for direct/group grants.
- Complaint, absence, and inquiry output/download routes are authenticated and scoped. Older circular/visit/data/correspondence attachment helpers still require a follow-up protected-download batch.
- The configured `.env` remains unchanged. A separate disposable MariaDB instance on 3307/socket was initialized as `hms_migration_test`, imported from the read-only dump, and accessed by restricted local user `hms_audit` through ignored `.env.audit` configuration.
- No destructive commands or incompatible inquiry migrations were run.

## Automated evidence

`php artisan optimize:clear`, route listing (163 routes), Laravel tests (16 passed, 55 assertions), and `npm run build` pass. Playwright guest direct-URL tests pass on desktop/mobile and the super-admin desktop 28-scope crawl passes against the local servers; remaining role/viewport review is QA follow-up.
