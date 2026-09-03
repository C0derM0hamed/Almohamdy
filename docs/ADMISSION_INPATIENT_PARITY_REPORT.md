# Admission / Inpatient parity report

Status: **IN PROGRESS — لا تُعد المطابقة النهائية مكتملة قبل توثيق فحص المتصفح لكل دور.**

Source of truth: `/home/mohamed/Downloads/medicalLaravel/OldProject`  
Target: `/home/mohamed/Downloads/medicalLaravel/NewProject`  
Scope: admission, inpatient, medical-procedure clinic navigation, and the financial-claim children shown in the legacy branch-2 menu.

Every row below has a canonical NewProject route. Legacy filenames remain compatibility entry points and redirect into that canonical workflow; no migrated feature depends on a hidden direct URL.

## Sidebar and visibility

| Old behavior | NewProject equivalent | Verification | Status |
|---|---|---|---|
| `الخدمات > التنويم >` section 7, rooms | `services` group → `services_inpatient` → `modules.services.sections.show?section=7` | `LegacyNavigationParityTest::test_inpatient_service_sections_are_nested_and_keep_their_company_rule` | COMPLETE |
| `الخدمات > التنويم >` section 8, reproduction | Same hierarchy, section 8 | Same test; hidden for company 3 as in the old rule | COMPLETE |
| `الخدمات > التنويم >` section 9, endoscopy | Same hierarchy, section 9 | Same test | COMPLETE |
| `الخدمات > التنويم >` section 10, dialysis | Same hierarchy, section 10 | Same test | COMPLETE |
| `الإجراء الطبي > حجز موعد إجراء طبي` | `modules.medical-appointments.index` | Navigation scope and route audit | COMPLETE |
| `الإجراء الطبي > عرض العيادات` / `clinicians_view.php` | `legacy.clinicians-view` → `modules.clinics-directory.index`؛ فلترة العيادة/الفرع/الاسم/الكود، بطاقات الأطباء، تحرير/نشر/إيقاف/حذف حسب الدور | تمّ فحص تحميل الخدمة والواجهة محليًا؛ فحص المتصفح متعدد الأدوار قيد التوثيق | IN PROGRESS |
| Branch 2 financial-claims group and all 11 children | `financial_claims` group in `config/hm.php`, with legal claims, generic claim workflows, and medical-agreement variants | Branch-2 title/route and duplicate-URL assertions | COMPLETE |
| Super-admin sidebar visibility for claims and agreements | Branch/company restrictions remain active for legacy branch groups, preventing branch-2 financial children and branch-scoped agreement workflows from appearing as duplicate menus | `LegacyNavigationParityTest` scope and duplicate-visibility assertions | COMPLETE |
| Branch 3 `القضايا` menu | One nested group replaces the old standalone `المطالبات القانونية` entry: financial, commercial, labor, medical, administrative cases, and executive titles | `LegacyNavigationParityTest::test_branch_three_receives_the_old_cases_menu_without_a_duplicate_legal_claims_entry` | COMPLETE |

The branch-2 children are explicitly present for: claims, executive titles, required claim documents, required executive-title documents, payment guarantee, payment-guarantee archive, financial notice, claim approval, financial inquiry, legal-department inquiry, and medical-report request.

## Admission / inpatient workflow audit

