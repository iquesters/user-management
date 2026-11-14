# Configuration (Developer)

## Source

All runtime settings come from the database, read through:

```php
ConfProvider::from(Module::USER_MGMT)->signin_flow;
```

- Defaults are defined in `src/Config/UserManagementConf.php` (`prepareDefault()`).
- `php artisan user-management:seed` writes those defaults into `master_data` / `master_data_metas`.
  It overwrites existing values with the defaults, so on a live install add a new key as a single row instead.
- A key missing from the DB falls back to its default (a warning is logged).

There is no `config/user-management.php` and no `.env` keys; the DB is the only source.

## Keys

| Key | DB meta key | Default | Purpose |
|---|---|---|---|
| `auth_layout` | `AUTH_LAYOUT` | `usermanagement::layouts.package` | Layout for auth pages (user-interface's layout wins when installed) |
| `app_layout` | `APP_LAYOUT` | `usermanagement::layouts.app` | Layout for app pages |
| `logo` | `LOGO` | `img/logo.png` | Logo on auth pages (URL, path or package asset) |
| `default_user_role` | `DEFAULT_USER_ROLE` | `user` | Role given to new users |
| `default_auth_route` | `DEFAULT_AUTH_ROUTE` | `dashboard` | Redirect after login |
| `organisation_needed` | `ORGANISATION_NEEDED` | `false` | Require an organisation for users |
| `signin_identifier` | `SIGNIN_IDENTIFIER` | `email` | Identifier used to sign in |
| `signin_flow` | `SIGNIN_FLOW` | `classic` | `classic` or `unified` sign-in |
| `auth_form_type` | `AUTH_FORM_TYPE` | `classic` | Which form auth pages render (see below) |
| `recaptcha.*` | `$RECAPTCHA~…` | disabled | reCAPTCHA v3: `enabled`, `site_key`, `secret_key` |
| `social_login.*` | `$SOCIAL_LOGIN~…` | disabled | See [social-login-configuration.md](social-login-configuration.md) |
| `whatsapp_login.*` | `$WHATSAPP_LOGIN~…` | disabled | WhatsApp OTP login |
| `registration_fields.*` | `$REGISTRATION_FIELDS~…` | none | Extra fields collected at registration |

## Auth form type

`auth_form_type` controls the login, register, forgot password, reset password and setup pages:

| Value | Renders |
|---|---|
| `classic` (default) | The Blade form only |
| `schema` | The form-schema form only (needs iquesters/user-interface and seeded schemas) |
| `both` | Both side by side (for comparing while building schemas) |

Unknown values, or `schema`/`both` without user-interface installed, fall back to `classic`.
Resolved by `Support\AuthFormSchema::formType()` / `showsClassic()` / `showsSchema()`.

Both forms support:

- **reCAPTCHA** – the classic form uses `recaptcha.js`; the schema form gets a `recaptchaAction` and
  `form.js` fetches a v3 token before submitting. Actions match the server rules:
  `login`, `register`, `password_reset_link`, `password_reset`.
- **Social login** – rendered under whichever form is shown (once, in the classic column, when `both`).

Pages showing a single form use a narrow auth card (`@section('auth-card-class', 'col-md-7 col-lg-4')`);
`both` keeps the wide card.
