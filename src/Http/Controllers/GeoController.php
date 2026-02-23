<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Controllers;

use Illuminate\View\View;
use MetaFramework\Accounts\Models\AccountAddress;

class GeoController
{
    public function index(): View
    {
        $locations = AccountAddress::query()
            ->selectRaw('locality, country_code, COUNT(DISTINCT user_id) as clients_count')
            ->whereNotNull('locality')
            ->where('locality', '!=', '')
            ->groupBy('country_code', 'locality')
            ->orderByDesc('clients_count')
            ->orderBy('country_code')
            ->orderBy('locality')
            ->get();

        return view('mfw-accounts::geo.index', [
            'locations' => $locations,
        ]);
    }
}
