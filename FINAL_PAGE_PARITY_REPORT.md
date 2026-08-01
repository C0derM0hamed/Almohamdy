# Final Page Parity Report

## Current result

This is the final implementation report for the requested remaining blockers. Code-level blockers for protected downloads and verified leave parity are complete, and all required desktop/mobile Playwright role verification passed. Delivery is **READY FOR CONFIRMED SCOPE**; three workflows remain **CLIENT DECISION REQUIRED** and are not marked delivery-blocking unless the client adds them to the confirmed scope.

| Measure | Count |
|---|---:|
| Active legacy main pages discovered | 115 |
| Candidate client delivery pages | 28 |
| Candidate pages complete/verified for confirmed scope | 25 |
| Candidate pages needing credentialed browser verification | 0 |
| Candidate pages client decision required | 3 |
| Active pages outside candidate scope requiring client decision | 75 |

The 1,865 PHP files/endpoints are classified in `ACTIVE_PAGE_SCOPE.md`; handlers, AJAX, print/PDF, includes, and child actions are dependencies rather than additional main pages.

## Completed blocker results

- Protected downloads completed for circulars, inspection visits, data requests, correspondence, and outgoing correspondence where stored files exist.
- Download endpoints enforce authentication, page/action permission middleware, company scope, branch scope, parent/child attachment ownership, path traversal rejection, safe 404/403 behavior, Arabic filename preservation, and detected MIME type.
- Public circular formal links no longer expose direct public file paths; they point to protected module download routes.
- Leave parity recheck found and fixed only verified gaps: branch scope on listing/detail and self-approval denial for branch approvers.
- Training management, training coordination, and medical appointment requests were not implemented because full legacy workflow evidence is not verifiable yet.
- Audit credentials were recovered/reset only for the four `PW_AUDIT_*` accounts using the verified legacy SHA-256 hash format and stored only in ignored `.env.audit`.

## Verification

- Laravel feature/unit suite: **23 passed, 83 assertions**.
- `php artisan optimize:clear`: passed.
- `php artisan route:list`: passed, **162 routes listed**.
- `npm run build`: passed.
- NewProject Playwright role suite: **8 passed, 0 failed, 0 skipped** across desktop/mobile and all four `PW_AUDIT_*` roles.
- OldProject/NewProject parity Playwright suite: **16 passed, 0 failed, 0 skipped** across desktop/mobile.
- Permission/isolation result: menu visibility, direct URL protection, action permission URLs, company isolation, branch isolation, protected downloads, leave self-approval prevention, and critical console/request telemetry passed.
- Database: configured `.env` was not changed. The existing `.env.audit` local audit configuration was used for the temporary Laravel server; database import was not repeated.

## Client decisions required

1. Training management: Confirm the source legacy pages/tables, exact statuses, approval actors, cancellation behavior, certificate/attendance outputs, and permissions for every action.
2. Training coordination: Confirm coordinator role boundaries, assignment/reschedule/close workflow, reporting outputs, status IDs, and menu permission names.
3. Medical appointment requests: Confirm the canonical lifecycle across the seven related tables, required linked pages, each actor's allowed actions, status mapping, notification behavior, and print/export requirements.

## Git

Branch: `autonomous-delivery-sprint`

Latest implementation commit before this documentation update: `58864fd Protect circular public attachment links`

Final delivery status: **READY FOR CONFIRMED SCOPE; CLIENT DECISION REQUIRED for training management, training coordination, and medical appointment requests**.
