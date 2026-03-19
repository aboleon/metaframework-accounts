# Configuration

## Publish the Config File

```bash
php artisan vendor:publish --tag=mfw-accounts-config
```

Published to: `config/mfw-accounts.php`

## Available Options

```php
<?php

return [
    'route_prefix' => 'mfw-accounts',
    'models' => [
        'account' => \MetaFramework\Accounts\Models\Account::class,
    ],
];
```

### `route_prefix`

**Type:** `string`
**Default:** `mfw-accounts`

Changes the URL path prefix for all package routes.

**Example:** Setting `route_prefix` to `admin/crm` changes the invoice index URL from `/mfw-accounts/invoices` to `/admin/crm/invoices`.

> **Note:** Only the URL path changes. Route names always remain `mfw-accounts.*` regardless of the configured prefix.

### `models.account`

**Type:** `class-string`
**Default:** `\MetaFramework\Accounts\Models\Account::class`

Defines which Eloquent model class the package should use for accounts / clients.

Use this when the host application needs to extend the package account model with custom relations or query behavior.

**Example:**

```php
'models' => [
    'account' => App\Models\Account::class,
],
```

The configured model must extend `MetaFramework\Accounts\Models\Account`.
