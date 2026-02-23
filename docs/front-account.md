# Front Account (Customer Portal)

## Overview

The front account skeleton provides a customer-facing login and dashboard, separate from the admin panel. It is an optional feature that must be explicitly installed.

## Installation

### Option 1 — Files only

```bash
php artisan vendor:publish \
  --provider="MetaFramework\\Accounts\\Providers\\AccountsServiceProvider" \
  --tag="mfw-accounts-front"
```

Copies the skeleton files into the application. You must wire the auth guard and routes manually.

### Option 2 — Files + automatic wiring (recommended)

```bash
php artisan mfw-accounts:front
```

This command:

1. Publishes all skeleton files into the application.
2. Adds `require __DIR__ . '/account.php';` to `routes/web.php` (if not already present).
3. Adds the following entries to `config/auth.php`:

**Guard:**
```php
'account' => [
    'driver'   => 'session',
    'provider' => 'accounts',
],
```

**Provider:**
```php
'accounts' => [
    'driver' => 'eloquent',
    'model'  => \MetaFramework\Accounts\Models\Account::class,
],
```

**Password broker:**
```php
'accounts' => [
    'provider'  => 'accounts',
    'table'     => 'password_reset_tokens',
    'expire'    => 60,
    'throttle'  => 60,
],
```

## Published Files

After running either option above, the following files are placed into your application:

| File | Purpose |
|------|---------|
| `app/Http/Controllers/Front/Account/AccountAuthController.php` | Login / logout controller |
| `app/Http/Controllers/Front/Account/AccountPortalController.php` | Authenticated portal controller |
| `app/Http/Requests/Front/Account/AccountLoginRequest.php` | Login form request |
| `app/Http/Middleware/Front/AccountLocale.php` | Sets locale for authenticated sessions |
| `app/Http/Middleware/Front/AccountLoginLocale.php` | Sets locale for the login page |
| `routes/account.php` | Customer portal route file |
| `resources/views/front/account/login.blade.php` | Login view |
| `resources/views/front/account/dashboard.blade.php` | Customer dashboard view |

## Routes (routes/account.php)

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| GET | `/account/login` | `account.login` | Show login form |
| POST | `/account/login` | `account.login.store` | Process login |
| GET | `/account/` | `account.dashboard` | Customer dashboard (auth required) |
| POST | `/account/logout` | `account.logout` | Logout |

## Controller Reference

### AccountAuthController

| Method | Description |
|--------|-------------|
| `create(): View` | Renders the login form |
| `store(AccountLoginRequest): RedirectResponse` | Validates credentials and logs in the customer |
| `destroy(): RedirectResponse` | Logs out the customer and redirects to login |

The controller calls `setAccountLocale()` internally to apply the customer's preferred locale after login.

### AccountPortalController

| Method | Description |
|--------|-------------|
| `dashboard(): View` | Renders the customer dashboard (requires `account` guard) |

## Auth Guard

The customer portal uses its own `account` guard (not `web`). This keeps customer sessions separate from admin sessions. The `Account` model (backed by the `users` table) is used as the authenticatable.

## Customisation

Because the skeleton files are published into the application, all controllers, middleware, views, and routes are fully customisable. The package does not load these files itself — they belong to the application after publishing.
