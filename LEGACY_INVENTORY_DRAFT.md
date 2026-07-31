# Legacy Inventory Draft

This document is a generated, evidence-led inventory of `OldProject`. The legacy tree was read only. It is a discovery artifact, not proof that every file was reachable in production.

## Method and boundaries

- Scanned all PHP under `OldProject` and separated bundled third-party/template trees from first-party candidates.
- First-party candidates exclude `vendor`, TCPDF, HTML2PDF, Hope/template demo PHP, PHPMailer, phpseclib, TinyMCE, and Calx internals.
- Searched first-party code for menus, hyperlinks, redirects, forms, AJAX requests, SQL tables, session scope, permission checks, uploads/downloads, PDF/print behavior, and token/public access.
- `LEGACY_INVENTORY.tsv` is the exhaustive per-file machine-readable register. A row means the endpoint exists in source; it does not by itself prove production reachability.
- Dynamic PHP, database-stored permission rows, production rewrite rules, and production data cannot be fully resolved statically. Those cases are marked `unknown` rather than obsolete.

## Inventory totals

Totals are populated below after static extraction.

| Measure | Count |
|---|---:|
| All legacy PHP files, including bundled libraries/templates | 4,355 |
| First-party PHP candidates inventoried | 1,865 |
| Root endpoints/files | 753 |
| Branch endpoints/files | 689 |
| Admin endpoints/files | 336 |
| Includes/support classes | 57 |
| Other first-party integration/support files | 30 |
| Directly matched menu targets | 179 |
| Files with extracted SQL table evidence | 1,581 |
| Files with HTML form-action evidence | 725 |
| Files with company-scope terms | 199 |
| Files with branch-scope terms | 1,120 |
| Files with attachment/file evidence | 744 |
| Files with print/PDF evidence | 1,094 |
| Files with AJAX/JavaScript request evidence | 1,442 |
| Public/token/direct-access review candidates | 785 |

These feature counts are deliberately broad static flags and overlap. For example, a linked PDF name can make a parent page a `print/pdf` evidence row even when the parent does not render the PDF itself.

## Classification rules

- `menu`: directly linked from a discovered first-party menu file.
- `linked`: referenced by another first-party PHP/JavaScript source.
- `handler`: receives a form/AJAX request or predominantly performs a write/response.
- `print/pdf`: filename or code invokes a PDF/print library or browser printing.
- `public/token candidate`: no standard header/session guard detected, or a token-like request parameter is present. This is a review flag, not a security conclusion.
- `unknown`: source exists but static evidence is insufficient to prove active, duplicate, obsolete, or inaccessible status.

## Major legacy domains discovered

The source contains substantially more business domains than the approved narrow scope, including legal cases and sessions, labor and medical cases, payment guarantees, medical-service agreements, admission consent, emergency and incident reporting, governmental services, inspection visits, circulars, correspondence, complaints, inquiries, HR requests, training, appointments, operational reports, administrative master data, branch accounting, and signing/integration utilities.

## High-risk review observations

- Some legacy permission checks contain hardcoded user IDs as bypasses. These are evidence of legacy behavior but must not be replicated in Laravel.
- Scope is commonly enforced with session company/branch fields inside SQL. Static presence is recorded per endpoint; correctness requires query-level review and production-like tests.
- Numerous endpoints appear callable without the standard page header. Some are intended AJAX/PDF/public endpoints; others require direct-access security review.
- Multiple filenames carry `Bk`, `BK`, numeric suffixes, spelling variants, or duplicate-looking names. No file is classified duplicate without call-site and behavior evidence.
- PDF and file families are extensive and use multiple storage roots. Source presence does not prove that production files were copied.

## Evidence families

Detailed rows are in `LEGACY_INVENTORY.tsv`. The sections below summarize discovered navigation and endpoint families.

### Navigational entry points

The active-looking menu/header sources are `header.inc.php`, `admin/main_menu.php`, `branch/main_menu.php`, the admin/branch mail menus, and their associated header includes. Static menu parsing found 214 unique literal or dynamic `.php` link expressions and resolved 179 first-party files by basename. Dynamic expressions such as `report_<?php echo ... ?>.php` represent multiple branch-specific pages and must be expanded using production branch data.

