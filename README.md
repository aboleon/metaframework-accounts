# metaframework-accounts

Accounts and invoices package for MetaFramework-based Laravel applications.

## What this package provides

- Accounts / clients management
- Invoices management
- Cashflow reference data and related accounting tables
- Package routes, views, translations, migrations, and public assets

## Installation

Install the package in your Laravel app (or use your local path repository setup).

```bash
composer require aboleon/metaframework-accounts
```

Laravel package discovery registers:

- `MetaFramework\Accounts\Providers\AccountsServiceProvider`

## Publishable resources

### Config

Publish the package config file:

```bash
php artisan vendor:publish --tag=mfw-accounts-config
```

Published file:

- `config/mfw-accounts.php`

Current config options:

- `route_prefix` (default: `mfw-accounts`)

Example:

```php
<?php

return [
    'route_prefix' => 'mfw-accounts',
];
```

### Assets

Publish package public assets:

```bash
php artisan vendor:publish --tag=mfw-accounts-assets
```

Assets are published to:

- `public/vendor/mfw-accounts`

### Views (optional override)

```bash
php artisan vendor:publish --tag=mfw-accounts-views
```

Published to:

- `resources/views/modules/mfw-accounts`

### Translations (optional override)

```bash
php artisan vendor:publish --tag=mfw-accounts-translations
```

Published to:

- `lang/modules/mfw-accounts`

## Routes

The package loads web routes from:

- `Routes/web.php`

There is no package API routes file.

The URL prefix is configurable through:

- `config('mfw-accounts.route_prefix')`

Default URL prefix:

- `/mfw-accounts`

Route names remain:

- `mfw-accounts.*`

Only the URL path prefix changes when `route_prefix` changes.

## Migrations and table names

The package ships migrations that create static tables named with the `mfw_accounts_` prefix, for example:

- `mfw_accounts_invoices`
- `mfw_accounts_accounts`
- `mfw_accounts_currencies`

Run package migrations only when you explicitly want to create these tables in the host application:

```bash
php artisan migrate
```

If your host application currently uses legacy `thesaurus_*` tables, handle the rename/data migration in the host project with a dedicated migration. Do not change package migrations to target host-specific legacy table names.

## Notes for integration

- This package keeps its own table naming (`mfw_accounts_*`).
- Asset URLs are expected under `public/vendor/mfw-accounts`.
- Route prefix is configurable; route names are stable (`mfw-accounts.*`).

