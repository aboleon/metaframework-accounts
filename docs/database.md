# Database Reference

## Naming Convention

All tables use the `mfw_accounts_` prefix. Migrations are run via `php artisan migrate` and are loaded automatically by the service provider.

---

## Reference Tables

These tables store configuration data that is typically set up once and referenced by other tables.

### mfw_accounts_currencies

Supported currencies.

| Column | Type | Notes |
|--------|------|-------|
| `id` | unsignedInteger | Primary key |
| `name` | text | Currency name |
| `code` | string(3) | ISO 4217 code |
| `sign` | tinyText | Display symbol |
| `default` | unsignedTinyInteger | Default flag |
| `conversion_rate` | decimal(10,5) | Rate relative to EUR (nullable for EUR) |

### mfw_accounts_vat

VAT rates. Soft-deleted to preserve history.

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigInteger | Auto-increment |
| `rate` | unsignedInteger | Rate × 100 (e.g. `2000` = 20%) |
| `default` | boolean | Unique default flag |
| `deleted_at` | timestamp | Soft delete |

### mfw_accounts_cashflow_doc_types

Document type definitions (invoice, quote, credit note, etc.).

| Column | Type | Notes |
|--------|------|-------|
| `id` | increments | |
| `slug` | string | Machine-readable key |
| `admin_name` | longText | Admin display name (translatable) |
| `name` | longText | Customer-facing name (translatable) |
| `default` | unsignedTinyInteger | |
| `accountable` | unsignedTinyInteger | Appears in accounting (1 = yes) |
| `inventory` | tinyInteger | |
| `numerotation` | enum | `generic` or `own` |

### mfw_accounts_sell_channels

Sales channels.

| Column | Type | Notes |
|--------|------|-------|
| `id` | increments | |
| `name` | mediumText | |
| `default` | char(1) | Nullable |

### mfw_accounts_pay_means

Payment method types.

| Column | Type | Notes |
|--------|------|-------|
| `id` | increments | |
| `type` | string(50) | Machine-readable type |
| `name` | longText | Display name (translatable) |

### mfw_accounts_bank_accounts

Bank account IBAN / BIC data.

| Column | Type | Notes |
|--------|------|-------|
| `id` | unsignedInteger | |
| `BIC` | text | |
| `IBAN` | string(22) | |

### mfw_accounts_company

Your company's legal information (single row).

| Column | Type | Notes |
|--------|------|-------|
| `id` | unsignedInteger | Fixed; no auto-increment |
| `EIN` | tinyText | |
| `VAT` | tinyText | |
| `bilan_start` | string(5) | Fiscal year start (`MM-DD`) |
| `bilan_end` | string(5) | Fiscal year end (`MM-DD`) |
| `website` | mediumText | |
| `email` | mediumText | |
| `phone` | mediumText | |

### mfw_accounts_providers

External providers / suppliers.

| Column | Type | Notes |
|--------|------|-------|
| `id` | increments | |
| `name` | text | |
| `adresse` | text | |
| `location` | unsignedInteger | |

---

## Account Tables

### mfw_accounts_accounts

Client records (mapped to the `users` table by the `Account` model).

| Column | Type | Notes |
|--------|------|-------|
| `id` | increments | |
| `prenom` | tinyText | First name |
| `nom` | tinyText | Last name |
| `prenom_alt` | text | Alternate first name |
| `nom_alt` | text | Alternate last name |
| `email` | string(128) | |
| `phone` | string(128) | |
| `societe` | tinyText | Company name |
| `societe_alt` | tinyText | Alternate company name |
| `tva` | tinyText | VAT number |
| `siret` | tinyText | SIRET / registration number |
| `civ` | enum | `M`, `Mme`, `Mlle` |
| `adresse` | tinyText | Legacy address field |
| `localisation` | unsignedInteger | |
| `country` | unsignedInteger | |

### mfw_accounts_account_address

Client addresses (one client may have multiple addresses).

| Column | Type | Notes |
|--------|------|-------|
| `id` | id | |
| `user_id` | unsignedBigInteger | FK → users.id (cascadeOnDelete) |
| `billing` | boolean | Default billing address flag |
| `street_number` | string | |
| `route` | longText | Street name |
| `locality` | longText | City |
| `postal_code` | string | |
| `country_code` | string | ISO country code |
| `place_id` | string | Google Places ID |
| `lat` | decimal(16,13) | Latitude |
| `lon` | decimal(16,13) | Longitude |
| `company` | string | Company at this address |
| `complementary` | text | Additional address line |
| `administrative_area_level_1` | longText | Region / state |
| `administrative_area_level_2` | longText | Department / county |
| `text_address` | longText | Full plain-text address |

### mfw_accounts_account_business

Business details for company-type clients.

| Column | Type | Notes |
|--------|------|-------|
| `id` | id | |
| `user_id` | unsignedBigInteger | FK → users.id (cascadeOnDelete) |
| `name` | longText | Business name (translatable) |
| `vat_number` | string | VAT registration number |
| `reg_number` | string | Company registration number |

---

## Invoice Tables

### mfw_accounts_invoices

Core invoice / document records.

