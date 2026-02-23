<?php

declare(strict_types=1);

namespace App\Http\Controllers\Front\Account;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountPortalController extends Controller
{
    public function dashboard(): View
    {
        return view('front.account.dashboard', [
            'account' => Auth::guard('account')->user(),
        ]);
    }
}
