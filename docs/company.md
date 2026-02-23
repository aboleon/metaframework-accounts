# Company

## Overview

The company module stores your own organisation's information — legal identifiers, contact details, and bank account data — used on invoice PDFs and in email footers.

## Model: Company

**Table:** `mfw_accounts_company`

Single-row table (no auto-increment; `id` is fixed at `1`).

| Field | Type | Description |
|-------|------|-------------|
| `EIN` | tinyText | Employer Identification Number |
| `VAT` | tinyText | VAT registration number |
| `bilan_start` | string(5) | Fiscal year start (e.g. `01-01`) |
| `bilan_end` | string(5) | Fiscal year end (e.g. `12-31`) |
| `website` | mediumText | Company website URL |
| `email` | mediumText | Company email address |
| `phone` | mediumText | Company phone number |

### Relations

- `data()` — HasMany → `CompanyData` (translatable name, owner, address, licence)

### Static Methods

```php
Company::info(?string $lang): Collection
```

Returns the company data for the given locale, combining the scalar fields and the current-locale translation.

```php
Company::scalarSettingsFields(): array
Company::defaultSettingsAttributes(): array
```

Helper methods used internally by the edit form.

## Model: CompanyData (Translatable)

**Table:** `mfw_accounts_company_data`

Stores locale-specific company information.

| Field | Type | Description |
|-------|------|-------------|
| `company_id` | unsignedInteger | FK → mfw_accounts_company.id |
| `lg` | string(2) | Locale code (`en`, `fr`, `bg`) |
| `name` | text | Company name in this locale |
| `owner` | text | Company owner name in this locale |
| `adresse` | text | Company address in this locale |
| `siege` | text | Registered office address |
| `licence` | text | Licence or legal notice text |

One row exists per supported locale.

## Model: BankAccountsData (Translatable)

**Table:** `mfw_accounts_bank_accounts_data`

Translatable bank account details displayed on invoices.

| Field | Type | Description |
|-------|------|-------------|
| `account_id` | unsignedInteger | FK → mfw_accounts_bank_accounts.id |
| `lg` | string(2) | Locale code |
| `name` | text | Account display name |
| `bank` | text | Bank name |
| `address` | text | Bank address |

## Edit Route

| Method | URI | Route Name |
|--------|-----|------------|
| GET | `/company/edit` | `mfw-accounts.company.edit` |
| PUT | `/company` | `mfw-accounts.company.update` |

The company edit form allows updating all scalar fields and the translatable data for each supported locale simultaneously.
