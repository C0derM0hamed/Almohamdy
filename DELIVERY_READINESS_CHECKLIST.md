# Delivery Readiness Checklist

| Area | Result | Evidence/blocker |
|---|---|---|
| Active-page scope | Pass for discovery | 115 conceptual pages and 28 candidate delivery pages documented. |
| Legacy database compatibility | Pass (local audit) | Read-only dump imported into separate `hms_migration_test` on local 3307/socket; restricted `hms_audit` reads real records. Original `.env` and dump were not changed. |
| Authentication/OTP | Needs verification | Code/tests exist; no legacy-data/browser acceptance. |
| Password recovery | Pass | Username/mobile generic flow, hashed expiring OTP, throttling, one-time reset marker, and legacy SHA-256 write tested. |
| Permission administration | Partial pass | Core protections tested; module page/action authorization and explicit inherited-deny semantics require verification. |
| Branch/company isolation | Partial pass | Complaint, inquiry, and absence writes/reads enforce scoped company/branch/requester queries; all four role crawls remain to be reviewed. |
| Complaints | Pass for verified P0 | Create, sequential workflow, attachment storage/download authorization, and PDF output are implemented and smoke-tested with PW_AUDIT data. |
| Inquiries | Pass for verified P0 | Outgoing create/detail and incoming reply/completion/transfer writes use dump-backed columns and scoped queries. |
| Employee services | Pass for absence P0 | Self-service absence request/create, duplicate guard, recipient rows, and protected certificate download are implemented; leave parity remains verification work. |
| Attachments/downloads | Partial | Complaint and absence downloads are protected; circular/visit/data/correspondence helpers still need typed protected endpoints. |
| Printing/PDF | Partial pass | Complaint and inquiry PDF outputs are available and authorized; remaining parent-page print parity needs review. |
| Arabic RTL/responsive UI | Partial pass | Playwright harness and guest desktop/mobile checks pass; full authenticated role matrix remains. |
| Laravel tests/build | Pass | 13 tests pass; optimize clear, route list, and Vite build pass. |
| Playwright | Partial pass | Harness exists; guest desktop/mobile protection tests and super-admin desktop scope crawl pass. Remaining roles/viewport review is outstanding. |

## Decision

**NOT DELIVERY-READY.** P0 implementation is substantially complete and tested locally. Final delivery still requires the remaining protected-download batch, full role/viewport crawl, and client decisions for training management, training coordination, and medical appointment requests.
