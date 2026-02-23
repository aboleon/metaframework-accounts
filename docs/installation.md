# Installation

## Requirements

- PHP 8.3 or higher
- Laravel 12.x
- The [MetaFramework](https://github.com/aboleon/metaframework) core package

## Composer

```bash
composer require aboleon/metaframework-accounts
```

Laravel's package auto-discovery registers the service provider automatically:

```
MetaFramework\Accounts\Providers\AccountsServiceProvider
```

## Run Migrations

The package ships its own migrations. After installation, run:

```bash
php artisan migrate
```

This creates all `mfw_accounts_*` tables in the configured database. See [Database Reference](database.md) for the full table list.

## Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| `aboleon/metaframework` | `dev-mfw-2026` or `^1.0` | Core MetaFramework |
| `aboleon/metaframework-google-places` | `^1.2` | Address autocomplete (Google Places API) |
| `aboleon/metaframework-mailer` | `0.*` | Invoice email dispatch |
| `aboleon/metaframework-mediaclass` | `0.*` | Media/file handling |
| `barryvdh/laravel-dompdf` | `^3.1` | PDF generation |
| `maatwebsite/excel` | `^3.1` | Excel export |

All dependencies are declared in the package `composer.json` and installed automatically via Composer.
