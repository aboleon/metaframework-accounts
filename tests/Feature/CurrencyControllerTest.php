<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\View\View;
use MetaFramework\Accounts\Http\Controllers\CurrencyController;
use MetaFramework\Accounts\Tests\Feature\Concerns\InteractsWithAccountsControllerData;
use MetaFramework\Accounts\Tests\TestCase;

class CurrencyControllerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAccountsControllerData;

    public function test_index_returns_currency_view_with_seeded_currencies(): void
    {
        $this->seedCurrency([
            'id' => 1,
            'name' => 'Euro',
            'code' => 'EUR',
            'sign' => '€',
            'default' => 1,
        ]);
        $this->seedCurrency([
            'id' => 2,
            'name' => 'US Dollar',
            'code' => 'USD',
            'sign' => '$',
            'default' => 0,
        ]);

        $view = (new CurrencyController)->index();

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('mfw-accounts::currency.index', $view->name());

        $data = $view->getData()['data'];
        $this->assertSame(2, $data->count());
        $this->assertSame(['EUR', 'USD'], $data->pluck('code')->all());
    }
}
