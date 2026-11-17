# Upgrading iquesters/user-management

## 1.x → 2.0

Upgrade together with foundation, user-interface and organisation; follow foundation's
`UPGRADE.md` first (requirements, migrations, admin roles).

### Breaking changes
- **Settings moved to the database.** `config/user-management.php` and its `.env` keys
  (`USER_MANAGEMENT_*`, `USERMANAGEMENT_*`, `ORGANISATION_NEEDED`) are no longer read. Set the values
  in the DB-backed conf (config screen, or `master_data_metas`); see `docs/developer/configuration.md`.
  Then remove your published `config/user-management.php`.
- **User model.** `App\Models\User` needs the `HasUserManagement` trait and fillable `uid`, `phone`,
  `status`. This is applied automatically on `composer update` (`package:discover`); check the
  output, or add it by hand (see README).
- **First-run setup gate.** Login and registration redirect to `/setup` until setup has been
  submitted. Existing installs that already have users count as set up.
- `google/apiclient` is no longer used (Google ID tokens are verified directly).
- `routes/api.php` is removed (it was empty; the auth endpoints live in `routes/auth.php`).
- Registration JSON no longer echoes the submitted data (it contained the password) or raw
  exception messages.

### New settings (defaults keep 1.x behaviour)
- `auth_form_type`: `classic` (default), `schema` or `both` for the auth pages.
