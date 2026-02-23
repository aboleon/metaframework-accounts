<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use MetaFramework\Accounts\Http\Controllers\DashboardController;
use MetaFramework\Accounts\Tests\Feature\Concerns\InteractsWithAccountsControllerData;
use MetaFramework\Accounts\Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAccountsControllerData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->disableMultilangForTests();
        $this->registerSqliteDateFormatFunction();
    }

    public function test_index_returns_dashboard_view_with_aggregates(): void
    {
        $client = $this->createAccount();
        $operator = $this->createSystemUser();

        DB::table('mfw_accounts_account_address')->insert([
            'user_id' => $client->id,
            'locality' => 'Paris',
            'country_code' => 'FR',
            'billing' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->createInvoice([
            'account' => $client,
            'operator' => $operator,
            'doc_type' => 1,
            'document_id' => 100,
            'invoice_date' => '15/01/2025',
            'amount' => 100,
            'vat' => 20,
        ]);
        $this->createInvoice([
            'account' => $client,
            'operator' => $operator,
            'doc_type' => 1,
            'document_id' => 101,
            'invoice_date' => '10/02/2026',
            'amount' => 200,
            'vat' => 40,
            'paid' => 1,
        ]);

        $view = (new DashboardController)->index(Request::create('/dashboard', 'GET', [
            'account_id' => $client->id,
        ]));

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('mfw-accounts::dashboard.index', $view->name());

        $data = $view->getData();
        $this->assertArrayHasKey('clients', $data);
        $this->assertArrayHasKey('turnover', $data);
        $this->assertArrayHasKey('turnoverExport', $data);
        $this->assertArrayHasKey('operative_turnover', $data);

        $this->assertSame(1, $data['clients']['total']);
        $this->assertArrayHasKey('FR', $data['clients']['named_countries']);
        $this->assertGreaterThanOrEqual(1, $data['turnover']->count());
        $this->assertGreaterThanOrEqual(1, $data['operative_turnover']->count());
    }
}
