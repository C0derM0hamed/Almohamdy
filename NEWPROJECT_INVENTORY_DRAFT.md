# NewProject Inventory Draft

## Purpose and Method

This is a code-derived inventory of the Laravel 12 application as found on 2026-07-31. It inventories what `NewProject` currently exposes; it does not assert legacy parity. Evidence was collected from `php artisan route:list --json`, `routes/web.php`, controllers, requests, services, repositories, models, Blade templates, navigation configuration, middleware, public assets, package manifests, and tests.

Counts at inventory time:

| Surface | Count | Notes |
|---|---:|---|
| Registered routes | 138 | Includes Laravel health and local storage routes; 135 with `--except-vendor` |
| Controllers | 46 | Includes base controller and currently unreachable placeholder controller |
| Form requests / request concerns | 49 | Validation and action authorization |
| Services | 38 | Includes authentication, navigation, workflow, PDF/export, SMS |
| Repositories | 26 | Legacy-schema queries and writes |
| Eloquent models | 80 | Mostly explicit legacy table mappings |
| Blade templates | 146 | Includes layouts, partials, email, public, PDF, and unused templates |
| PHP test files | 6 | Two example tests plus four focused application tests |
| Translation domains | 23 per locale | Arabic and English pairs |

There is no `routes/api.php`, no API controller namespace, and no first-party JSON API. AJAX is limited to inquiry status/timeline interactions against normal web routes.

## Global Application Surfaces

| Area | Routes / entry points | Implementation | Authorization / scope | Views / output |
|---|---|---|---|---|
| Login | `GET /`, `GET/POST /login` | `Auth\LoginController`, `LoginService`, `UserRepository` | Guest-like session flow; active user lookup in `ra_users` | `auth.login`, redirect-replace partial |
| OTP | `GET/POST /otp`, `POST /otp/resend`, `GET /otp/cancel` | `OtpController`, `OtpService`, `LoginOtpMail` | `otp.pending`, attempt/expiry configuration | `auth.otp`, email template |
| Forgot password | `GET/POST /password/forgot` | `PasswordRecoveryController` | Generic validation/response; no reset-token/password update route is present | `auth.forgot-password` |
| Logout | `POST /logout` | Invokable `LogoutController` | `auth.session` | Redirect |
| Locale | `GET /lang/ar`, `/lang/en` | `LocaleController`, `SetLocale` | Public web middleware | Arabic/English translations; app layout sets language/direction |
| Dashboards | `/dashboard`, `/admin/dashboard`, `/branch/dashboard` | three dashboard controllers, shared resolver/services | `auth.session`; redirects by user level | `dashboard.home` |
| Health | `GET /up` | framework | Public | Framework response |

`EnsureAuthSession` reloads the current legacy user, rejects disabled accounts, and hydrates `hm_permissions`. `PreventPageCaching` is applied to login/OTP. The app middleware aliases are `auth.session`, `otp.pending`, `permission`, `admin`, `permission.admin`, and `prevent.cache`.

## Module and Route Inventory

### Doctors Directory

| Surface | Routes / actions | Backend | Data | UI |
|---|---|---|---|---|
| Directory entry | `GET /modules/doctors-directory` redirect | route closure | n/a | redirects to specialities |
| Specialities | list, departments by speciality | `SpecialityController`, `SpecialityService/Repository` | `specialized_clinics`, outpatient/branch relationships | speciality index and departments pages |
| Doctors | department list, branch list, OPD list, doctor detail | `DoctorController`, `DoctorService/Repository` | `clinicians`, `clinician_hospitals`, `outpatient_clinics`, sections/duty data | doctor cards, popup partials, detail |
| Administration | dashboard; speciality/department/doctor list/create/edit/update/publish; assignment create/delete | `DoctorsDirectoryAdmin` controllers/services/repositories and 12 form requests | clinician, speciality, outpatient section and assignment tables | complete CRUD-oriented Blade set |

All directory read routes use `auth.session`. All administration routes use `auth.session, admin`. No delete route exists for doctor, speciality, or department; administration exposes publish/unpublish and assignment deletion. Doctor photos resolve through configured legacy `/files` paths and public assets.

### Service Locations

Routes cover dashboard/index, floors, floor detail, OPD location detail, OPD support services, and an OPD doctors link. `ServiceLocationController`, `ServiceLocationService/Repository`, and the doctors controller back these pages. Principal tables include `outpatient_clinics`, `outpatient_clinics_sections`, `floors`, `floors_details`, `clinics_support_services_sectionbk`, and `clinics_support_services_details`. All routes are authenticated but have no explicit route permission. Empty location/floor results display “coming soon,” so data absence can look like incomplete functionality.

### Hospital Services

