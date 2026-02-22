<?php

namespace MetaFramework\Accounts\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MetaFramework\Polyglote\Interfaces\TranslatableInterface;
use MetaFramework\Polyglote\Traits\Translation;

class AccountBusiness extends Model implements TranslatableInterface
{

    use Translation;

    protected $table = 'mfw_accounts_account_business';

    protected $fillable = [
        'user_id',
        'name',
        'vat_number',
        'reg_number',
    ];

    public function setTranslatables(): array
    {
        return [
            'name' => __('mfw-accounts::ui.CompanyName'),
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'user_id');
    }
}