| Old pages / behavior | NewProject canonical implementation | Status |
|---|---|---|
| `admission_calculator.php`, branch calculator variants | `modules.admission-inpatient.calculator.index` with standard and manual modes | COMPLETE |
| Procedure and observation calculator screens | `calculator.create` modes `procedures` and `observation`; persisted child rows and validation retained | COMPLETE |
| Manual calculator and room/procedure screens | Manual calculator mode with the legacy room/procedure fields and `room_type` compatibility column | COMPLETE |
| Calculator edit, delete, PDF, Arabic/English PDF aliases, SMS | `calculator.edit`, `update`, `destroy`, `pdf`, `sms`, plus all root/branch legacy PDF aliases | COMPLETE |
| Preview-only branch calculator | `calculator.preview` and `calculator.preview.store`; preserves non-persisting preview behavior | COMPLETE |
| Nationality, admission status, rooms, service prices | `reference` CRUD routes for `nationalities`, `statuses`, `rooms`, and `service-prices`, including import | COMPLETE |
| Room type and booking period | `reference` CRUD routes for `room-types` and `booking-periods` | COMPLETE |
| Hospitalization place | `reference` CRUD route for `hospitalization-places` | COMPLETE |
| Medical approval statuses and rejection reasons | `reference` CRUD routes with branch-10/admin visibility and legacy delete/language aliases | COMPLETE |
| Report-9 reference tables | `reference` CRUD routes for sections, notices, and actions; lookup aliases retained | COMPLETE |
| Admission consent list/create/edit/delete/publish | `consents` CRUD and toggle routes | COMPLETE |
| Consent reminders, duty decision, timeline and PDF | `consents.reminder`, `duty-decision`, `timeline`, and `pdf` | COMPLETE |
| Consent language forms/templates and template fragments | `consent-templates` CRUD plus legacy Arabic/English/template fragment aliases | COMPLETE |
| Public consent contract approval and PDF | Public token routes `legacy.hospital-admission-consent-contract-approval` and `legacy.hospital-admission-consent-pdf` | COMPLETE |
| Inpatient doctors | `doctors` CRUD/toggle routes, scoped clinician data and legacy `inpatient_doctors.php` alias | COMPLETE |
| Approval notifications | `approvals` CRUD, send action, scope and legacy approval aliases | COMPLETE |
| Approval contacts / collection contacts | `contacts/{kind}` CRUD/toggle routes | COMPLETE |
| Hospitalization packages and package catalog | `packages` CRUD/PDF/toggle and `package-catalog` | COMPLETE |
| Service package administration | Rich system-administration package form with section, bilingual names/notes, details, prices, all discount fields, publish state, attachments, and delete/download actions | COMPLETE |
| `service_packages.php` / `service_packages_details.php` XLSX flow | System-admin package import (`CSV/TXT/XLSX`), section filter, and legacy aliases | COMPLETE |
| Report 9 | `report9` CRUD, child/support files, lookups, PDF, delete, and branch-9 scope | COMPLETE |
| Employee report 9 | `employee-report9` CRUD, child/support files, PDF, delete, and legacy admin/branch aliases | COMPLETE |

## Financial and related action audit

| Old page family | NewProject equivalent | Status |
|---|---|---|
| `lawsuit.php`, `financial_claims.php`, create forms and claim filters | `modules.legal-claims.index`, نموذج ديناميكي لأنواع السداد 1/2/3، استرجاع ضمان الدفع، فلتر الإفادات، ونطاق الفرع/الشركة | اختبار تلقائي وتحميل العرض؛ فحص المتصفح الكامل قيد التوثيق | IN PROGRESS |
| Claim show, actions, approval, attachments and statements | صحيفة الدعوى وPDF التفاصيل، تحديث الحالة بكامل بيانات الطلب/الجلسة/صك الحكم، قائمة الجلسات، طلبات الإفادة وقائمتها، والمستندات | اختبار تلقائي وتحميل العرض؛ فحص المتصفح الكامل قيد التوثيق | IN PROGRESS |
| Installments and payment status | `installments.store` and `installments.paid` | اختبار تلقائي ناجح؛ فحص المتصفح قيد التوثيق | IN PROGRESS |
| Suspension and suspension PDF | `suspensions.store` and `suspension-pdf` | اختبار تلقائي ناجح؛ فحص المتصفح قيد التوثيق | IN PROGRESS |
| Claim PDF and required-document PDF | `legal-claims.pdf`, downloads, and generic required-document workflow | COMPLETE |
| Executive-title pages | `legacy-sidebar` executive-title workflow with scoped CRUD, actions, attachments, documents, PDF and aliases | COMPLETE |
| Financial notice | `legacy-sidebar` financial-notice workflow with filters, create/update, attachment/PDF compatibility | COMPLETE |
| `lawsuitApproval.php` | Generic approval workflow with approve/reject action, reason, action log and scoped data | COMPLETE |
| `sit_rep2.php` and status/answer/reason/PDF actions | Generic `sit_rep2` workflow, status transition, timeline, attachment and PDF routes | COMPLETE |
| `rep_ss.php` and report/reply/reason/PDF actions | Generic `rep_ss` workflow, status transition, timeline, attachment and PDF routes | COMPLETE |
| `archives.php` and archive answer/reason/PDF actions | Generic legal-inquiry archive workflow with scoped CRUD, timeline-compatible actions and PDF alias | COMPLETE |
| Root, `/branch`, and `/admin` legacy financial filenames | Compatibility route families in `legacy-sidebar.php`; each family resolves to the canonical scoped screen | COMPLETE |
| Payment-guarantee / medical-services-agreement variants, Yaqeen fragments, manual flow, PDF and branch copies | `modules.medical-agreements` standard, SADQ and manual variants, with dynamic compatibility aliases | COMPLETE |

