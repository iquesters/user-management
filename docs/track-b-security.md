# Track B — Controlled password writes and credential revocation

The alternate dashboard organisation-create route and controller require platform authority. Normal web password changes re-read the user under a row lock. HasUserManagement serializes every existing Eloquent password update with Python session issuance/rotation, changes the remember credential, revokes Sanctum and refresh families, removes database sessions when present and records durable legacy audit evidence when the approved additive table exists. Reset/admin save paths inherit the same unit of work. Failed revocation rolls the password update back. New Python password management is not a remote PHP service.

The existing first-password setup flow for Google/OTP accounts is retained; known-password changes still require current password and confirmation. Canonical Python password change always requires current password. Built-in admin role rename protection is retained; global admin does not acquire platform authority.

## Validation

86 regression tests pass on disposable MariaDB (299 assertions). Includes real Laravel bcrypt writer/verification and password rollback/revocation. A real PHP writer also races Python refresh successfully in Util’s MariaDB cross-runtime suite.

## Review and release gates

This branch is for review only. [Architecture and API contract](https://github.com/iquesters/chatbot-util/blob/feature/knorai-rbac-account-management/docs/security/TRACK_B_SECURITY_FOUNDATION.md), [complete source inventory](https://github.com/iquesters/chatbot-util/blob/feature/knorai-rbac-account-management/docs/security/LARAVEL_MIGRATION_INVENTORY.md), and [test evidence](https://github.com/iquesters/chatbot-util/blob/feature/knorai-rbac-account-management/docs/security/TEST_EVIDENCE.md).

Track C (chatbot-util #137) owns additive schema/revision IDs. No new Alembic head, historical SQL edit, live migration, production role grant, merge or deployment is included. Coordinate schema verification, tenant assignments, all Util caller service credentials and the host dependency lock before release. Do not merge schema-dependent changes before the reviewed C contract is agreed. PHP remains transitional; final backend parity moves to Python.
