# Legacy Files and Output Report

Audit date: 2026-07-31

## Scope and safety

This is a code and filesystem mapping audit. It did not copy, rename, delete, or mutate any legacy file, and it did not query or change the production database. The legacy database stores file references in several different formats, so a bulk move is not safe without a production inventory and record-by-record verification.

## Verified legacy locations

| Legacy location | Verified use | NewProject behavior | Delivery action |
|---|---|---|---|
| `OldProject/files/` | Shared legacy uploads, including clinician images and files used by complaints, inquiries, correspondence, visits, and other modules | Legacy paths are generally resolved as `/files/<name>`; clinician photos use `HM_CLINICIANS_PHOTOS_PATH` | Mount or copy the production directory to the web-visible path configured for the deployment. Do not rely on the small repository sample. |
| `OldProject/absence_notification_service_files/` | Sick-leave evidence referenced by absence notification records | The current Laravel absence screens read legacy record fields, but a complete production file inventory was not available | Verify every non-empty legacy filename against the production directory before acceptance. |
| `OldProject/payment_guarantee_files/` | Service-package images and medical agreement/payment-guarantee files | Service package attachments resolve through `HM_SERVICE_ATTACHMENTS_PATH`, default `/files` | Confirm whether production package images live in `/files` or the separate legacy directory, then set the environment path or deployment mapping accordingly. |
| `OldProject/report_file/` references | Reports and some data-request legacy attachments | Data-request resolver explicitly falls back to `/report_file/<basename>` | Provide the directory at that public path and validate sampled database references. |
| Other legacy directories (`corpse_files`, `claiming_against_others_files`, `emdhaFiles`, etc.) | Modules outside, or not proven inside, the approved Laravel scope | No general Laravel mapping | Do not migrate speculatively. Inventory only if those modules enter scope. |

The checked-out legacy upload folders contain almost no representative business files. Their size and file count cannot be used as evidence that production records have no attachments.

## New uploads

NewProject writes supported new uploads to Laravel's `public` disk under module-specific paths:

- `government-circulars/YYYY/MM`
- `inspection-visits/...`
- `data-requests/...`
- `correspondence/...`
- `outgoing-correspondence/...`

The application also contains legacy-aware URL fallbacks for these modules. Deployment must create the normal `public/storage` link with `php artisan storage:link`. In this checkout, `public/storage` is a real directory rather than the standard symlink; this must be corrected or deliberately mapped by deployment infrastructure before client delivery. Existing files must be preserved while doing so.

## Database compatibility

The file-bearing Laravel models retain the legacy tables and columns, including:

- `service_packages_attachments.file_name`
- `clinicians.uploaded_file`
- `government_circulars_attachments.circulars_file`
- `government_inspection_visits_attachments.file_name`
- `government_inspection_visits_abuses_and_notes.uploaded_file`
- `government_inspection_visits_returned.uploaded_file`
- `corporate_communications_attachments.file`
- `corporate_communications_replies_attachments.file` / `file_name`
- `corporate_communications_outgoing_letters_attachments.file` / `file_name`

No schema redesign or migration was introduced by this workstream. Compatibility still requires testing against a sanitized clone of the current production schema because repository fixtures do not establish the existence, collation, nullability, or contents of every legacy table.

## PDF, printing, and export

- Inquiry PDF is implemented with mPDF, UTF-8, RTL direction, and an Arabic-capable bundled font.
- Browser print views exist for circular receipts, inspection visits, data requests, correspondence, and outgoing correspondence, with shared print CSS.
- Absence notification export supports UTF-8 CSV and SpreadsheetML `.xls` streaming.
- PDF/print routes still require authenticated, scoped browser smoke tests with real Arabic data and representative long content.
- A generated PDF must be visually inspected after deployment; a successful HTTP response alone does not prove Arabic shaping, pagination, or font availability.

## Hope UI, Arabic RTL, and responsive status

The main, auth, and public-reply layouts set `lang` and `dir` from the active locale. Hope UI 2.0 assets and the RTL stylesheet are loaded, local Arabic fonts are present, and responsive/print bridge styles exist. This is implementation evidence, not final visual acceptance. Required browser checks remain:

1. Arabic and English at desktop and mobile widths.
2. Sidebar open/collapsed behavior and no content overlap.
3. Long Arabic labels in tables, filters, modal dialogs, and buttons.
4. File upload validation/error states.
5. Receipt printing and inquiry PDF with multi-page Arabic content.

## Integrations and API review

No general public JSON API was found. Integrations are email, SMS, and tokenized public reply/form links. The SMS gateway supports a local `log` provider and configured remote providers. Corporate notifications are disabled by configuration unless explicitly enabled. Saudi/provider-restricted calls were not made and must remain disabled in local QA.

Before production enablement, validate mail delivery, sender identities, SMS allow-listing, provider credentials, callback/link hostnames, HTTPS, and token expiry/business expectations in the approved Saudi environment. Never test these by placing real credentials in source control.

## Placeholder decision

The Laravel placeholder routes and navigation entries for training management, training coordination, and appointment requests were removed. Although legacy files with training or appointment-related names exist, the approved audit did not establish a bounded schema/workflow that can be migrated safely. Direct placeholder URLs now return 404 instead of presenting a non-functional workflow. Implement these modules only after their exact legacy pages, tables, statuses, permissions, branch rules, and acceptance criteria are approved.

## Delivery blockers from this audit

- Production legacy-file inventory and path mapping are not verified.
- The `public/storage` deployment mapping needs correction/confirmation without overwriting existing files.
- Attachment create/read tests against a sanitized production-schema clone remain outstanding.
- Arabic PDF and all print layouts need visual browser/printer acceptance with representative data.
- Saudi SMS and production mail delivery remain environment-only tests.

Until these items are verified, files and printed outputs must not be marked delivery-ready.
