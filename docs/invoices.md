# Invoices

## Overview

The invoice system manages financial documents (invoices, quotes, etc.) linked to client accounts. Document types are configurable via `CashflowDocTypes`, allowing different numbering sequences per type.

## Model: Invoice

**Table:** `mfw_accounts_invoices`

### Key Fields

| Field | Type | Description |
|-------|------|-------------|
| `document_id` | unsignedInteger | Document number within its doc type |
| `account_id` | unsignedBigInteger | FK → users.id |
| `doc_type` | unsignedInteger | FK → mfw_accounts_cashflow_doc_types.id |
| `hash` | string(40) | Unique hash for PDF URL (no auth required) |
| `title` | text | Document title |
| `subtitle` | text | Document subtitle |
| `content` | text | Main body text |
| `notes` | text | Internal notes |
| `amount` | integer | Total amount (stored as integer, cast via `PriceInteger`) |
| `vat` | integer | VAT amount |
| `expenses` | unsignedInteger | Total expenses (computed) |
| `net_gain` | integer | Net gain after expenses (computed) |
| `net_gain_percent` | integer | Net gain as percentage (computed) |
| `expense_protocol_ref` | string | Reference for expense protocol |
| `vat_id` | unsignedInteger | FK → mfw_accounts_vat.id |
| `currency` | unsignedInteger | FK → mfw_accounts_currencies.id |
| `paid` | string(1) | Payment status flag |
| `pay_mean` | unsignedInteger | FK → mfw_accounts_pay_means.id |
| `date_paid` | date | Date payment was received |
| `invoice_date` | date | Invoice date |
| `transaction_date` | date | Transaction date |
| `date_before` | date | Due date |
| `sent_at` | timestamp | When the invoice email was sent |
| `duplicata` | string(1) | Marks the record as a duplicate |
| `bank_account` | unsignedInteger | FK → mfw_accounts_bank_accounts.id |
| `attached_to` | unsignedInteger | FK → parent invoice (for attachments) |
| `pdf_locale` | tinyText | Locale used for PDF generation |
| `sell_channel` | unsignedTinyInteger | FK → mfw_accounts_sell_channels.id |
| `user` | unsignedInteger | Admin user who created the document |

> **Note:** `net_gain`, `net_gain_percent`, and `expenses` are computed fields. Do not edit them manually; they are updated automatically via the expense accessor.

### Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `DEFAULT_DOC_TYPE` | `5` | Default document type ID |
| `DEFAULT_CURRENCY` | `1` | Default currency ID |
| `EUR_CURRENCY_ID` | `1` | Reference currency ID |

### Relations

| Relation | Type | Target |
|----------|------|--------|
| `client()` | BelongsTo | `Account` |
| `currencyType()` | BelongsTo | `Currency` |
| `docType()` | BelongsTo | `CashflowDocTypes` |
| `details()` | HasMany | `InvoiceStructure` (line items) |
| `payMean()` | BelongsTo | `PayMeans` |
| `vat()` | BelongsTo | `Vat` |
| `createdBy()` | BelongsTo | `User` (admin) |
| `duplicatas()` | HasMany | `Invoice` (where duplicata = 1) |
| `attachedTo()` | BelongsTo | `Invoice` (parent, doc_type = 1) |
| `facturation()` | HasMany | `Invoice` (attached children, doc_type = 1) |
| `cashflow()` | — | `CashflowStructure` lookup |
| `expenseAssociatedInvoices()` | BelongsToMany | `Invoice` (via expense_associations pivot) |

### Accessors

- `getOperatorAttribute()` — Returns the operator sign for the document.
- `getTotalAmountAttribute()` — Returns `amount + vat` as a float.

### Query Scopes

| Scope | Description |
|-------|-------------|
| `scopeFilters(array $filters)` | Combined filter scope (client, doc type, currency, paid status) |
| `scopeAmounts(array $filters)` | Filter by amount range |
| `scopeDateRange(array $filters)` | Filter by invoice date range |
| `scopeIsPaid(array $filters)` | Filter by paid status |
| `scopeExport(bool $export)` | Adjust query for Excel export |
| `scopeSale(?int $saleId)` | Filter by sale ID |

### Static Methods

| Method | Description |
|--------|-------------|
| `getClientInvoices(int $client): array` | Returns all invoices for a client |
| `nextDocumentId(int $docType): int` | Returns the next document number for a doc type |
| `findWithDetails(int $id): ?self` | Eager-loads an invoice with its line items |

## Model: InvoiceStructure (Line Items)

**Table:** `mfw_accounts_invoices_structure`

Each invoice can have multiple line items.

| Field | Type | Description |
|-------|------|-------------|
| `invoice_id` | unsignedInteger | FK → mfw_accounts_invoices.id |
| `content` | text | Line item description |
| `quantity` | unsignedInteger | Quantity |
| `amount` | integer | Unit price (cast via `PriceInteger`) |
| `vat` | integer | VAT amount (cast via `PriceInteger`) |
| `vat_id` | unsignedBigInteger | FK → mfw_accounts_vat.id |

## Model: CashflowDocTypes (Document Types)

**Table:** `mfw_accounts_cashflow_doc_types`

Defines the available document types (invoice, quote, credit note, etc.) with configurable numbering.

| Field | Type | Description |
|-------|------|-------------|
| `slug` | string | Machine-readable identifier |
| `admin_name` | longText | Admin display name (translatable) |
| `name` | longText | Customer-facing name (translatable) |
| `default` | unsignedTinyInteger | Default doc type flag |
| `accountable` | unsignedTinyInteger | Whether this type appears in accounting |
| `inventory` | tinyInteger | Inventory flag |
| `numerotation` | enum | `generic` (shared sequence) or `own` (per-type sequence) |

## CRUD Operations

All operations require the `auth` middleware.

| Action | Method | URI | Route Name |
|--------|--------|-----|------------|
| List | GET | `/invoices` | `mfw-accounts.invoices.index` |
| Create form | GET | `/invoices/create` | `mfw-accounts.invoices.create` |
| Store | POST | `/invoices` | `mfw-accounts.invoices.store` |
| Edit form | GET | `/invoices/{invoice}/edit` | `mfw-accounts.invoices.edit` |
| Update | PUT | `/invoices/{invoice}` | `mfw-accounts.invoices.update` |
| Delete | DELETE | `/invoices/{invoice}` | `mfw-accounts.invoices.destroy` |

### Index Filters

The invoice list supports filtering by:

- Date range (`invoice_date`)
- Currency
- Document type
- Payment status (paid / unpaid)
- Client (account)
- Amount range

## Duplicate Invoice

Create a copy of an existing invoice:

```
POST /invoices/{invoice}/duplicate
```

Route name: `mfw-accounts.invoices.duplicate`

The duplicate is saved with `duplicata` set, preserving the original document structure.

## PDF Generation

Each invoice has a unique `hash` field. The PDF URL does **not** require authentication:

```
GET /mfw-accounts/pdf/{hash}
```

The hash is generated on creation. Share this URL to give clients direct access to their invoice PDF without requiring a login. PDF rendering uses `barryvdh/laravel-dompdf`.

## Email Dispatch

Invoices can be emailed to the client via the `Mailer\Invoice` class (from `aboleon/metaframework-mailer`).

Preview the email before sending:

```
GET /invoices/mail-preview/{hash}
```

Route name: `mfw-accounts.invoices.mail_preview`

## Excel Export

The invoice list can be exported to Excel using `InvoicesIndexExport` (backed by `maatwebsite/excel`). The export respects the currently active filters.
