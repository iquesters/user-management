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

---

## 🔧 Configuration

Settings are stored in the database (not a config file or `.env`). `php artisan user-management:seed` writes the defaults.

See [docs/developer/configuration.md](docs/developer/configuration.md) for every key, including `auth_form_type`
(classic / schema / both auth forms), and [docs/developer/social-login-configuration.md](docs/developer/social-login-configuration.md).

---
