# Navigation and Permission Parity Draft

## Scope and method

This draft audits navigation and access-control evidence only. `OldProject` was read-only. The audit starts from the three legacy application shells and follows explicit menu links, dynamic links, form actions, AJAX URLs, redirects, and the legacy permission implementation into `NewProject` routes, sidebar configuration, and middleware.

Evidence base:

- Legacy employee shell: `OldProject/header.inc.php`
- Legacy branch shell: `OldProject/branch/header.inc.php`
- Legacy super-administrator shell: `OldProject/admin/header.inc.php`, which includes `OldProject/admin/main_menu.php` at line 686
- Legacy authorization: `OldProject/includes/permissions.class.php` and `OldProject/includes/permissions.link.php`
- Laravel routes/navigation: `routes/web.php`, `config/hm.php`, `app/Services/Dashboard/NavigationService.php`, and authorization middleware

After removing HTML-commented blocks, the shell scan found 81 distinct explicit PHP targets in the employee header, 82 in the branch header, and 25 in the admin menu. These are not total legacy page counts: dynamic child targets, forms, AJAX, redirects, and database page records expand the reachable graph. Repository-wide static indicators include 282 PHP files containing a form action that references PHP, 703 PHP/JS files containing a PHP URL reference, and 1,106 PHP files containing redirect indicators. Vendor/template files can inflate the broad counts, so each endpoint still requires page-level confirmation.

## Critical findings

### P0: Laravel module authorization is mostly authentication-only

All routes from `routes/web.php:94` through line 374 are inside `auth.session`, but most modules have no page-permission middleware. Explicit server-side page permissions exist only for:

- system administration (`admin`) at `routes/web.php:119-134`
- user/permission management (`permission.admin`) at `routes/web.php:135-142`
- doctors-directory administration (`admin`) at `routes/web.php:143-189`
- leave processing actions at `routes/web.php:232-239`
- absence view/export/process/activate at `routes/web.php:242-267`

Complaints (`269-279`), government circulars (`282-298`), inspection visits (`299-315`), data requests (`316-329`), correspondence (`330-343`), outgoing correspondence (`344-357`), and inquiries (`358-373`) have no route-level legacy page-permission middleware. The same is true for public-facing doctors, service locations, and hospital services (`101-224`). Authentication alone therefore grants route reachability to any active session unless every controller independently reproduces the missing authorization. This is not navigation/access parity and requires controller-by-controller confirmation before delivery.

### P0: Sidebar visibility is not legacy-permission driven

`config/hm.php:78-263` defines a fixed sidebar. `NavigationService::resolveSidebarItem()` and `resolveGroupItem()` remove missing routes and `admin_only` items, but `NavigationService.php:293-298` special-cases only `modules.work-absence.*`. Consequently, complaints, inquiries, corporate communications, circulars, visits, data requests, directories, and hospital services are shown without checking their corresponding `page` permission.

There is also no users/permissions sidebar item even for an authorized Permission Administrator: the system-administration group is `admin_only` (`config/hm.php:231-261`), and its children omit `modules.system-admin.users.index`. Thus the implemented permission-admin route is not reachable through normal navigation for a non-super permission administrator.

### P0: Legacy permission namespace must be extracted from production data

Legacy authorization derives the current basename without `.php`, queries `page.page`, then checks `user_permission(userid,page,permit)` and `user_groups_permission(groupid,page,permit)` (`OldProject/includes/permissions.class.php:3-17,23-54`). Permit `1` denies and permit `2` permits (`:59-76`). Crucially, enforcement runs only when the current basename exists in `page` (`:14-17`). Menu-link visibility uses the group table independently (`OldProject/includes/permissions.link.php:6-22`).

Laravel loads both direct and group rows (`app/Repositories/Auth/PermissionRepository.php:12-47`) and treats all returned non-deny permissions as grants, but routes do not generally request those grants. A sanitized export of `page`, `user_permission`, and `user_groups_permission` is required to map every active database-stored permission name. Source scan alone cannot prove which of 4,354 legacy PHP files are registered or granted in production.

### P1: Legacy contains an identity hardcode that must not be migrated

`OldProject/includes/permissions.class.php:78` bypasses authorization for user IDs 1 or 2. This conflicts with the client rule against privileged IDs and must remain deliberately absent. Laravel super-administrator handling is data-driven through configured level `3` (`config/hm.php:17-20`, `PermissionService.php:82-87`).

### P1: Legacy role shells and scope conditions are broader than Laravel navigation

The shells enforce different user levels: employee level `0` (`OldProject/header.inc.php:122-131`), super administrator level `3` (`OldProject/admin/header.inc.php:136-150`), and a branch-level allow-list (`OldProject/branch/header.inc.php:93`). Menu entries additionally depend on company, branch, job title, and database rows. Examples:

- Employee governmental/corporate menus require branch/company combinations at `header.inc.php:2496-2555`.
- Employee inquiries require company 1 or 3 at `header.inc.php:2686-2702`.
- Branch employee services expose vacation, permission, duty-time, resignation, evaluation, absence, training, and coordination links at `branch/header.inc.php:1340-1401`.
- Branch hospital functions are restricted by explicit branch sets at `branch/header.inc.php:1473-1632`.
- Service-location children are database-driven and company-filtered (`header.inc.php:2262-2269`; branch equivalent at `branch/header.inc.php:1673-1699`).
- Report filenames are selected from branch ID (`header.inc.php:2626-2658`; `branch/header.inc.php:1783-1813`).

