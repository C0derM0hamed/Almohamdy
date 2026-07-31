# Implementation Progress

## Discovery checkpoint

- Consolidated legacy inventory: 115 active main business pages; 28-page candidate delivery scope documented in `ACTIVE_PAGE_SCOPE.md`.
- Discovery checkpoint committed as `b035752` on `autonomous-delivery-sprint`.
- No OldProject files, SQL dump, `.env`, migrations, or production systems were modified.

## Completed or existing implementation

- Authentication shell, OTP flow, role dashboards, doctors directory/admin, service locations, hospital services, user/permission administration, leave shell, absence management, circulars, inspection visits, data requests, correspondence, outgoing correspondence, and inquiry read/PDF surfaces are present in code.
- Permission-management tests cover standard-user denial, scoped permission administrator behavior, no self-promotion, and final super-administrator protection.
- Complaint timeline ordering and inquiry legacy status/branch/company compatibility tests pass.

## Implemented this cycle

- Imported the read-only legacy dump into disposable `hms_migration_test` on separate local MariaDB 3307/socket; Laravel reads 636 legacy tables and real records through restricted `hms_audit`.
- Added username/mobile password recovery with expiring HMAC-hashed OTP, throttling, one-time reset marker, and legacy SHA-256 compatibility.
- Added complaint create/store, sequential reply/status workflow, dump-backed fields, protected reply attachment downloads, and Dompdf output with company/branch scope.
- Added outgoing inquiry create/detail and incoming scoped detail, exact reply/status columns, transfer handling, and completion status 6.
- Added absence self-service create/list for all five verified types, duplicate protection, supervisor recipient rows, private certificate storage, and owner/recipient/management download authorization.
- Added fail-closed page/action permission middleware for complaint, inquiry, and absence routes, explicit direct-deny precedence, and branch/company/requester isolation.
- Added OldProject Playwright Chromium desktop/mobile harness, telemetry, screenshots, guest protection checks, conditional role tests, and isolation scaffolding.

## Required next batch after infrastructure is provisioned

1. Complete protected download controllers for circulars, visits, data requests, and correspondence, then review the final permission matrix.
2. Run all four audit roles on desktop/mobile with representative grants and review screenshots/telemetry.
3. Obtain client decision and verified workflow evidence before implementing training management, training coordination, or medical appointment requests.
