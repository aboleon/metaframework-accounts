# Routes

## Overview

All package routes are loaded from `Routes/web.php`. The URL prefix is configurable via `config('mfw-accounts.route_prefix')` (default: `mfw-accounts`). Route **names** always start with `mfw-accounts.` regardless of the configured prefix.

All routes except the PDF route require the `web` and `auth` middleware.

## Full Route Table

### PDF (unauthenticated)

| Method | URI | Route Name | Controller |
|--------|-----|------------|------------|
| GET | `/mfw-accounts/pdf/{hash?}` | — | `PDF::show` |

The PDF route does **not** require authentication. Access is controlled by the unique `hash` stored on each invoice.

---

### Dashboard

| Method | URI | Route Name | Controller |
|--------|-----|------------|------------|
| GET | `/{prefix}/dashboard` | `mfw-accounts.dashboard.index` | `DashboardController@index` |

---

### Clients (Accounts)

| Method | URI | Route Name | Controller |
|--------|-----|------------|------------|
| GET | `/{prefix}/clients/search` | `mfw-accounts.clients.search` | `AccountController` |
| GET | `/{prefix}/clients` | `mfw-accounts.clients.index` | `AccountController` |
| GET | `/{prefix}/clients/create` | `mfw-accounts.clients.create` | `AccountController` |
| POST | `/{prefix}/clients` | `mfw-accounts.clients.store` | `AccountController` |
| GET | `/{prefix}/clients/{client}/edit` | `mfw-accounts.clients.edit` | `AccountController` |
| PUT | `/{prefix}/clients/{client}` | `mfw-accounts.clients.update` | `AccountController` |
| DELETE | `/{prefix}/clients/{client}` | `mfw-accounts.clients.destroy` | `AccountController` |
| GET | `/{prefix}/clients/{client}/dashboard` | `mfw-accounts.clients.dashboard` | `AccountController` |

---

### Invoices

| Method | URI | Route Name | Controller |
|--------|-----|------------|------------|
| GET | `/{prefix}/invoices` | `mfw-accounts.invoices.index` | `InvoiceController` |
| GET | `/{prefix}/invoices/create` | `mfw-accounts.invoices.create` | `InvoiceController` |
| POST | `/{prefix}/invoices` | `mfw-accounts.invoices.store` | `InvoiceController` |
| GET | `/{prefix}/invoices/{invoice}/edit` | `mfw-accounts.invoices.edit` | `InvoiceController` |
| PUT | `/{prefix}/invoices/{invoice}` | `mfw-accounts.invoices.update` | `InvoiceController` |
| DELETE | `/{prefix}/invoices/{invoice}` | `mfw-accounts.invoices.destroy` | `InvoiceController` |
| POST | `/{prefix}/invoices/{invoice}/duplicate` | `mfw-accounts.invoices.duplicate` | `InvoiceController@duplicate` |
| GET | `/{prefix}/invoices/mail-preview/{hash}` | `mfw-accounts.invoices.mail_preview` | `InvoiceController@mailPreview` |

---

### Company

| Method | URI | Route Name | Controller |
|--------|-----|------------|------------|
| GET | `/{prefix}/company/edit` | `mfw-accounts.company.edit` | `CompanyController@edit` |
| PUT | `/{prefix}/company` | `mfw-accounts.company.update` | `CompanyController@update` |

---

### Payment Methods (PayMeans)

| Method | URI | Route Name | Controller |
|--------|-----|------------|------------|
| GET | `/{prefix}/pay-means` | `mfw-accounts.pay-means.index` | `PayMeanController` |
| GET | `/{prefix}/pay-means/create` | `mfw-accounts.pay-means.create` | `PayMeanController` |
| POST | `/{prefix}/pay-means` | `mfw-accounts.pay-means.store` | `PayMeanController` |
| GET | `/{prefix}/pay-means/{payMean}/edit` | `mfw-accounts.pay-means.edit` | `PayMeanController` |
| PUT/PATCH | `/{prefix}/pay-means/{payMean}` | `mfw-accounts.pay-means.update` | `PayMeanController` |
| DELETE | `/{prefix}/pay-means/{payMean}` | `mfw-accounts.pay-means.destroy` | `PayMeanController` |