Laravel refreshes `hr_user_level`, `hr_branch_id`, `companies_groups_id`, and `groupid` from the database on each authenticated request (`EnsureAuthSession.php:24-49`), which is a sound base, but the fixed sidebar does not reproduce most legacy branch/company/job-title menu gates.

## Legacy menu-to-Laravel matrix

The rows below group only links proven active in non-commented shell markup. A group is **partial** when Laravel implements some functions but omits sibling pages/actions.

| Legacy menu area / targets | Legacy evidence | Laravel equivalent | Navigation/access status |
|---|---|---|---|
| Doctors/clinicians: `clinicians_view.php`, dynamic `clinic_details.php` | `header.inc.php:2064-2128`; branch `:1123-1230` | `modules.doctors.*`, `modules.doctors-admin.*` | Partial. Reachable; public directory has auth only. Admin CRUD has `admin`. Legacy page permission not mapped. |
| Service locations: dynamic outpatient clinics, floors, central sections | `header.inc.php:2218-2325`; branch `:1660-1710` | `modules.service-locations.*` | Partial. Main/floor/OPD/support routes exist. Fixed visibility replaces company-filtered legacy menu behavior. |
| Hospital services and service details | `header.inc.php:2116-2192`; branch `:1412-1632` | `modules.hospital-services`, `modules.services.*` | Partial. Generic section pages cover some content, but many branch-specific forms have no route and all present routes are auth-only. |
| Complaints: `complaints.php`, request/report/confirm, deleted basket, public reply/check and PDFs | employee `header.inc.php:1312-1353`; branch `:632-660,1830-1841`; indirect endpoints include `complaint_reply_form.php`, `complaintCheck.php`, `complaints_pdf.php` | dashboard/list/show/timeline only (`web.php:269-279`) | Missing/partial. No create/request, report, confirmation, trash, public reply/check, or complaint PDF route; no complaint permission middleware. |
| Inquiries: sender, receiver, reports, PDF/public print | `header.inc.php:2680-2702`; branch `:1921-1941`; `governmental_services_pdf_7.php:193` links `inquiriesPrint.php` | outgoing/incoming/timeline/PDF/status (`web.php:358-373`) | Partial. Main workflow exists, but no legacy page-permission middleware and public print/token parity needs confirmation. |
| Government circulars | `header.inc.php:2497-2504`; AJAX classification endpoints at `government_circulars.php:2383-2412`; token page `government_circulars_form.php` | CRUD/status/receipt/departments and public formal show | Partial. Core route set exists. No module permission middleware; AJAX semantics and all legacy token behavior need action-level proof. |
| Inspection visits | `header.inc.php:2503-2512`; AJAX at `government_inspection_visits.php:3657-3686`; token/PDF pages | CRUD/status/attachment/receipt and public reply/returned reply | Partial. Core routes exist, but no module permission middleware and legacy PDF/menu report parity is unproven. |
| Data requests / government reports | `Governmentـreportss.php` at `header.inc.php:2510-2512` and related public form chain | `modules.data-requests.*`, public reply | Partial. Reachable in Laravel sidebar, but no legacy permission middleware; legacy report equivalence not proven. |
| Correspondence | `corporate_communications.php` at `header.inc.php:2519-2538`; AJAX issuing-authority endpoints at `:4823-4866`; public reply chain | `modules.correspondence.*`, public reply | Partial. Core route set exists, but module permission is absent and not every print/PDF/action has a mapped route. |
| Outgoing correspondence | `corporate_communications_outgoing_letters.php` at `header.inc.php:2545-2547`, branch `:1329-1331`; confirm, revise, shipment/return/delivery/supplementary PDFs are indirect children | CRUD/status/show/one print route plus public revise | Partial. Multiple verified legacy print/delivery/confirmation targets are not separately represented; no module permission middleware. |
| Employee leave | employee request links; branch request and admin processing links at `branch/header.inc.php:1349-1376,1724-1754`; admin menu `main_menu.php:164-185` | leave dashboard/request/create/show and branch/HR process | Partial. Leave request is implemented and process actions protected, but sidebar has no leave item. Permission request, duty-time, resignation, and evaluation siblings are missing. |
| Absence notifications | `header.inc.php:2603-2605`; branch `:1381-1389`; report `:1823-1825` | absence dashboard/list/show/export/process/memo/activate | Best-aligned. Route permissions and sidebar visibility use named grants. Legacy report/PDF/upload child parity still needs separate page audit. |
| Training confirmation/coordinator | branch menu `branch/header.inc.php:1393-1401` | none | Missing. Active non-commented menu evidence exists; it cannot be classified obsolete from source alone. |
| Appointment requests | `book_a_medical_appointment.php` and clinicians link at `header.inc.php:2737-2752`; branch has same target | none | Missing. Visibility is branch/company conditional, but link is active. Client decision or full workflow migration required. |
| Legal workflows | lawsuits, executive titles, commercial/labor/medical/administrative cases and archives at `header.inc.php:2333-2480` | none | Missing. These are active menu links with branch restrictions; no Laravel routes/navigation. |
| Operational branch forms | birth notification, admission calculators, emergency, approvals, psychosocial, medicine codes, technical failures, governmental services, referral/bed/crisis/red-crescent forms | numerous branch links at `branch/header.inc.php:570-859,1473-1632` | Mostly missing. No Laravel navigation or routes except generic hospital/service content. Must be audited individually, not hidden by current sidebar. |
| Reports | dynamic `report_{branch}.php`, `rep_{branch}.php`, employee reports, complaints/thanks/evaluation/posts/inquiry/absence reports | `header.inc.php:2619-2660`; branch `:1766-1861` | Missing/partial. Laravel has isolated export/PDF/print routes but no equivalent report navigation or complete report set. |
| Super-admin users/groups | `users.php`, `adm_user_groups.php`, child `user_groups_permissins.php` (`admin/main_menu.php:175-181`; `adm_user_groups.php:370`) | `modules.system-admin.users.*` | Partial. User CRUD and combined group/direct/effective permissions exist. Authorized non-super permission administrator lacks sidebar access. Standalone groups management parity needs confirmation. |
| Super-admin reference/config pages | complaints settings, posts, terminology, service codes, inquiries types, government types, companies/branches/departments/needs/areas/service types | active entries in `admin/main_menu.php:27-75,214-224,376-397` | Mostly missing. Laravel system admin currently exposes packages and doctors only (`config/hm.php:317-351`). |

