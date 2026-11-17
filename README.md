# Laravel User Management Package – Iquesters <img src="https://avatars.githubusercontent.com/u/7593318?s=200&v=4" alt="Iquesters Logo" width="40" style="vertical-align: middle;"/>

A robust and reusable **User Management package** for Laravel applications, developed and maintained by **[Iquesters](https://github.com/iquesters)**.

This package provides authentication, role management, Google OAuth integration, and reCAPTCHA support. It also comes with configurable layouts, logos, and default behaviors for new users.

---

## ⚙️ Purpose

The **User Management Package** helps Laravel applications:

* Handle authentication and user roles
* Integrate Google login and One Tap authentication
* Customize login/register views with your own layout and branding
* Protect forms with reCAPTCHA
* Set default routes and roles for new users

It acts as a **foundation for secure and modular user handling**, which can be extended or used by other Iquesters packages.

---

## 🚀 Installation

1. Install the package using Composer:

```bash
composer require iquesters/user-management
```

2. Run migrations to create required database tables:

```bash
php artisan migrate
```

3. Seed:

```bash
php artisan user-management:seed
```

4. `App\Models\User` is updated automatically on `composer install/update` (`package:discover`): the
   `HasUserManagement` trait is added and `uid`, `phone`, `status` are made fillable. If your model has an
   unusual shape the installer prints what it couldn't do; the model then needs:

```php
use Iquesters\UserManagement\Traits\HasUserManagement; // roles (spatie) + Sanctum tokens

#[Fillable(['uid', 'name', 'email', 'password', 'phone', 'status'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUserManagement;
    // with iquesters/organisation also: use \Iquesters\Organisation\Traits\HasOrganisations;
}
```

   Without this, registration fails with `Field 'uid' doesn't have a default value`.

5. Don't create users in `DatabaseSeeder`: any existing user makes first-run setup count as done.
   The first account registered after `/setup` becomes the super-admin.

---

## 🔧 Configuration

Settings are stored in the database (not a config file or `.env`). `php artisan user-management:seed` writes the defaults.

See [docs/developer/configuration.md](docs/developer/configuration.md) for every key, including `auth_form_type`
(classic / schema / both auth forms), and [docs/developer/social-login-configuration.md](docs/developer/social-login-configuration.md).

---