Routes cover the services dashboard, generic section detail, private rooms, and redirect aliases for laboratory, radiology, and agreements. Sections 1, 2, 3, 4, 6, 11, and 12 are linked in navigation. Backends are `HospitalServicesDashboardController`, `ServiceSectionController`, `PrivateRoomController`, `HospitalServiceCatalogService`, and three repositories. Models/tables include `services_sections`, `service_packages`, `service_packages_attachments`, and `admission_rooms`. The app contains unused controller files named `CompanyAgreementController`, `LabServiceController`, and `RadiologyServiceController`; registered routes use redirects/generic section rendering instead. All routes are authenticated without explicit permission middleware.

### Complaints

Routes cover dashboard, filtered list, detail, and standalone/AJAX-compatible timeline. `ComplaintsDashboardController`, `ComplaintController`, `ComplaintService`, three repositories, and timeline presentation support read-only complaint review. Tables are `complaints`, `complaints_reply`, and `complaints_status`. There are no NewProject create, reply, status-update, attachment-upload, print, or PDF routes for complaints. Access uses `auth.session`; branch/company enforcement must therefore be verified in repository/service queries rather than route middleware.

### Inquiries

Routes cover incoming/outgoing filtered lists, timeline, PDF download, and incoming status update. `InquiryAndServiceController`, `InquiryAndServiceService/Repository`, `InquiryTimelineService`, and `InquiryPdfService` support the module. Data maps to `inquiries_and_services`, `inquiries_and_services_reply`, and `inquiries_and_services_status`; the separate `inquiries` model is present but is not the primary module repository. Browser JavaScript fetches timeline HTML and submits status changes to these web routes. Status update rejects outgoing direction in the controller. There is no inquiry create route or standalone detail page. Authentication is required, with company/branch/direction scope implemented in repository/service/controller rather than permission middleware.

### Employee Leave

Routes cover dashboard, request list, create/store, detail, branch processing, and HR processing. `LeaveRequestController`, service/repository, workflow status resolver, form requests, and authorization concerns map to `emp_vacations`, `client_vacations_branch_reply`, `client_vacations_hr_reply`, and `approval_status`. Branch and HR actions explicitly require `employee_leave.branch_process` and `employee_leave.hr_process`; basic list/create/show routes only require authentication and rely on service/repository scope. No attachment, export, print, PDF, edit, or delete route is registered.

### Work Absence Notification

Routes cover dashboard, filtered notification list, detail, CSV/Excel-compatible export, processing, memo creation, and activation. `NotificationController`, dashboard controller, four services, two repositories, permission constants, timeline/workflow resolvers, and seven requests support the flow. Tables/models include `absence_notification_service`, action types, memos, recipients, service types, `memo_types`, and `ra_users`. Explicit route permissions are:

- `work_absence_notification.view`
- `work_absence_notification.export`
- `work_absence_notification.process`
- `work_absence_notification.activate`

No creation route for the original absence notification exists; NewProject manages existing notices. Export is streamed rather than a Blade print/PDF page.

### Employee Services

`GET /modules/employee-services` renders a navigation dashboard. Only work absence routes are included in its configured cards/sidebar. The legacy-placeholder controller and `modules/placeholder.blade.php` remain in source but have no registered route. The dashboard still has empty-state “coming soon” presentation. Training management, training coordination, and appointment request routes are not registered.

### Government Circulars

Routes cover list, create/store, detail, departments, receipt, and status update. Controller, service/repository, three requests, and circular models cover attachments, issuing authorities/classifications, notification types, receiving mechanisms, departments/administrators, receipt reports, and statuses. Upload validation accepts PDF/Office/image formats, and storage supports Laravel public-disk paths plus legacy public paths. A public formal view exists at `GET /public/government-circulars/formal/{token}`; it has no POST action. Internal routes use only `auth.session`, with workflow and scope checks located below route level.

### Government Inspection Visits

Routes cover list, create/store, detail, receipt, status update, and additional attachment upload. Two public reply workflows support ordinary and returned replies, each with GET/POST token routes. Controller/service/repository and five requests cover visit types/numbers, findings, attachments, status/timeline, returned entries, reply submissions, and receipt reports. Public reply upload validation is present. Stored public-disk files and several legacy public path patterns are resolved. Internal routes are authenticated without explicit page permission middleware.

### Government Data Requests

Routes cover list, create/store, detail, receipt, and status update. Public department reply GET/POST token routes accept response files. Controller/service/repository and three requests map legacy `g_*` tables: requests, entities, data types, receiving methods, statuses, timeline, views, mail files, and answer files. Attachment URLs fall back to legacy `public/report_file`. Internal routes are authenticated without explicit page permission middleware.

