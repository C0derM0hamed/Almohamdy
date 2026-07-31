# Verified Page Gaps

Audit date: 2026-07-31. Evidence sources: `ACTIVE_PAGE_SCOPE.md`, route/controller inspection, non-destructive Laravel tests, and local database connectivity checks. No legacy code or database was modified.

## Delivery-scope findings

| Page/workflow | Status | Evidence-backed gap | Priority |
|---|---|---|---|
| Login, OTP, dashboards, doctors, service locations, hospital services | Needs verification | Routes/views exist, but role/data parity requires the safe legacy database and browser crawl. | P1 |
| Password recovery/reset | Broken | Forgot endpoint only returns a generic success response; no token, reset form, or password write route is present. | P0 |
| Complaints list/dashboard | Partial | Read/list/timeline routes exist; legacy create, reply, status, confirmation, attachments, and output flows are absent. | P0 |
| Complaint create/request | Missing | No Laravel create/store route or view. Legacy `complaint_request.php` is active. | P0 |
| Complaint detail/workflow | Partial | Timeline is read-only; verified legacy transitions and attachment/output actions are not implemented. | P0 |
| Leave requests and approvals | Needs verification | Main routes exist; legacy field/status/approval parity is unverified without data. | P1 |
| Absence notifications | Partial | Management actions exist, but the legacy request/create flow and complete output/file behavior are unverified. | P0 |
| Circulars, inspection visits, data requests, correspondence | Needs verification | Main screens and public reply/receipt routes exist; role, scope, attachment, and print parity is unverified. | P1 |
| Outgoing correspondence | Needs verification | Main list/print/revise routes exist; complete legacy status and file behavior is unverified. | P1 |
| Inquiries | Partial | Listing/timeline/status/PDF exist; create, outgoing detail, and complete reply workflow are absent. | P0 |
| Training management | Missing | Active legacy menu/permission evidence; no verified NewProject route. | P1 |
| Training coordination | Missing | Active legacy menu/permission evidence; no verified NewProject route. | P1 |
| Medical appointment requests | Missing | Legacy appointment family and client scope identify it as a candidate; no verified NewProject workflow. | P1 |

## Authorization and infrastructure blockers

- Most selected module routes are protected only by session authentication; page/action grants and direct-URL denial are not proven for complaints, inquiries, correspondence, circulars, visits, data requests, leave, and service modules.
- File URLs are generally direct public paths; authenticated download authorization is not proven.
- `.env` points to MySQL `127.0.0.1:3307`/`hms`, where no local service is listening. Port 3306 credentials also fail. The legacy dump has not been imported into a disposable local database.
- No destructive commands or incompatible inquiry migrations were run.

## Automated evidence

`php artisan optimize:clear`, route listing, Laravel tests (13 passed), and `npm run build` pass. `npx playwright test` exits with “No tests found”; authenticated browser parity has not been completed.

