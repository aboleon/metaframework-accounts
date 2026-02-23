# Payments

## Overview

The payment system covers payment methods, bank accounts, and the cashflow ledger. Payment methods (`PayMeans`) can be linked to specific bank accounts through channels (`PayMeansChannels`).

## Model: PayMeans (Payment Methods)

**Table:** `mfw_accounts_pay_means`

| Field | Type | Description |
|-------|------|-------------|
| `type` | string(50) | Machine-readable type identifier |
| `name` | longText | Display name (translatable) |

### Relations

- `channels()` — HasMany → `PayMeansChannels`

### Static Methods

- `fetchPayMeansByLocale(?string $locale)` — Returns payment methods with translations for the given locale.
- `select_form($value, string $form_name)` — Returns an HTML `<select>` element for use in forms.

### CRUD Routes

| Action | Method | URI | Route Name |
|--------|--------|-----|------------|
| List | GET | `/pay-means` | `mfw-accounts.pay-means.index` |
| Create | GET | `/pay-means/create` | `mfw-accounts.pay-means.create` |
| Store | POST | `/pay-means` | `mfw-accounts.pay-means.store` |
| Edit | GET | `/pay-means/{payMean}/edit` | `mfw-accounts.pay-means.edit` |
| Update | PUT/PATCH | `/pay-means/{payMean}` | `mfw-accounts.pay-means.update` |
| Delete | DELETE | `/pay-means/{payMean}` | `mfw-accounts.pay-means.destroy` |

## Model: PayMeansChannels

**Table:** `mfw_accounts_pay_means_channels`

Links a payment method to a bank account, creating a named "channel" (e.g. "Stripe → IBAN FR76...").

| Field | Type | Description |
|-------|------|-------------|
| `pay_mean_id` | unsignedInteger | FK → mfw_accounts_pay_means.id |
| `bank_account_id` | unsignedInteger | FK → mfw_accounts_bank_accounts.id (nullable) |

Translatable display data is stored in the companion table `mfw_accounts_pay_means_channels_data`:

| Field | Type | Description |
|-------|------|-------------|
| `pay_channel_id` | unsignedInteger | FK → mfw_accounts_pay_means_channels.id |
| `name` | string | Channel name |
| `description` | text | Channel description |
| `lg` | string(2) | Locale code |

### Relations

- `translation()` — Current-locale translation (`PayMeansChannelsData`)
- `translations()` — All translations (`PayMeansChannelsData`)
- `master()` — Parent `PayMeans`

### Routes

| Action | Method | URI | Route Name |
|--------|--------|-----|------------|
| List | GET | `/PayMeansChannels/index/{payMean?}` | `mfw-accounts.pay-mean-channels.index` |
| Store | POST | `/PayMeansChannels/make` | `mfw-accounts.pay-mean-channels.store` |
| Edit | GET/POST | `/PayMeansChannels/edit/{payMeansChannel}` | `mfw-accounts.pay-mean-channels.edit` |
| Delete | POST/DELETE | `/PayMeansChannels/remove/{payMeansChannel}` | `mfw-accounts.pay-mean-channels.destroy` |

## Model: BankAccounts

**Table:** `mfw_accounts_bank_accounts`

| Field | Type | Description |
|-------|------|-------------|
| `BIC` | text | BIC / SWIFT code |
| `IBAN` | string(22) | IBAN number |

Translatable details are stored in `mfw_accounts_bank_accounts_data`:

| Field | Type | Description |
|-------|------|-------------|
| `account_id` | unsignedInteger | FK → mfw_accounts_bank_accounts.id |
| `name` | text | Account display name |
| `bank` | text | Bank name |
| `address` | text | Bank address |
| `lg` | string(2) | Locale code |

### Static Methods

- `accounts($lang)` — Returns all bank accounts with their translation for the given locale.
- `fetchAccounts()` — Returns accounts using the current locale.

## Invoice Payment Fields

Payment is recorded directly on the `Invoice` model:

| Field | Description |
|-------|-------------|
| `paid` | Payment status flag (`string(1)`, nullable = unpaid) |
| `pay_mean` | FK → `mfw_accounts_pay_means.id` |
| `date_paid` | Date payment was received |
| `bank_account` | FK → `mfw_accounts_bank_accounts.id` |

## Cashflow Ledger

### Model: Cashflow

**Table:** `mfw_accounts_cashflow`

A transaction ledger recording individual cash movements.

| Field | Type | Description |
|-------|------|-------------|
| `id` | unsignedInteger | Primary key |
| `invoice_id` | unsignedInteger | Related invoice (nullable) |
| `account_id` | unsignedInteger | FK → mfw_accounts_accounts.id |
| `title` | text | Transaction description |
| `amount` | decimal(10,2) | Transaction amount |
| `transaction_date` | date | Date of transaction |
| `bank_transaction_date` | date | Bank clearing date |
| `pay_mean` | unsignedInteger | Payment method used |
| `pay_id` | string | External payment reference |
| `link` | text | Related URL or reference |

### Model: CashflowStructure

**Table:** `mfw_accounts_cashflow_structure`

Links cashflow entries to their source invoices.

| Field | Type |
|-------|------|
| `cashflow_id` | unsignedInteger |
| `invoice_id` | unsignedInteger |
