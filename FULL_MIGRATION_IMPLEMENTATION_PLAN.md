# Full Migration Implementation Plan

Generated: 2026-07-31

## Basis and completion rule

This plan is derived from `LEGACY_INVENTORY.tsv`, `OLD_TO_NEW_COMPLETE_GAP_AUDIT.md`, `NEWPROJECT_INVENTORY_DRAFT.md`, and `NAV_PERMISSION_PARITY_DRAFT.md`. The inventory contains 1,865 first-party legacy PHP surfaces, including 179 direct menu targets. NewProject exposes 135 application routes.

A batch is complete only when every included legacy row has route, content, validation, authorization, scope, workflow, file, and output evidence. Filename similarity is not acceptance evidence. No unknown file may be called obsolete or duplicate without production/runtime or documented client evidence.

## Required evidence gate

Before broad workflow implementation, obtain a sanitized current schema/data snapshot plus exports of `page`, `user_permission`, `user_groups_permission`, branch permission tables, and representative role/company/branch accounts. Obtain the production attachment inventory and web-server rewrite rules. These are non-destructive inputs; without them, permission names, active pages, status labels, and file mappings cannot be safely finalized.

## Dependency batches

| Batch | Scope | Dependencies | Acceptance |
|---|---|---|---|
| 0. Inventory baseline | Freeze 1,865-row register, NewProject inventory, active menu graph, reproducible matrix | Source tree | Row counts stable; every first-party file represented; no unsupported obsolete/duplicate decisions |
| 1. Authentication and account profile | Login, OTP, forgot/reset password completion, change password, signature/profile, logout | Production mail/OTP rules and signature storage | Direct URL tests, rate/expiry tests, password write compatibility, no identity hardcodes |
| 2. Permission namespace and route map | Import active `page` keys into a documented route/action map; preserve direct/inherited deny/permit semantics | Production permission exports | Every protected route/action has server-side grant and scope tests; navigation uses same decision |
| 3. Navigation parity | Employee, branch, admin menus; dynamic service/report children; notification deep links | Batches 1-2 and active company/branch configuration | Every active page is reachable only when permitted; zero dead links; no hidden missing functions |
| 4. Existing Laravel module closure | Complaints, inquiries, leave, absence, circulars, visits, data requests, correspondence, outgoing correspondence, doctors, locations, hospital services | Batches 1-3, representative DB/files | Page-level legacy row reconciliation; complete missing create/reply/status/report/file actions; scoped CRUD tests |
| 5. Employee services | Permission request, duty-time change, resignation, evaluation, training confirmation/coordination, medical appointment booking | Batches 1-3; verified tables/status rows; Saudi integrations isolated | Full forms, coordinator/approval actions, PDF/Emdha/SMS boundaries, branch/company restrictions |
| 6. Legal workflows | Lawsuits, executive titles, commercial/labor/medical/administrative cases, sessions, reconciliation, archives | Batches 1-3; legal status/approval confirmation | Full case lifecycle, documents, timelines, reports, permissions and scope |
| 7. Operational branch workflows | Birth/death, admission calculators/consents, referrals, beds, emergency/incidents, psychosocial, technical failure, government services and other active branch targets | Batches 1-3; workflow-by-workflow client/data validation | Complete forms/handlers/PDFs and branch allow-list replacement with data-driven policy |
| 8. Agreements and guarantees | Payment guarantees, medical-service agreements, Sadq/Emdha/Yakeen variants, claiming against others | Auth/scope map; Saudi production sandbox/approval | No local live restricted calls; signed output and failure/retry behavior accepted in Saudi environment |
| 9. Administration and reference data | Companies, branches, departments, service types, complaint/inquiry/status/reference tables, standalone group management | Permission map and schema verification | Scoped CRUD, referential validation, final-super-admin protection, no schema redesign |
| 10. Reports, print, PDF and exports | All active report families and dedicated letters/receipts/confirmation layouts | Completed source workflows, fonts/extensions, production files | Content/column/filter parity; Arabic visual acceptance; authorized file access |
| 11. Public/token endpoints | All 785 public/token/direct-access candidates resolved and implemented/secured as applicable | Active call graph, token schema/expiry rules | Valid/invalid/expired/replayed token tests; upload validation; no record enumeration |
| 12. File migration and download controls | Map every active storage root; authorized download routes where content is non-public | Production inventory and deployment filesystem | Counts/checksums reconciled; traversal/MIME/size tests; no unintended public exposure |
| 13. RTL and responsive acceptance | Hope UI Arabic/English content parity across all accepted pages | Completed UI batches | Browser screenshots at mobile/desktop; no overlap; print direction/fonts accepted |
| 14. Regression and delivery | Full automated suite, route audit, build, authenticated browser matrix, Saudi-only acceptance list | All prior batches | Zero active Missing/Partial rows and zero critical authorization/write/file failures |

## Immediate verified batch

The current sprint can safely expose the already-implemented user/permission screen in navigation using the same `PermissionService::canManageUsers()` gate as the route middleware. A focused feature test must prove authorized visibility and standard-user absence. No generic permission middleware will be added to other modules until the production page-key map is available; guessing permission names would change access behavior without evidence.

## Workstream sizing

The broad families are not single-page tasks: the static register identifies 84 complaint-related, 34 inquiry-related, 79 HR request, 46 training/course, 30 appointment/booking, 107 legal case, 106 agreement/guarantee, and 288 report-related files. Each family must be split by shared table/workflow and committed only after its focused tests pass.

## Stop/decision conditions

- A status or approval meaning exists only in current production lookup data.
- A legacy condition is contradictory or relies on a hardcoded user/branch identity.
- Two candidate duplicate files remain differently reachable or their production usage is unknown.
- A schema change appears necessary.
- Saudi-restricted provider behavior cannot be verified outside its approved environment.

These are client/data evidence gates, not reasons to classify the affected legacy page obsolete or complete.
