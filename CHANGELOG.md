# Changelog

All notable changes to this package are documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [SemVer](https://semver.org/).

## [2.0.2] – 2026-11-17

### Security
- **Open redirect after login:** `redirect_url` from the query string, the One Tap form and the session was used without checks, so a crafted link could send a freshly signed-in user to another site. `Support\SafeRedirect` now allows only same-site paths and URLs; it is applied in Google login (redirect, popup, One Tap) and `ContextController::set_redirect_url()`.

### Fixed
- Google sign-up failed (500) for accounts without a profile picture: an empty avatar was saved as a NULL `logo` meta.

## [2.0.1] – 2026-11-17

### Fixed
- `make_email_nullable_on_users_table` used MySQL-only `ALTER TABLE … MODIFY`; it now uses the schema builder, so fresh installs migrate on SQLite (the Laravel default) and PostgreSQL.
- `users.uid` is filled with a ULID for users created outside the package (Laravel's stock seeder/factory, tinker, other packages), including seeders that run without model events.

## [2.0.0] – 2026-11-17

### Settings engine
- Validation rules for auth settings (`auth_form_type`, `signin_flow`, `signin_identifier`) and WhatsApp OTP limits; `organisation_needed` is a boolean.
- Setup saves name/logo/description through the validated settings; the setup check reads the stored app name (no more seeded-default workaround).

### Housekeeping
- Removed commented-out code (incl. a dead `download()` method) and emoji from log messages; added Pint/Larastan, test CI, `.gitattributes`, `SECURITY.md`, `CONTRIBUTING.md`, `UPGRADE.md` and composer metadata.

### Breaking
- Requires PHP 8.3, Laravel 12/13 and spatie/laravel-permission 8; google/apiclient replaced.
- `config/user-management.php` and its `.env` keys removed; all settings come from the DB conf (`UserManagementConf`).
  See [docs/developer/configuration.md](docs/developer/configuration.md).
- No longer hard-codes `App\Models\User` or the app base controller.
- Depends on iquesters/user-interface.

### Added
- `auth_form_type` setting (`classic` default, `schema`, `both`) for the login, register, forgot/reset password and setup pages.
  Schema forms support reCAPTCHA and show social login.
- First-run setup gate: login and registration are blocked until setup is submitted; `/setup` locks afterwards.
- Schema-driven setup form.
- Unified sign-in flow and WhatsApp OTP (experimental).
- Testbench test suite.

### Changed
- Assets served statically when published.
- Logging uses foundation's `Loggable`.

### Changed
- `Support\Assets` extends foundation's `ModuleAssets` (shared asset route/URL logic).
- `UserManagementServiceProvider` (440 → ~90 lines) extends foundation's `BaseServiceProvider`, configured by
  `Package\UserManagementPackageInfo`. Asset route/URLs moved to `Support\Assets`, install steps to `Install\InstallSteps`.
  `getLogoUrl()` / `getCssUrl()` / `getJsUrl()` are kept on the provider.
- On `composer install/update` the app's `User` model gets `HasUserManagement` and fillable `uid`, `phone`, `status`
  automatically (previously a manual step; registration failed without it).
- Registration errors show a clear message (cause appended only with `APP_DEBUG`); form errors appear as toasts.

### Removed
- Unused `api_conf` setting (module APIs are declared in composer.json now; this module has no JSON API).
- Empty `routes/api.php`, the fallback Spatie publish path that never ran, a duplicate user-interface conf
  registration, and the logging-only `EmailOtpSender` singleton binding.

### Security
- Registration no longer returns the raw exception (SQL incl. password hash) or the submitted password in its JSON.

### Docs
- README install steps now cover the required `App\Models\User` changes (`uid` fillable, `HasUserManagement`).

### Fixed
- Fresh installs skipped first-run setup: `user-interface:seed` stores the default `APP_NAME` ("Iquesters"), which
  `SetupState` took as "setup submitted". The seeded default no longer counts.
- Setup cleared the wrong conf cache key, so the saved app name/logo/description stayed cached; it now calls `clearCache()` on the user-interface conf.
- Roles pages extended a layout read from a config key that never existed; they now use the DB layout.
- confirm-password and verify-email pages failed to render.
- Sanctum auto-setup ran on every boot.
- Database: `user_metas` LONGTEXT and unique keys, timestamped migrations.

### Removed
- Dead Google → Socialite config mapping (read keys that were never set).

### Security
- Admin role required on `/admin` routes; SRI on CDN assets.
- Stored web API token is encrypted.
- Path traversal in `/vendor/usermanagement/{path}`.
