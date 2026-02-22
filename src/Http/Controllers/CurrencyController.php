<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use MetaFramework\Accounts\Models\Currency;

class CurrencyController extends Controller
{
    public function index(): View
    {
        return view('mfw-accounts::currency.index')->with('data', Currency::query()->get());
    }
}
