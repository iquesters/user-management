# Social Login Conf Guide

This document explains how social login configuration is read and used at runtime.

## Config Source

Social login config is loaded from:

- `ConfProvider::from(Module::USER_MGMT)->social_login`

So in your setup, values are expected from your DB-backed conf storage (not necessarily from `config/user-management.php`).

## Structure

`social_login` contains:

- `enabled` (bool): global switch for all social providers.
- `o_auth_providers` (map): provider-specific conf, keyed by provider identifier (for example `google`).

Google provider supports:

- `enabled` (bool): enables Google provider routes and buttons.
- `popup_login` (bool): if true, "Continue with Google" opens OAuth in a popup.
- `one_tap_enabled` (bool): enables Google One Tap initialization on auth pages.
- `auto_signin` (bool): enables Google One Tap `auto_select` behavior.
- `client_id` (string): Google OAuth client ID.
- `client_secret` (string): Google OAuth client secret.
- `redirect_url` (string): callback URL, typically `http://localhost:8000/auth/google/callback`.
- `scopes` (array): OAuth scopes (default `email`, `profile`).

## Where It Is Used

- Auth UI include:
  - `resources/views/auth/login.blade.php`
  - `resources/views/auth/register.blade.php`
  - both include `resources/views/components/social-login-section.blade.php`.

- Social section:
  - checks `social_login.enabled`
  - renders enabled providers from `o_auth_providers`.

- Google button component:
  - `resources/views/components/signin-with-google-button.blade.php`
  - reads `popup_login`, `one_tap_enabled`, `auto_signin`, `client_id`.

- Backend handlers:
  - `src/Http/Controllers/Auth/GoogleController.php`
  - `google_redirect`: starts OAuth redirect (popup-aware).
  - `google_callback`: completes OAuth login (popup/non-popup aware).
  - `google_onetap_callback`: verifies One Tap token and logs in user.

## Behavior Matrix

- `social_login.enabled = false`
  - no social buttons are rendered.

- `social_login.enabled = true`, `google.enabled = false`
  - Google button/flows remain disabled.

- `google.enabled = true`, `popup_login = false`
  - standard full-page redirect flow.

- `google.enabled = true`, `popup_login = true`
  - OAuth flow opens in popup and sends result to opener window.

- `google.enabled = true`, `one_tap_enabled = true`, `auto_signin = false`
  - One Tap prompt appears; user confirms account.

- `google.enabled = true`, `one_tap_enabled = true`, `auto_signin = true`
  - One Tap prompt can auto-select previously approved Google account.

## Required Google Console Setup

For popup/redirect flow:

- Authorized redirect URI: `http://localhost:8000/auth/google/callback`

For One Tap:

- Authorized JavaScript origin: `http://localhost:8000`

If origin is missing, Google returns `invalid_client` / `no registered origin`.

## Example DB Keys

If your conf UI stores flattened keys, these are the expected ones:

- `$SOCIAL_LOGIN~ENABLED`
- `$SOCIAL_LOGIN~@O_AUTH_PROVIDERS~$GOOGLE~ENABLED`
- `$SOCIAL_LOGIN~@O_AUTH_PROVIDERS~$GOOGLE~POPUP_LOGIN`
- `$SOCIAL_LOGIN~@O_AUTH_PROVIDERS~$GOOGLE~ONE_TAP_ENABLED`
- `$SOCIAL_LOGIN~@O_AUTH_PROVIDERS~$GOOGLE~AUTO_SIGNIN`
- `$SOCIAL_LOGIN~@O_AUTH_PROVIDERS~$GOOGLE~CLIENT_ID`
- `$SOCIAL_LOGIN~@O_AUTH_PROVIDERS~$GOOGLE~CLIENT_SECRET`
- `$SOCIAL_LOGIN~@O_AUTH_PROVIDERS~$GOOGLE~REDIRECT_URL`

## Minimal Recommended Setup

- `social_login.enabled = true`
- `google.enabled = true`
- `google.client_id = <your-web-client-id>`
- `google.client_secret = <your-client-secret>`
- `google.redirect_url = http://localhost:8000/auth/google/callback`

Optional:

- set `popup_login = true` for popup UX.
- set `one_tap_enabled = true` and `auto_signin = true` for automatic One Tap sign-in.