## Hardcoded and indirect navigation evidence

These endpoints must be treated as page/function inventory even when absent from a menu:

- Complaint dependent selects call `rep12_main_section_grap.php`, `rep12_sub_section_client_grap.php`, and `rep12_sub_section_employee_grap.php` (`complaint_request.php:1123-1681`, repeated in branch copy).
- Complaint public links and PDFs use `complaintCheck.php`, `complaint_reply_form.php`, and result pages (`complaintCheck.php:310`; `complaint_reply_form_result.php:317`; `complaints_pdf.php:366`).
- Circulars and inspection pages call issuing-authority/classification graph handlers (`government_circulars.php:2383-2412`; `government_inspection_visits.php:3657-3686`).
- Correspondence and outgoing letters call `government_circulars_issuing_authority_grap.php` (`corporate_communications.php:4823-4866`; `corporate_communications_outgoing_letters.php:4506-4549`).
- Public correspondence replies post to themselves and redirect to a result page (`corporate_communications_form_reply.php:268-373,861`).
- Outgoing confirmation posts to `corporate_communications_outgoing_letters_confirm.php` and redirects to the list (`:236-272,784`; branch copy equivalent).
- Legacy print variants include automatic elevator, shipment request, return shipment, postal delivery, agency delivery, supplementary letter, complaint letter, complaint approval, and inspection PDFs. A single generic Laravel print route is not evidence that all layouts/actions were merged.

## Authentication and profile navigation differences

- All shells link to `change_my_pass.php`, `signature.php`, and logout in profile menus (employee header around `:1903-1944`; branch `:1069-1088`; admin header around `:650-680`). Laravel has POST logout but no authenticated change-password or signature route in `web.php`.
- Legacy logout is a GET link. Laravel correctly uses POST (`web.php:95`), but every Blade navigation must use a form/button rather than a dead GET link.
- Legacy notifications deep-link to `view_post.php?id=...` and `inquiries_and_services_receiver1.php?id=...` (`header.inc.php:1774-1840`; branch `:941-1000`). Laravel notifications are disabled (`config/hm.php:266-269`), so these navigation functions are currently absent.

## Required implementation order

1. Export and normalize active legacy `page` records and direct/group grants; establish an explicit legacy-page-to-Laravel-route permission map.
2. Apply permission middleware to every protected module route and matching policy/controller scope checks. Do not rely on menu hiding.
3. Make `NavigationService` consume the same permission map; add the users route for in-scope Permission Administrators without exposing the super-admin-only group.
4. Reconcile employee, branch, and super-admin active menu targets against the complete page inventory. Restore links only when their backend, forms, children, and scope protections are complete.
5. Implement or document client decisions for the proven active missing groups: legal workflows, operational branch forms, reports, training, appointments, profile password/signature, and super-admin reference data.
6. Add route tests for direct-URL denial, direct and inherited grants, deny semantics, company/branch/job-title isolation, permission-admin navigation visibility, and every public token endpoint.

## Audit limitations and decisions required

- Static source proves that links exist, not that every conditional branch is reached by a current production user. Production branch/company configuration and sanitized permission records are required.
- HTML-commented menu blocks were excluded from active counts and must not be called obsolete without history/client evidence.
- Some legacy conditions appear logically defective, for example the `!=` chain at `branch/header.inc.php:1272`; migration must preserve intended business access, not reproduce an apparent always-true expression without confirmation.
- This report makes no functional edits and does not classify any active target as obsolete or duplicate.
