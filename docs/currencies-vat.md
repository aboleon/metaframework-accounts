# Currencies and VAT

## Currencies

### Model: Currency

**Table:** `mfw_accounts_currencies`

| Field | Type | Description |
|-------|------|-------------|
| `id` | unsignedInteger | Primary key |
| `name` | text | Currency name |
| `code` | string(3) | ISO 4217 currency code (host-defined) |
| `sign` | tinyText | Display symbol (e.g. `€`, `$`) |
| `default` | unsignedTinyInteger | Default currency flag |
| `conversion_rate` | decimal(10,5) | Conversion rate to the reference currency |

### Static Methods

```php
Currency::getCurrencies(): Collection    // All currencies
```

Currencies are cached by the service provider at boot time and shared with all views.

### Currency in Invoices

Every invoice stores the currency as a foreign key (`currency` → `mfw_accounts_currencies.id`). The active default currency is determined by the `default` flag on the `Currency` model.

### Multi-Currency Reporting

The dashboard aggregates invoice amounts across currencies using each currency's `conversion_rate` field. Currencies must be seeded with an appropriate rate relative to your reference currency. See [Dashboard](dashboard.md).

---

## VAT

### Model: Vat

**Table:** `mfw_accounts_vat`

Soft-deleted to preserve historical records referenced by invoices.

| Field | Type | Description |
|-------|------|-------------|
| `rate` | unsignedInteger | VAT rate stored as integer (cast via `PriceInteger`, e.g. `2000` = 20%) |
| `default` | boolean | Default VAT flag (only one can be default at a time) |
| `deleted_at` | timestamp | Soft-delete timestamp |

### Default VAT Behaviour

Calling `$vat->manageDefaultState()` after saving a VAT record automatically clears the `default` flag on all other VAT records. This ensures exactly one VAT rate is marked as default at any time.

### CRUD Routes

| Action | Method | URI | Route Name |
|--------|--------|-----|------------|
| List | GET | `/vat` | `mfw-accounts.vat.index` |
| Create | GET | `/vat/create` | `mfw-accounts.vat.create` |
| Store | POST | `/vat` | `mfw-accounts.vat.store` |
| Edit | GET | `/vat/{vat}/edit` | `mfw-accounts.vat.edit` |
| Update | PUT | `/vat/{vat}` | `mfw-accounts.vat.update` |
| Delete | DELETE | `/vat/{vat}` | `mfw-accounts.vat.destroy` |

Deleted VAT rates remain in the database (soft-deleted) so that existing invoices retain their VAT reference.

### VAT on Invoices

VAT can be applied at two levels:

1. **Invoice level** — `Invoice.vat` stores the total VAT amount; `Invoice.vat_id` links to the applicable rate.
2. **Line item level** — `InvoiceStructure.vat` stores the VAT per line; `InvoiceStructure.vat_id` links to the rate used for that line.

Both `amount` and `vat` fields on `Invoice` and `InvoiceStructure` are cast via `PriceInteger` (stored as integers, divided by 100 when read).