### Correspondence

The corporate communication dashboard is separate from the correspondence list. Correspondence routes cover list, create/store, detail, receipt, and status update; public department replies use GET/POST token routes. Controller/service/repository and requests map communication records, statuses/timeline, attachments, replies/reply attachments, sectors, sender titles, receipt reports, and several delivery/shipment action tables accessed through raw queries. Uploads use the public disk and legacy fallback resolution. Internal routes use `auth.session` only.

### Outgoing Correspondence

Routes cover list, create/store, detail, printable HTML, and status update. A public revise GET/POST token workflow is registered. Controller/service/repository and requests map outgoing letters, attachments, status/timeline, supplementary records, templates, return/shipment/action tables, and recipient metadata. `outgoing-correspondence.print` is a browser print view, not a generated PDF. Internal routes use `auth.session` only.

### System Administration

| Surface | Routes / actions | Authorization | Notes |
|---|---|---|---|
| Dashboard | module dashboard | `admin` | configured cards list packages and doctors directory admin |
| Service packages | list, edit/update, publish, delete | `admin` | no create/store route; manages existing legacy packages |
| Users and permissions | list, detail, create/store, edit/update | `permission.admin` | supports user identity, company/branch, group, direct/inherited/effective permissions |

User administration uses `UserPermissionAdminService/Repository`, `SaveUserRequest`, and legacy `ra_users`, `companies_groups`, `branches`, `user_groups`, `user_permission`, and `user_groups_permission`. Super administrator is data-driven by configured user level `3`. Permission administrators require effective legacy permissions `users` and the legacy-spelled `user_groups_permissins`; scope and escalation checks are server-side. There is no user delete route. Important navigation gap: user management is absent from both `config('hm.navigation.sidebar')` and `config('hm.system_administration.cards')`, so it is route-reachable but not normally discoverable from configured navigation.

## Authorization and Scope Inventory

`PermissionRepository` unions allowed `page` values from direct `user_permission` and inherited `user_groups_permission` rows. `PermissionService` stores effective pages in session, recognizes admin level `3`, and supports a deployment-configurable bypass (`HM_PERMISSIONS_BYPASS`, default false). `EnsureAdmin` permits configured admin levels. `EnsurePermissionAdministrator` delegates to the user-management capability check.

Explicit route-level page permissions exist only for work absence actions and employee leave processing. Doctors administration and service-package administration use the broad admin gate. User management has its specialist gate. Complaints, inquiries, circulars, inspections, data requests, correspondence, outgoing correspondence, service locations, hospital services, and their write/status routes have no explicit `permission:*` route middleware. Their branch/company restrictions and action eligibility, where implemented, live in request authorization, controllers, services, or repository queries and require module-by-module testing.

The configured sidebar has doctors, service locations, incoming/outgoing inquiries, hospital service sections, five corporate-communication children, two absence children, and an admin group. It does not directly link complaints, employee leave, the employee-services dashboard, users/permissions, or private rooms. Dashboard cards provide some otherwise absent entry points (complaints and employee services), but employee leave and user administration remain especially dependent on indirect links or manually known routes.

## Attachments, Downloads, Print, and PDF

| Capability | Present implementation |
|---|---|
| Inquiry PDF | `InquiryPdfService`, `inquiries/pdf/report.blade.php`; mPDF/DOMPDF and Arabic PHP dependencies are installed |
| Outgoing letter print | authenticated HTML print route and `outgoing-correspondence/print.blade.php` |
| Receipts | circular, inspection, data request, and correspondence receipt Blade pages |
| Absence export | streamed export route supporting CSV/Excel-oriented formats |
| Upload domains | circulars, inspection visits/replies/notices, data requests/answers/notices, correspondence/replies/status, outgoing correspondence/status, doctors, service packages |
| Public token uploads | correspondence replies, data-request replies, inspection replies; outgoing revise is form-based |
| Storage strategy | new uploads use `public` disk; services also resolve selected legacy `public/files`, `public/report_file`, and stored relative paths |

`public/storage` is a physical directory containing only `.gitignore`, not a verified Laravel symlink. `public/files` contains legacy-looking clinician images. Complete production file inventory/mapping cannot be inferred from source and must be tested against the deployed file corpus. There are no dedicated authenticated download controllers; most attachment links are generated asset/storage URLs, so access control at the file URL layer is not evident.

## Models and Legacy Tables

The 80 Eloquent models map these major table families:

- Identity/scope: `ra_users`, `branches`, `branch_users`, `branches_departments`, `companies_groups`.
- Doctors/locations/services: `clinicians`, `clinician_hospitals`, `clinics`, `outpatient_clinics`, `outpatient_clinics_sections`, `specialized_clinics`, `services_sections`, `service_packages`, `service_packages_attachments`, `admission_rooms`, holiday offer tables.
- Complaints/inquiries: `complaints`, `complaints_reply`, `complaints_status`, `inquiries`, `inquiries_and_services`, `inquiries_and_services_reply`, `inquiries_and_services_status`.
- Leave/absence: `emp_vacations`, branch/HR reply tables, `approval_status`, and six absence/memo tables.
- Circulars: circular, attachment, authority/classification, notification, receiving, section/admin/type, status, and receipt-report tables.
- Inspection: visit, type, number, finding, attachment, status, timeline, returned, reply-submission, and receipt-report tables.
- Data requests: `g_data`, `g_sections`, `g_sectionsub`, `g_requestdelivry`, `g_status`, `g_timestatus`, `g_view`, `g_filesmail`, `g_filesanswer`.
- Corporate communication: incoming and outgoing record, attachment, reply, status, timeline, receipt, sector/title, template, and supplementary tables, plus raw-query action/delivery/shipment tables.

Permission tables are accessed through query builder rather than models: `user_permission`, `user_groups_permission`, and `user_groups`.

## Integration and Scheduled Work

- Mail: login OTP and department reply link mailables.
- SMS: `SmsGateway` supports `log`, `mshastra`, and `synapse`; credentials are environment-driven. Live providers must not be exercised locally.
- Scheduler: `hm:cc-send-daily-reminders` runs daily at configured time (default 07:00 Asia/Riyadh), without overlap.
- Console utilities: clinician assignment/name diagnostics and repairs, OPD diagnosis, daily reminders, and notification smoke test.
- No first-party external REST API is exposed. No webhook receiver was found.

## UI, RTL, and Components

The application layout sets `lang` and `dir`, adds locale-specific body classes, and has responsive sidebar/navbar components. Hope UI compatibility is implemented through bridge/overlay/module CSS plus Bootstrap icons. Domain CSS exists for complaints, doctors, employee leave/services, government/corporate modules, hospital/service locations, inquiries, and absence notifications. Shared Blade components are implemented as partials rather than Laravel class components: breadcrumbs, searchable select, pagination, cards, timeline modals, receipt bodies, clinician popup/card parts, and layout parts. Arabic and English domain translations are paired across 23 translation files.

There is no evidence in this inventory alone of completed viewport screenshot testing for every page. Unused `welcome.blade.php` still references a nonexistent `register` route, but no registered application route renders that view.

## Tests Present

Focused tests are limited to:

- `Feature/UserPermissionManagementTest.php`
- `Unit/ComplaintTimelineTest.php`
- `Unit/InquiryLegacyCompatibilityTest.php`

The remaining feature/unit example tests are framework scaffolding. There is no current focused automated coverage for login/OTP, module CRUD, leave/absence workflow, attachment upload/download, token workflows, PDF/print, menu parity, or branch/company isolation across all modules.

## Inventory Findings Requiring Gap-Matrix Attention

1. NewProject inventory cannot establish old-to-new parity by itself; every item above must be matched to the legacy inventory.
2. Most authenticated business actions lack explicit route-level page permission middleware. A controller/service scope may be valid, but each must be evidenced and tested against legacy permission records.
3. User and permission management is implemented but missing from configured sidebar and system-administration dashboard cards.
4. Complaints are read-only in NewProject and have no attachment, reply, workflow-write, print, or PDF endpoints.
5. Inquiries have list/timeline/PDF/status update, but no create or standalone detail route.
6. Employee leave has no attachment, print/PDF, edit, or delete surface; only processing actions have explicit permissions.
7. Work absence manages existing notifications but has no source-notification create route.
8. System service-package administration has no create route; user administration has no delete route.
9. Several pages are reachable only indirectly or not represented in sidebar configuration: complaints, employee leave, users/permissions, private rooms, and employee-services overview.
10. `ModulePlaceholderController` and its Blade remain dead source. Empty dashboard/location states also use “coming soon,” which should not be mistaken for implemented parity.
11. Attachment access is mostly direct public URL generation, not authenticated download routing. Legacy file mapping and access-control parity remain unproven.
12. Forgot password has a request form but no reset-token/password-change workflow in registered routes.
13. Automated coverage is materially narrower than the exposed application surface.

## Primary Evidence Paths

- `routes/web.php`
- `bootstrap/app.php`
- `config/hm.php`
- `app/Http/Controllers/`
- `app/Http/Requests/`
- `app/Services/`
- `app/Repositories/`
- `app/Models/`
- `resources/views/`
- `resources/views/layouts/partials/sidebar.blade.php`
- `public/css/` and `public/js/`
- `composer.json`
- `tests/`