| Column | Type | Notes |
|--------|------|-------|
| `id` | increments | |
| `document_id` | unsignedInteger | Sequence number within doc type |
| `account_id` | unsignedBigInteger | FK → users.id (cascadeOnDelete) |
| `doc_type` | unsignedInteger | FK → mfw_accounts_cashflow_doc_types.id |
| `hash` | string(40) | Unique hash for PDF URL |
| `title` | text | |
| `subtitle` | text | |
| `content` | text | |
| `notes` | text | |
| `amount` | integer | Amount × 100 |
| `vat` | integer | VAT × 100 |
| `expenses` | unsignedInteger | Computed total expenses |
| `net_gain` | integer | Computed net gain |
| `net_gain_percent` | integer | Computed net gain % |
| `expense_protocol_ref` | string | |
| `vat_id` | unsignedInteger | FK → mfw_accounts_vat.id |
| `currency` | unsignedInteger | FK → mfw_accounts_currencies.id |
| `sell_channel` | unsignedTinyInteger | FK → mfw_accounts_sell_channels.id |
| `paid` | string(1) | Nullable = unpaid |
| `pay_mean` | unsignedInteger | FK → mfw_accounts_pay_means.id |
| `date_paid` | date | |
| `invoice_date` | date | |
| `transaction_date` | date | |
| `date_before` | date | Due date |
| `sent_at` | timestamp | Email sent timestamp |
| `duplicata` | string(1) | Duplicate flag |
| `bank_account` | unsignedInteger | FK → mfw_accounts_bank_accounts.id |
| `attached_to` | unsignedInteger | FK → mfw_accounts_invoices.id |
| `pdf_locale` | tinyText | Locale for PDF generation |
| `user` | unsignedInteger | Admin user who created the record |
| `sale_id` | unsignedInteger | Optional sale reference |
| `deleted_at` | timestamp | Soft delete |

### mfw_accounts_invoices_structure

Invoice line items.

| Column | Type | Notes |
|--------|------|-------|
| `id` | increments | |
| `invoice_id` | unsignedInteger | FK → mfw_accounts_invoices.id (cascadeOnDelete) |
| `content` | text | Line description |
| `quantity` | unsignedInteger | |
| `amount` | integer | Unit amount × 100 |
| `vat` | integer | VAT × 100 |
| `vat_id` | unsignedBigInteger | FK → mfw_accounts_vat.id |

### mfw_accounts_invoice_expense_associations

Links expense invoices to parent revenue invoices.

| Column | Type | Notes |
|--------|------|-------|
| `id` | id | |
| `parent_invoice_id` | unsignedInteger | FK → mfw_accounts_invoices.id (cascadeOnDelete) |
| `associated_invoice_id` | unsignedInteger | FK → mfw_accounts_invoices.id (cascadeOnDelete); unique |

---

## Payment & Channel Tables

### mfw_accounts_pay_means_channels

Links payment methods to bank accounts.

| Column | Type | Notes |
|--------|------|-------|
| `id` | increments | |
| `pay_mean_id` | unsignedInteger | FK → mfw_accounts_pay_means.id (cascadeOnDelete) |
| `bank_account_id` | unsignedInteger | FK → mfw_accounts_bank_accounts.id (nullOnDelete) |

### mfw_accounts_pay_means_channels_data

Translatable channel details.

| Column | Type | Notes |
|--------|------|-------|
| `id` | increments | |
| `pay_channel_id` | unsignedInteger | FK → mfw_accounts_pay_means_channels.id (cascadeOnDelete) |
| `name` | string | |
| `description` | text | |
| `lg` | string(2) | Locale code |

---

## Company Translatable Tables

### mfw_accounts_company_data

Locale-specific company information.

| Column | Type | Notes |
|--------|------|-------|
| `id` | increments | |
| `company_id` | unsignedInteger | |
| `lg` | string(2) | Locale code |
| `name` | text | |
| `owner` | text | |
| `adresse` | text | |
| `siege` | text | Registered office |
| `licence` | text | Legal notice |

### mfw_accounts_bank_accounts_data

Translatable bank account details.

| Column | Type | Notes |
|--------|------|-------|
| `id` | unsignedInteger | |
| `account_id` | unsignedInteger | |
| `lg` | string(2) | Locale code |
| `name` | text | |
| `bank` | text | |
| `address` | text | |

---

## Cashflow Tables

### mfw_accounts_cashflow

Transaction ledger.

| Column | Type | Notes |
|--------|------|-------|
| `id` | unsignedInteger | |
| `invoice_id` | unsignedInteger | Related invoice (nullable) |
| `account_id` | unsignedInteger | FK → mfw_accounts_accounts.id |
| `title` | text | |
| `amount` | decimal(10,2) | |
| `transaction_date` | date | |
| `bank_transaction_date` | date | Bank clearing date |
| `pay_mean` | unsignedInteger | Payment method used |
| `pay_id` | string | External payment reference |
| `link` | text | |

### mfw_accounts_cashflow_structure

Links cashflow entries to source invoices.

| Column | Type |
|--------|------|
| `cashflow_id` | unsignedInteger |
| `invoice_id` | unsignedInteger |

---

## Migration Order

Migrations run in this order (timestamps ensure correct dependency ordering):

1. `2026_02_21_000001` — Reference tables (currencies, VAT, doc types, pay means, company, bank accounts)
2. `2026_02_21_000002` — Account tables (accounts, addresses, business)
3. `2026_02_21_000003` — Invoice tables (invoices, structure, expense associations)
4. `2026_02_21_000004` — Company translatable + payment channel tables
5. `2026_02_21_000005` — Cashflow tables
