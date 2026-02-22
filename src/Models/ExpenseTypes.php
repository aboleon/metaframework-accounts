<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseTypes extends Model
{
    protected $table = 'mfw_accounts_expense_types';

    public $timestamps = false;
}
