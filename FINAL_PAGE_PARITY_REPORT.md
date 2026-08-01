# Final Page Parity Report

## Current result

This is the final implementation report for the confirmed delivery scope. All 28 candidate delivery pages are now implemented in NewProject with verified legacy content and functionality on the current Laravel architecture and Hope UI Arabic RTL layout. The three previously deferred workflows — training management, training coordination and medical appointment requests — were implemented in this sprint from verified legacy source and dump evidence. Delivery is **READY FOR CONFIRMED SCOPE**; 75 active legacy pages outside that scope still need client inclusion before a full-system READY can be declared.

| Measure | Count |
|---|---:|
| Active legacy main pages discovered | 115 |
| Candidate client delivery pages | 28 |
| Candidate pages complete/verified for confirmed scope | 28 |
| Candidate pages needing credentialed browser verification | 0 |
| Candidate pages client decision required | 0 |
| Active pages outside candidate scope requiring client decision | 75 |

The 1,865 PHP files/endpoints are classified in `ACTIVE_PAGE_SCOPE.md`; handlers, AJAX, print/PDF, includes, and child actions are dependencies rather than additional main pages.

## Completed blocker results

- Protected downloads completed for circulars, inspection visits, data requests, correspondence, and outgoing correspondence where stored files exist.
- Download endpoints enforce authentication, page/action permission middleware, company scope, branch scope, parent/child attachment ownership, path traversal rejection, safe 404/403 behavior, Arabic filename preservation, and detected MIME type.
- Public circular formal links no longer expose direct public file paths; they point to protected module download routes.
- Leave parity recheck found and fixed only verified gaps: branch scope on listing/detail and self-approval denial for branch approvers.
- Training management implemented: legacy `training` type-2 plan creation, status transitions, scoped list/detail, timeline, protected document downloads and signed PDF, gated by `TrainingPermissions::MANAGEMENT`.
- Training coordination implemented: legacy `training` type-1 path with its own `TrainingPermissions::COORDINATION` gate, create/status writes, scoped list/detail, timeline, protected documents and signed PDF.
- Medical appointment requests implemented: `book_a_medical_appointment` plus its six related legacy tables, scoped list with status summary and legacy filters, bilingual create with multiple proposed slots, statuses 5/8/9/10/12, dependent physician lookup, timeline, public patient and doctor token pages, and request/accepted/rejected/doctor-reply PDF outputs. Every read is branch+company scoped, so direct IDs cannot cross branches.
- Audit credentials were recovered/reset only for the four `PW_AUDIT_*` accounts using the verified legacy SHA-256 hash format and stored only in ignored `.env.audit`.

## Verification

- Laravel feature/unit suite: **33 passed, 172 assertions**.
- `php artisan optimize:clear`: passed.
- `php artisan route:list`: passed, **193 routes listed**.
- PHP lint on all changed/added PHP files: passed. `git diff --check`: clean. Working tree: clean.
- `npm run build`: passed.
- NewProject Playwright role suite: **8 passed, 0 failed, 0 skipped** across desktop/mobile and all four `PW_AUDIT_*` roles.
- OldProject/NewProject parity Playwright suite: **16 passed, 0 failed, 0 skipped** across desktop/mobile.
- Permission/isolation result: menu visibility, direct URL protection, action permission URLs, company isolation, branch isolation, protected downloads, leave self-approval prevention, and critical console/request telemetry passed.
- Database: configured `.env` was not changed. The existing `.env.audit` local audit configuration was used for the temporary Laravel server; database import was not repeated.

## Client decisions required

1. Scope inclusion for the 75 active legacy pages outside the confirmed delivery modules (legal, clinical operations, finance, reports, messaging, reference administration), listed per page in `ACTIVE_PAGE_SCOPE.md`. No behavior for these was invented or partially stubbed.
2. Current production permission and menu data, needed to settle the 772 legacy entries that cannot be classified active/dead from source alone.

## Unverified legacy behavior (documented, not invented)

- Legacy notification side effects (SMS/email dispatch via `sms_class.php` and `PHPMailer`) attached to some workflows were not reproduced; no substitute behavior was invented.

## Git

Branch: `autonomous-delivery-sprint`

Latest commits:

- `97ac9a0 Wire training coordination and medical appointment routes, navigation and audit coverage`
- `925d0d9 Implement medical appointment requests module with legacy workflow and PDFs`
- `0466c56 Implement training coordination parity and align training management views`
- `851569e Ignore Playwright report and test-results output`
- `4d5a530 Implement training management parity`

Final delivery status: **READY FOR CONFIRMED SCOPE.** Not full-system READY: 75 active legacy pages outside the confirmed scope still require client inclusion.
