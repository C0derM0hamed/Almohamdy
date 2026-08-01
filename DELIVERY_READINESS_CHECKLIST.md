# Delivery Readiness Checklist

| Area | Result | Evidence/blocker |
|---|---|---|
| Active-page scope | Pass for discovery | 115 conceptual pages and 28 candidate delivery pages documented. |
| Legacy database compatibility | Pass for local audit | Existing `.env.audit` configuration was used; `.env`, dump, and imports were not changed. |
| Authentication/OTP | Pass | Login and OTP passed in desktop/mobile Playwright for all four `PW_AUDIT_*` roles. |
| Password recovery | Pass | Username/mobile generic flow, hashed expiring OTP, throttling, one-time reset marker, and legacy SHA-256 write tested. |
| Permission administration | Pass | Permission-admin browser role passed menu visibility, direct URL protection, and permitted user-management access. |
| Branch/company isolation | Pass | Feature tests and Playwright role checks verified company isolation, branch isolation, and protected download branch scope. |
| Complaints | Pass for verified P0 | Create, sequential workflow, attachment storage/download authorization, and PDF output are implemented and tested. |
| Inquiries | Pass for verified P0 | Outgoing create/detail and incoming reply/completion/transfer writes use dump-backed columns and scoped queries. |
| Employee services | Pass for verified absence and leave parity | Absence workflow is implemented; leave now matches verified legacy branch scope and self-approval denial. |
| Attachments/downloads | Pass for requested protected-download blocker | Circulars, inspection visits, data requests, correspondence, and outgoing correspondence use protected typed routes with permission, company/branch scope, IDOR, traversal, filename, and MIME safeguards. |
| Printing/PDF | Partial pass | Complaint and inquiry PDF outputs are available and authorized; remaining parent-page print parity remains outside the requested blocker set. |
| Arabic RTL/responsive UI | Pass for confirmed scope | Desktop/mobile Playwright passed for all four audit roles with no critical browser console errors or failed requests. |
| Training management | Pass | Type-2 legacy plan create, status transitions, scoped list/detail, timeline, protected documents and signed PDF implemented and feature-tested. |
| Training coordination | Pass | Type-1 legacy coordination path implemented behind its own permission, with scoped list/detail, timeline, protected documents and signed PDF. |
| Medical appointment requests | Pass | Seven-table legacy family implemented: scoped list/summary/detail, bilingual create, statuses 5/8/9/10/12, public patient and doctor token pages, four PDF outputs, branch+company scoped queries. |
| Out-of-scope legacy pages | Client decision required | 75 active legacy pages in legal, clinical operations, finance, reports, messaging and reference administration remain outside the confirmed scope; see `ACTIVE_PAGE_SCOPE.md`. |
| Laravel tests/build | Pass | `optimize:clear`, `route:list` (193 routes), `php artisan test` (33 passed, 172 assertions), and `npm run build` passed. PHP lint on all changed files and `git diff --check` are clean. |
| Playwright | Pass | NewProject role suite: 8 passed, 0 failed, 0 skipped. OldProject/NewProject parity suite: 16 passed, 0 failed, 0 skipped. |

## Decision

**READY FOR CONFIRMED SCOPE.** Every page in the confirmed delivery scope is now implemented in NewProject with the verified legacy fields, table columns, actions, statuses, workflows, permissions, branch/company isolation, attachments and PDF/print outputs, using the current Laravel architecture and Hope UI Arabic RTL layout. Training management, training coordination and medical appointment requests moved from *client decision required* to *implemented* in this sprint.

This is **not** a full-system READY. Seventy-five active legacy pages remain outside the confirmed scope and 772 legacy entries cannot be classified without current production permission/menu data. Full delivery READY requires the client to confirm which of those pages are in scope.