Representative root menu domains include absence notification, leave and permission requests, briefings, circulars, inspection visits, governmental services, complaints, inquiries/services, technical failures, corporate communications and outgoing letters, medical reports, agreements/guarantees, appointments, legal case families, employee evaluation, and branch-specific reports. Admin navigation exposes users/groups/permissions plus a large set of operational lookup/master-data pages. Branch navigation exposes branch users, groups, registration, inventory/accounting, and reports.

### Permission and scope evidence

`includes/permissions.class.php` derives the permission key from the current PHP basename, checks the `page` table, then checks direct `user_permission` and inherited `user_groups_permission` rows. `includes/permissions.link.php` controls menu visibility from `user_groups_permission`. Branch equivalents use `branch_user_groups_permission` and branch session scope. Therefore the implied permission key for a protected page is generally its filename without `.php`, even when no literal permission call appears in that page.

The legacy permission class also bypasses denial for session user IDs 1 and 2, and branch menu code contains similar numeric-ID bypasses. This is concrete legacy evidence but is an unsafe identity hardcode and must not be reproduced. Laravel must express privileged roles through data, enforce permissions server-side, and apply company/branch scope to every query and action.

Common scope evidence is `$_SESSION` company/branch/user state embedded in queries. The TSV records whether company/branch terms occur, but a missing term cannot prove global intent and a present term cannot prove correct isolation.

### Endpoint families

Filename-family counts below overlap and are navigation aids, not unique modules:

| Family | First-party files matching family |
|---|---:|
| Authentication/account | 12 |
| Users/permissions/groups/roles | 53 |
| Complaints/patient relations | 84 |
| Inquiries | 34 |
| Correspondence/mail | 93 |
| Circulars/briefings | 19 |
| Inspection/government reports | 29 |
| HR/employee requests | 79 |
| Training/course | 46 |
| Appointments/booking | 30 |
| Legal, labor, medical, commercial, administrative cases | 107 |
| Guarantees/contracts/admission consent | 106 |
| Reports/department reports | 288 |
| Filename print/PDF endpoints | 287 |
| External integration naming (SMS/mail/Yakeen/Sadq/Emdha) | 206 |

### Forms, actions, workflow, and validation

The TSV extracts literal form targets, inferred action families (`create`, `update`, `delete`, `approve`, `reject`, `send`, `reply`), numeric status comparisons, linked child PHP pages, and SQL tables per file. Validation is implemented inconsistently through HTML attributes, page-local PHP, `includes/validationForm.php`, token helpers, and AJAX handlers. Empty form actions mean self-submit. Dynamic targets and SQL assembled across includes require manual tracing.

Status numbers are intentionally recorded without invented labels. Labels and approval meaning must be resolved from page conditionals, lookup tables, and production rows per workflow. Numeric matches can include unrelated fields, so they are evidence candidates rather than a definitive status map.

### Attachments, print, and public routes

File roots found include `absence_notification_service_files`, `claiming_against_others_files`, `corpse_files`, `payment_guarantee_files`, `files`, `files/birthchilds`, `branch/upload`, `emdhaFiles`, `sadq/uploads`, and signature/Emdha output/temp/log directories. Upload/download/delete behavior is distributed across form pages and handlers; filenames alone do not establish authorization.

The application includes multiple PDF stacks and many dedicated `_pdf`, `tcpdf`, `print`, receipt, letter, agreement, report, and confirmation pages. Public/token candidates include reply/confirmation links and integration callbacks, but static absence of a standard header is not enough to determine whether an endpoint is intentionally public. Each of the 785 review candidates needs call-chain and runtime authorization review.

### Activity and duplicate status

All 1,865 first-party rows remain `unknown` for activity unless direct menu evidence is present. Backup-looking suffixes (`Bk`, `BK`), spelling variants, copies with numeric suffixes, and parallel TCPDF/HTML PDF implementations were not classified obsolete or duplicate. Production access logs, stored `page` rows, client decisions, or call-site equivalence are required evidence.

## Required follow-up

1. Export production `pages`/permission tables and compare stored links to this register.
2. Capture production web-server rewrites and entry-point rules.
3. Confirm active companies, branches, roles, and representative users in a sanitized clone.
4. Inventory production attachment roots and compare checksums/counts by legacy family.
5. Resolve every `unknown`/public candidate by authenticated browser tracing before declaring obsolete or migrated.
