<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use MetaFramework\Accounts\Models\Dashboard;

class DashboardController
{
    public function index(Request $request): View
    {
        $filters = $request->all();

        $dashboard = new Dashboard;

        $years = $dashboard->years();

        $operativeTurnover = $dashboard->operativeTurnover($years);

        return view('mfw-accounts::dashboard.index')->with([
            'clients'         => $dashboard->statClients($filters, app()->getLocale()),
            'turnover'        => $dashboard->turnover(false, $filters),
            'turnoverExport'  => $dashboard->turnover(true, $filters),
            'operative_turnover' => collect($operativeTurnover),
        ]);
    }
}
