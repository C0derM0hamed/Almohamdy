# Autonomous Implementation Plan

## Constraints

- Work only in `NewProject`; treat `OldProject` and the approved audit as read-only references.
- Preserve the legacy MySQL schema and established statuses, permission identifiers, and file mappings.
- Do not run destructive database commands, overwrite `.env`, invent workflows, or call Saudi-restricted integrations.
- Do not create a migration. If schema support is insufficient, record a delivery blocker instead.

## Delivery Batches

1. Establish a Git baseline and capture the executable baseline (`optimize:clear`, routes, PHP tests, asset build).
2. Audit authentication and implement schema-supported user and permission administration with server-side scope and escalation protections.
3. Repair the complaints timeline and complete only inquiry transitions verified against the legacy implementation.
4. Verify existing modules, safely remove access to unverified placeholders, and review attachments, PDF, printing, and integrations.
5. Verify legacy database mappings and add focused authorization/workflow tests that do not depend on production data.
6. Review Hope UI, Arabic RTL, responsive behavior, and run authenticated/public browser smoke tests where credentials and fixtures permit.
7. Run the required command suite, close safe defects, document blockers and Saudi-only checks, then make the delivery-readiness decision.

## Acceptance Gates

- Every route is protected at the server and object queries enforce branch/company scope.
- A Super Administrator is data-driven, not identity-hardcoded; a scoped Permission Administrator cannot grant or access beyond their own effective scope.
- Self-promotion, cross-scope changes, direct URL bypass, and removal of the final Super Administrator are rejected.
- Complaint/inquiry state and timeline behavior matches legacy evidence.
- Unverified employee-service workflows are inaccessible rather than simulated.
- Existing writes retain validation and transactions where multiple legacy tables are affected.
- Legacy attachments resolve without exposing arbitrary filesystem paths; print/PDF responses render Arabic correctly.
- Required automated commands pass, or their exact environmental/product blocker is recorded.
- Critical browser paths have no 404/500, layout overlap, or authorization bypass.

## Workstream Ownership

- Authentication, users, and permissions: dedicated implementation/review stream.
- Complaints and inquiries: dedicated legacy-parity stream.
- Existing modules, placeholders, files, PDF/print, UI, DB, and integrations: dedicated audit stream.
- Lead review: inspect every diff, run tests per batch, commit only stable batches, and own the final readiness verdict.
