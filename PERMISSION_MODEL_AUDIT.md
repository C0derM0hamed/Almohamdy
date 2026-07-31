# Permission Model Audit

## Verified legacy model

- Administrative identities are stored in `ra_users`; no privileged identity is inferred from email, username, or numeric user ID.
- `hr_user_level = 3` is the existing Super Administrator grant used by the Laravel application.
- Direct grants are stored in `user_permission` and inherited grants in `user_groups_permission`; legacy grant value `2` is treated as enabled.
- Account scope is carried by `companies_groups_id` and `branch_id`. Group membership is carried by `groupid`.
- Separate `branch_users` and `branch_user_*_permission` tables exist, but the current Laravel authentication flow does not authenticate that identity type. They were therefore not merged into `ra_users` or altered.

## Implemented roles

- **Super Administrator:** active `ra_users` account whose configured legacy level is `3`. May manage users globally and choose company/branch scope.
- **Permission Administrator:** non-super account holding both legacy grants `users` and `user_groups_permissins`. May manage only non-super users in the exact same company and branch, and may grant only permissions it already holds.
- **Standard User:** any other authenticated account. User-management routes return HTTP 403.

## Server-side protections

- All six management routes require authenticated session plus `permission.admin` middleware.
- Each request refreshes active status, level, group, company, branch, and effective permissions from the database. Disabled accounts are logged out and stale elevated sessions lose access.
- Scoped record lookup rejects cross-company and cross-branch IDs, including direct URL access.
- Submitted branch must belong to submitted company; submitted group and permission names must exist in legacy tables.
- Permission Administrators cannot create or edit Super Administrators, cannot grant permissions they do not hold, and cannot assign a group whose inherited grants exceed their own.
- Users cannot change their own level, company, branch, group, active state, or direct grants.
- The final active Super Administrator cannot be disabled or demoted.
- Passwords are written using the legacy SHA-256 format already used by login. No schema changes or migrations were introduced.

## Delivered screens

- Scoped users list, user details, create/edit user.
- Group assignment and direct permission assignment/revocation.
- Separate inherited and effective permission previews.
- Company and branch scope controls (global only for Super Administrator).

## Residual risks and delivery conditions

- Legacy permission tables use MyISAM. Direct grants are updated with a minimal diff rather than delete-all replacement. Production backup and a controlled deployment window are still required.
- Permission names are legacy page identifiers rather than a centrally documented capability registry. The UI intentionally lists only identifiers already present in permission tables.
- `HM_PERMISSIONS_BYPASS` must be false in production. `HM_OTP_DEMO_MODE` currently defaults to true in config and must be explicitly false in production.
- Forgot-password currently returns a generic success message but does not deliver a reset workflow. This remains an authentication delivery blocker unless the client explicitly excludes password recovery.
- Live OTP email/SMS delivery and Saudi-restricted integrations require production-like acceptance testing; no live call was made locally.