## Cases audit (visible branch-3 menu)

| Old case page | NewProject list and actions | Verification | Status |
|---|---|---|---|
| `commercial_cases.php` | مستقل بأعمدة: رقم القضية، المدعي/سجله، المدعى عليه/سجله، مبلغ المطالبة، الحالة؛ مع تحرير الحالة وPDF والإجراءات | اختبارات CRUD/actions/scoping المشتركة نجحت؛ توثيق المتصفح قيد التنفيذ | IN PROGRESS |
| `labor_cases.php` | مستقل بأعمدة: رقم القضية، المدعي/هويته، المدعى عليه/هويته، مبلغ المطالبة، الحالة؛ مع المرفقات والإفادة والجلسات | اختبارات CRUD/actions/scoping المشتركة نجحت؛ توثيق المتصفح قيد التنفيذ | IN PROGRESS |
| `medical_cases.php` | مستقل بأعمدة: رقم القضية، المدعي/هويته، المدعى عليه/هويته، الحالة؛ مع PDF والإجراءات | اختبارات CRUD/actions/scoping المشتركة نجحت؛ توثيق المتصفح قيد التنفيذ | IN PROGRESS |
| `administrative_cases.php` | مستقل بأعمدة: رقم القرار، الجهة، صادر ضد، مبلغ المطالبة، رقم القضية، الحالة | اختبارات CRUD/actions/scoping المشتركة نجحت؛ توثيق المتصفح قيد التنفيذ | IN PROGRESS |
| `executive_title.php` | مستقل بأعمدة: التاريخ، المدين، الهوية/السجل، الملف/القضية، طلب التنفيذ، الحالة | اختبارات CRUD/actions/scoping المشتركة نجحت؛ توثيق المتصفح قيد التنفيذ | IN PROGRESS |

## Verification record

The following checks are part of this migration and must remain green after future changes:

```text
php artisan migrate --force
php artisan view:cache
php artisan test --filter='AdmissionCalculatorTest|AdmissionInpatientParityTest|LegacySidebarFunctionalTest|LegacySidebarPageTest|LegacyNavigationParityTest|LegalClaimTest' --testsuite=Feature
php artisan test
```

The feature checks cover route-family registration, calculator scoping, sidebar hierarchy and visibility for audit roles/company rules, duplicate visible URLs, generic legacy CRUD metadata, financial claim actions, and legal-claim scoping. The latest full suite passed with **114 tests and 814 assertions**; Blade view cache succeeds, and no deployment was performed. Rows marked `IN PROGRESS` intentionally remain so until the required browser-role evidence is recorded; they must not be relabeled `COMPLETE` merely because a route or redirect exists.
