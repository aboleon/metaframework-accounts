# metaframework-accounts

Accounts and invoices package for MetaFramework-based Laravel applications.

## What this package provides

- Accounts / clients management with multi-address support and Google Places integration
- Invoices management with line items, PDF generation, email dispatch, and Excel export
- Expense tracking linked to invoices with net gain computation
- Payment methods, bank accounts, and cashflow ledger
- Currency and VAT management
- Company information management (translatable)
- Admin dashboard with multi-currency financial reporting
- Optional customer-facing login portal (front account skeleton)

## Quick Start

```bash
composer require aboleon/metaframework-accounts
php artisan migrate
```

Laravel package discovery registers `MetaFramework\Accounts\Providers\AccountsServiceProvider` automatically.

---

## Table of Contents

| Topic | Description |
|-------|-------------|
| [Installation](docs/installation.md) | Requirements, Composer install, running migrations, dependency list |
| [Configuration](docs/configuration.md) | Publishing the config file and available options |
| [Publishing](docs/publishing.md) | All publish tags: config, assets, views, translations, front account skeleton |
| [Accounts](docs/accounts.md) | Account model, addresses, business data, controller actions, account search component |
| [Invoices](docs/invoices.md) | Invoice model, line items, document types, CRUD, PDF, email, Excel export |
| [Expenses](docs/expenses.md) | Expense associations, net gain computation, editability rules, Blade components |
| [Payments](docs/payments.md) | Payment methods, channels, bank accounts, cashflow ledger |
| [Currencies & VAT](docs/currencies-vat.md) | Currency model, reporting conversion, VAT rates and defaults |
| [Company](docs/company.md) | Company legal info, translatable data, bank account details |
| [Dashboard](docs/dashboard.md) | Turnover metrics, client stats, year filter, multi-currency handling |
| [Front Account](docs/front-account.md) | Customer portal installation, published files, auth guard, routes |
| [Routes](docs/routes.md) | Full route table with methods, URIs, names, and controllers |
| [Database](docs/database.md) | Full table reference grouped by domain, column types, foreign keys |
| [Translations](docs/translations.md) | Supported locales, file structure, publishing, and usage |

---

## Notes for integration

- Table names use the `mfw_accounts_*` prefix.
- Asset URLs are expected under `public/vendor/mfw-accounts`.
- Route prefix is configurable via `config('mfw-accounts.route_prefix')`; route names are stable (`mfw-accounts.*`).
- The front account skeleton (`mfw-accounts-front` tag or `php artisan mfw-accounts:front`) is optional and must be explicitly installed.
- Backward-compatibility alias: the tag `mfw-account` (without trailing `s`) maps to the same skeleton.
- Legacy `thesaurus_*` table renames must be handled in the host application; do not modify package migrations.
