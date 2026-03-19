<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use MetaFramework\Accounts\Models\Account as AccountsPackageAccount;

class Account extends AccountsPackageAccount
{
    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class, 'seller_offer_accounts', 'account_id', 'offer_id');
    }
}
