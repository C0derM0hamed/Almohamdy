# Implementation Progress

## Completed implementation

- Consolidated legacy inventory: 115 active main business pages and 28 candidate delivery pages documented in `ACTIVE_PAGE_SCOPE.md`.
- Password recovery/reset, complaints, inquiries, absence notifications, employee leave verified parity fixes, and corporate communication protected downloads are implemented at code level.
- Circulars, inspection visits, data requests, correspondence, and outgoing correspondence now use protected typed download endpoints instead of direct public file paths for module downloads.
- Page/action permission checks and company/branch scope are applied to the corporate communication module groups and download lookup paths.
- Leave workflow parity was rechecked against OldProject and the imported database. Only verified missing behavior was added: branch users are scoped to their branch and cannot approve their own leave request.
- Playwright audit-role coverage now passes for desktop and mobile projects, including login, OTP, menu visibility, direct URL protection, action permission URLs, company/branch isolation markers, protected downloads, critical console errors, and critical failed requests.

## Modules completed in this sprint

- **Training management** — legacy `training` type-2 plan creation, status transitions, scoped list/detail, timeline, protected document downloads and signed PDF, gated by `TrainingPermissions::MANAGEMENT`.
- **Training coordination** — legacy `training` type-1 coordination path with its own `TrainingPermissions::COORDINATION` gate, create/status writes, scoped list/detail, timeline, protected documents and signed PDF.
- **Medical appointment requests** — `book_a_medical_appointment` and its six related legacy tables: scoped list with status summary and legacy filters, bilingual create with multiple proposed slots, statuses 5/8/9/10/12, dependent physician lookup, timeline, public patient and doctor token pages, and request/accepted/rejected/doctor-reply PDF outputs. Visibility follows the legacy branch/company set in `MedicalAppointmentScope`; all reads go through a branch+company scoped query so direct IDs cannot leak across branches.

## Commits added after `6fd3c28`

- `9988a8a Add protected corporate download endpoints`
- `4f6ecd8 Add Playwright audit role coverage`
- `6a54952 Align employee leave branch approval scope`
- `58864fd Protect circular public attachment links`
- `212f8cc Complete audit role verification`
- `4d5a530 Implement training management parity`
- `851569e Ignore Playwright report and test-results output`
- `0466c56 Implement training coordination parity and align training management views`
- `925d0d9 Implement medical appointment requests module with legacy workflow and PDFs`
- `97ac9a0 Wire training coordination and medical appointment routes, navigation and audit coverage`

## Final QA run

- `php artisan optimize:clear`: passed.
- `php artisan route:list`: passed, 193 routes listed.
- `php artisan test`: passed, 33 tests and 172 assertions.
- `npm run build`: passed.
- NewProject role Playwright: 8 passed, 0 failed, 0 skipped across desktop/mobile and all four `PW_AUDIT_*` roles.
- OldProject/NewProject parity Playwright: 16 passed, 0 failed, 0 skipped across desktop/mobile.

- PHP lint on all changed/added PHP files: passed. `git diff --check`: clean. Working tree: clean.

## Remaining delivery blockers

1. Seventy-five active legacy pages (legal, clinical operations, finance, reports, messaging, reference administration) are outside the confirmed delivery scope and still require client inclusion before migration. They are enumerated in `ACTIVE_PAGE_SCOPE.md`.
2. 772 legacy PHP entries remain unclassifiable from source alone; settling them needs current production permission and menu data.
