# Translations

## Supported Locales

The package ships translations for three locales:

| Code | Language |
|------|----------|
| `en` | English |
| `fr` | French |
| `bg` | Bulgarian |

## Translation Files

Each locale contains the following files under `Resources/lang/{locale}/`:

| File | Purpose |
|------|---------|
| `ui.php` | All UI strings: labels, buttons, headings, status messages |
| `enum.php` | Enumeration labels (e.g. document numbering types) |
| `mailer/invoice.php` | Email template strings for invoice dispatch |

### ui.php

Contains ~294 keys covering:

- Account / client management (add, edit, search, list)
- Invoice / quote / payment document types
- Dashboard and financial reporting (turnover, evolution, charts)
- Expenses and profit summary
- Address management and geographic data
- Company information and bank account labels
- Client type labels (individual vs. company)
- Payment status (paid / unpaid)
- Document operations (send, duplicate, attach)

### enum.php

Keys for enumeration values:

| Key | Values |
|-----|--------|
| `doc_type_incrementation` | `generic`, `own` |

### mailer/invoice.php

Strings used in the invoice email:

- Subject line
- Greeting and body text
- Invoice details (number, date, amount)
- PDF download button label
- Company description, licence text, contact info
- Send success / failure messages

## Publishing and Overriding

```bash
php artisan vendor:publish --tag=mfw-accounts-translations
```

Published to: `lang/modules/mfw-accounts/{locale}/`

Once published, Laravel loads your application's copy instead of the package copy. Edit the files in `lang/modules/mfw-accounts/` to customise any string.

## Using Translation Keys

Use the standard Laravel translation helper with the package namespace:

```php
__('mfw-accounts::ui.some_key')
__('mfw-accounts::enum.doc_type_incrementation.generic')
__('mfw-accounts::mailer/invoice.subject')
```

In Blade templates:

```blade
{{ __('mfw-accounts::ui.add_client') }}
@lang('mfw-accounts::ui.invoices')
```

## Adding a New Locale

1. Publish the translation files.
2. Create a new directory: `lang/modules/mfw-accounts/{locale}/`.
3. Copy and translate `ui.php`, `enum.php`, and `mailer/invoice.php` into the new directory.
4. Ensure the new locale is supported by the host application's locale configuration.
