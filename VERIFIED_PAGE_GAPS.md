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
| Training management | Implemented | Legacy `training` type-2 plan creation, status transitions, scoped list/detail, timeline, protected documents, and signed PDF are implemented and feature-tested. | P1 |
| Training coordination | Implemented | Legacy `training` type-1 coordination create/status path, scoped list/detail, timeline, protected documents, and signed PDF are implemented behind a distinct coordination permission and feature-tested. | P1 |
| Medical appointment requests | Implemented | The seven-table legacy family (`book_a_medical_appointment` plus status, time, timeline, coverage status, procedure place, reason) drives scoped list/summary/detail, bilingual create, legacy statuses 5/8/9/10/12, public patient and doctor token pages, and four PDF outputs. Feature-tested. | P1 |

## Authorization and infrastructure blockers

- Complaint, inquiry, absence, circular, inspection visit, data request, correspondence, and outgoing correspondence module routes now fail closed through authentication plus page/action permission middleware where implemented.
- Protected corporate download tests cover Arabic filenames/MIME type, missing permission, branch scope, attachment IDOR, and path traversal rejection.
- Four-role browser verification is complete. The ignored local `.env.audit` now contains the recovered/reset `PW_AUDIT_*` credentials and deterministic audit OTP settings; passwords were not committed.
- The configured `.env` remains unchanged. The audit server was run with `.env.audit`; no database import was repeated.

## Client questions resolved

The three previously open questions were answered from the legacy source and the imported dump rather than by assumption:

1. Training management is the legacy `training` type-2 plan path; training coordination is the type-1 path. Each is gated by its own permission (`TrainingPermissions::MANAGEMENT` / `::COORDINATION`) and each only accepts the status values its type uses in the dump.
2. Medical appointment requests follow `book_a_medical_appointment` with statuses 5, 8, 9, 10 and 12; patient and doctor act through legacy token links, and the branch/company visibility set is preserved in `MedicalAppointmentScope`.

## Remaining out-of-scope pages

Seventy-five active legacy pages in legal, clinical operations, finance, reports, messaging and reference administration are still outside the confirmed NewProject delivery scope and are listed in `ACTIVE_PAGE_SCOPE.md`. They remain **client decision required** — they were not silently dropped, and no behavior for them was invented.

## Automated evidence

- `php artisan optimize:clear`: passed.
- `php artisan route:list`: passed, 193 routes listed.
- `php artisan test`: passed, 33 tests and 172 assertions.
- `npm run build`: passed.
- NewProject Playwright role suite: 8 passed, 0 failed, 0 skipped across desktop/mobile and all four `PW_AUDIT_*` roles.
- OldProject/NewProject parity suite: 16 passed, 0 failed, 0 skipped across desktop/mobile.
