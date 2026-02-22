<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Feature;

use MetaFramework\Accounts\Tests\TestCase;
use MetaFramework\Accounts\View\Components\AccountSearch;

class RoutePrefixConfigTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('mfw-accounts.route_prefix', 'accounts-admin');
    }

    public function test_named_routes_use_configured_route_prefix(): void
    {
        $this->assertSame('/panel/accounts-admin/company/edit', route('mfw-accounts.company.edit', absolute: false));
        $this->assertSame('/panel/accounts-admin/ajax', route('mfw-accounts.ajax', absolute: false));
    }

    public function test_account_search_component_uses_named_ajax_route(): void
    {
        $component = new AccountSearch();

        $this->assertSame('/panel/accounts-admin/ajax', $component->ajaxUrl);
    }
}
