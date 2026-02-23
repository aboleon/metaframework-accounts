<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;


use Illuminate\Database\Eloquent\Model;
use MetaFramework\Traits\Locale;

class BankAccounts extends Model
{

    use Locale;

    protected $table = 'mfw_accounts_bank_accounts';
    public $timestamps = false;

    public static function accounts($lang)
    {
        return BankAccountsData::whereLg($lang)->get();
    }

    public function fetchAccounts()
    {
        return self::with('translation')->get();
    }

    public function translation()
    {
        return $this->hasOne(BankAccountsData::class, 'account_id')->where('lg', $this->locale());
    }

}
