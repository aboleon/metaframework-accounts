<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use MetaFramework\Accounts\Http\Controllers\GeoController;
use MetaFramework\Accounts\Tests\Feature\Concerns\InteractsWithAccountsControllerData;
use MetaFramework\Accounts\Tests\TestCase;

class GeoControllerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAccountsControllerData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->disableMultilangForTests();
    }

    public function test_index_groups_locations_and_excludes_empty_localities(): void
    {
        $userA = $this->createAccount();
        $userB = $this->createAccount();
        $userC = $this->createAccount();

        DB::table('mfw_accounts_account_address')->insert([
            'user_id' => $userA->id,
            'locality' => 'Paris',
            'country_code' => 'FR',
            'billing' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('mfw_accounts_account_address')->insert([
            'user_id' => $userA->id,
            'locality' => 'Paris',
            'country_code' => 'FR',
            'billing' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('mfw_accounts_account_address')->insert([
            'user_id' => $userB->id,
            'locality' => 'Paris',
            'country_code' => 'FR',
            'billing' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('mfw_accounts_account_address')->insert([
            'user_id' => $userC->id,
            'locality' => 'Lyon',
            'country_code' => 'FR',
            'billing' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('mfw_accounts_account_address')->insert([
            'user_id' => $userC->id,
            'locality' => '',
            'country_code' => 'FR',
            'billing' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $view = (new GeoController)->index();

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('mfw-accounts::geo.index', $view->name());

        $locations = $view->getData()['locations'];
        $this->assertSame(2, $locations->count());

        $this->assertSame('Paris', $locations[0]->locality);
        $this->assertSame('FR', $locations[0]->country_code);
        $this->assertSame(2, (int) $locations[0]->clients_count);

        $this->assertSame('Lyon', $locations[1]->locality);
        $this->assertSame(1, (int) $locations[1]->clients_count);
    }
}
