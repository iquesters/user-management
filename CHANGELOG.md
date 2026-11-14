# Changelog

All notable changes to this package are documented here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versions follow [SemVer](https://semver.org/).

## [Unreleased] – 2.0.0

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
