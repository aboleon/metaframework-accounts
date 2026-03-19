# Accounts (Clients)

## Overview

The `Account` model represents a client record. Accounts are stored in the shared `users` table, with related package data split into dedicated account tables such as addresses, business data, and invoices.

## Model: Account

**Table:** `users`

### Key Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | unsignedBigInteger | Primary key |
| `account_id` | unsignedBigInteger nullable | Optional external / legacy account reference |
| `first_name` | longText | First name (translatable) |
| `last_name` | longText | Last name (translatable) |
| `email` | string | Email address |
| `phone` | string | Phone number |
| `password` | string | Login password hash / token |
| `civ` | string | Civility code |
| `locale` | string(5) | Preferred locale |
| `created_at` | timestamp | Creation date |
| `updated_at` | timestamp | Last update date |

### Relations

| Relation | Type | Target |
|----------|------|--------|
| `address()` | HasMany | `AccountAddress` |
| `business()` | HasOne | `AccountBusiness` |
| `currencyType()` | BelongsTo | `Currency` |
| `documents()` | HasMany | `Invoice` (non-duplicata) |
| `duplicata()` | HasMany | `Invoice` (duplicata only) |
| `invoices()` | HasMany | `Invoice` (doc_type 1 or 5) |

### Accessors

- `identity()` — Returns a formatted display name for the account.
- `isCompany()` — Returns `true` when the account has a company name set.

### Query Scopes

| Scope | Parameters | Description |
|-------|-----------|-------------|
| `scopeClientId` | `?int $clientId` | Filter by account ID |
| `scopeClientType` | `?int $clientType` | `1` = Individual, `2` = Company |
| `scopeCompany` | `?string $company` | Filter by company name |
| `scopeDateRange` | `$operator, $date, $date2` | Filter by creation date |
| `scopeFilters` | `array $filters` | Combined filter scope used by the index controller |

### Host App Extension

The package resolves the account model through `config('mfw-accounts.models.account')`. A host application can point that config value to its own model class extending `MetaFramework\Accounts\Models\Account`.

That extended model is the correct place for project-specific relations and client-index customization.

Available hooks on the resolved model:

| Method | Purpose |
|--------|---------|
| `applyClientIndexQuery(Builder $query, array $filters = []): Builder` | Modify the package clients index query before pagination |
| `clientIndexViewData(LengthAwarePaginator $clients, array $filters = [], ?Request $request = null): array` | Provide extra view data to the clients index view |

Example host-app model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use MetaFramework\Accounts\Models\Account as PackageAccount;
use Modules\Seller\Models\Offer;

class Account extends PackageAccount
{
    public static function applyClientIndexQuery(Builder $query, array $filters = []): Builder
    {
        return $query->withCount('offers');
    }

    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class, 'seller_offer_accounts', 'account_id', 'offer_id');
    }
}
```

## Model: AccountAddress

**Table:** `mfw_accounts_account_address`

Each account can have multiple addresses. The `billing` flag marks the default billing address.

### Key Fields

| Field | Type | Description |
|-------|------|-------------|
| `user_id` | unsignedBigInteger | FK → users.id |
| `billing` | boolean | `true` = default billing address |
| `street_number` | string | Street number |
| `route` | longText | Street name |
| `locality` | longText | City |
| `postal_code` | string | Postal / ZIP code |
| `country_code` | string | ISO country code |
| `place_id` | string | Google Places place ID |
| `lat` | decimal(16,13) | Latitude |
| `lon` | decimal(16,13) | Longitude |
| `company` | string | Company at this address |
| `complementary` | text | Additional address line |
| `administrative_area_level_1` | longText | Region / state |
| `administrative_area_level_2` | longText | Department / county |
| `text_address` | longText | Full plain-text address |

Google Places integration stores `place_id`, `lat`, and `lon` for geocoded addresses.

## Model: AccountBusiness

**Table:** `mfw_accounts_account_business`

Stores business-specific information linked to an account.

| Field | Type | Description |
|-------|------|-------------|
| `user_id` | unsignedBigInteger | FK → users.id |
| `name` | longText | Business name (translatable) |
| `vat_number` | string | VAT registration number |
| `reg_number` | string | Company registration number |

## Controller Actions

The `AccountController` provides the following actions (all under the `mfw-accounts.*` route prefix):

| Action | Route | Description |
|--------|-------|-------------|
| Index | `GET /clients` | Paginated list with filters |
| Create | `GET /clients/create` | New account form |
| Store | `POST /clients` | Save new account |
| Edit | `GET /clients/{client}/edit` | Edit form |
| Update | `PUT /clients/{client}` | Save changes |
| Destroy | `DELETE /clients/{client}` | Delete account |
| Dashboard | `GET /clients/{client}/dashboard` | Per-client invoice dashboard |
| Search | `GET /clients/search` | AJAX search endpoint |

### Index Filters

The index view supports filtering by:

- Client type (individual / company)
- Company name
- Creation date range

## Blade Component: Account Search

The `AccountSearch` component provides an AJAX-powered search widget for selecting a client.

```blade
<x-mfw-accounts::account-search />
```

### Component Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `label` | string | `''` | Field label |
| `placeholder` | string | `'search_client'` | Input placeholder translation key |
| `clientName` | string\|null | `null` | Pre-selected client name |
| `clientId` | int\|null | `null` | Pre-selected client ID |
| `clientNameInputName` | string | `'account_name'` | Hidden input name for display value |
| `clientInputName` | string | `'account_id'` | Hidden input name for ID value |
| `ajaxUrl` | string | `route('mfw-accounts.ajax')` | AJAX search URL |
| `showToggle` | bool | `false` | Show a toggle checkbox |
| `toggleName` | string | `'attach_to_account'` | Toggle checkbox name |
| `multiple` | bool | `false` | Allow multiple selections |
| `headers` | array | `[]` | Additional column headers |
| `createUrl` | string | `route('mfw-accounts.clients.create')` | URL for the "New Client" button |
| `createLabel` | string | `'NewClientAccountBtn'` | Translation key for create button |
