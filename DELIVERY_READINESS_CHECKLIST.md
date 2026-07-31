# Delivery Readiness Checklist

## Verified locally

- [x] Git branch and baseline established: `autonomous-delivery-sprint`.
- [x] `php artisan optimize:clear` passes.
- [x] `php artisan route:list` passes; 135 routes after removing unverified placeholders.
- [x] `php artisan test` passes with the deterministic test `APP_URL`: 12 tests, 22 assertions.
- [x] `npm run build` passes.
- [x] PHP syntax and `git diff --check` pass.
- [x] Complaint timeline is a dedicated page and preserves repeated legacy events.
- [x] Inquiry statuses and transitions use only verified legacy columns/statuses; company and branch scope is enforced.
- [x] User-management screens provide list, detail, create/edit, group, direct, inherited, effective, and scope views.
- [x] Server-side protections cover authentication refresh, scope, self-promotion, final Super Administrator, and direct URL access.
- [x] Training, training coordination, and appointment-request placeholders are hidden and direct URLs return 404.
- [x] No migration was created or executed; OldProject was not modified.

## Required before client acceptance

- [ ] Run the application against a sanitized clone of the current legacy MySQL schema and representative records.
- [ ] Inventory production attachment directories and verify every sampled legacy file mapping, including `/files`, `report_file`, and absence/payment-guarantee paths.
- [ ] Confirm deployment handling for the existing real `public/storage` directory without overwriting files; run `storage:link` only under an approved deployment procedure.
- [ ] Visual browser acceptance at Arabic/English desktop and mobile widths for dashboards, tables, forms, timeline, receipts, and user management.
- [ ] Generate and visually inspect Arabic inquiry PDF and all print views with long representative content using PHP `gd`/`calendar` extensions.
- [ ] Test production mail and OTP delivery, SMS provider allow-listing, HTTPS hostnames, token expiry, and callback links in the approved Saudi environment only.
- [ ] Review and explicitly do not run the existing inquiry migrations against the legacy database; they add unsupported `assigned_to`/status 6 behavior.
- [ ] Set `HM_PERMISSIONS_BYPASS=false` and `HM_OTP_DEMO_MODE=false` in production configuration without committing secrets.

## Decision

The project is **NOT DELIVERY-READY** until the outstanding legacy-schema, attachment, PDF, deployment, and Saudi-environment checks are completed. No local critical authorization test failed after the test URL fix.