---

### Payment Channels (PayMeansChannels)

| Method | URI | Route Name | Controller |
|--------|-----|------------|------------|
| GET | `/{prefix}/PayMeansChannels/index/{payMean?}` | `mfw-accounts.pay-mean-channels.index` | `PayMeansChannelController@index` |
| POST | `/{prefix}/PayMeansChannels/make` | `mfw-accounts.pay-mean-channels.store` | `PayMeansChannelController@store` |
| GET/POST | `/{prefix}/PayMeansChannels/edit/{payMeansChannel}` | `mfw-accounts.pay-mean-channels.edit` | `PayMeansChannelController@edit` |
| POST/DELETE | `/{prefix}/PayMeansChannels/remove/{payMeansChannel}` | `mfw-accounts.pay-mean-channels.destroy` | `PayMeansChannelController@destroy` |

---

### VAT

| Method | URI | Route Name | Controller |
|--------|-----|------------|------------|
| GET | `/{prefix}/vat` | `mfw-accounts.vat.index` | `VatController` |
| GET | `/{prefix}/vat/create` | `mfw-accounts.vat.create` | `VatController` |
| POST | `/{prefix}/vat` | `mfw-accounts.vat.store` | `VatController` |
| GET | `/{prefix}/vat/{vat}/edit` | `mfw-accounts.vat.edit` | `VatController` |
| PUT | `/{prefix}/vat/{vat}` | `mfw-accounts.vat.update` | `VatController` |
| DELETE | `/{prefix}/vat/{vat}` | `mfw-accounts.vat.destroy` | `VatController` |

---

### Cashflow Document Types

| Method | URI | Route Name | Controller |
|--------|-----|------------|------------|
| GET | `/{prefix}/cashflow-doctypes` | `mfw-accounts.cashflow-doctypes.index` | `CashflowDocTypeController` |
| GET | `/{prefix}/cashflow-doctypes/create` | `mfw-accounts.cashflow-doctypes.create` | `CashflowDocTypeController` |
| POST | `/{prefix}/cashflow-doctypes` | `mfw-accounts.cashflow-doctypes.store` | `CashflowDocTypeController` |
| GET | `/{prefix}/cashflow-doctypes/{cashflowDocType}/edit` | `mfw-accounts.cashflow-doctypes.edit` | `CashflowDocTypeController` |
| PUT/PATCH | `/{prefix}/cashflow-doctypes/{cashflowDocType}` | `mfw-accounts.cashflow-doctypes.update` | `CashflowDocTypeController` |
| DELETE | `/{prefix}/cashflow-doctypes/{cashflowDocType}` | `mfw-accounts.cashflow-doctypes.destroy` | `CashflowDocTypeController` |

---

### Currency

| Method | URI | Route Name | Controller |
|--------|-----|------------|------------|
| GET | `/{prefix}/currency/index` | `mfw-accounts.currency.index` | `CurrencyController@index` |

---

### Geo

| Method | URI | Route Name | Controller |
|--------|-----|------------|------------|
| GET | `/{prefix}/geo` | `mfw-accounts.geo.index` | `GeoController` |
| ANY | `/{prefix}/geo/{any}` | — | `GeoController` |

---

### AJAX

| Method | URI | Route Name | Controller |
|--------|-----|------------|------------|
| POST/PUT/PATCH | `/{prefix}/ajax` | `mfw-accounts.ajax` | `AjaxController` |

The AJAX endpoint handles live search and other dynamic interactions (e.g., the `AccountSearch` Blade component uses this route).

---

## Route Prefix Customisation

To change the URL prefix, publish the config and update `route_prefix`:

```php
// config/mfw-accounts.php
return [
    'route_prefix' => 'admin/crm',
];
```

Route names remain `mfw-accounts.*` — only the URL path changes.

## Customer Portal Routes

The customer-facing routes live in `routes/account.php` (published separately). See [Front Account](front-account.md).
