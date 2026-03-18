<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Feature;

use Illuminate\Support\ServiceProvider;
use MetaFramework\Accounts\Providers\AccountsServiceProvider;
use MetaFramework\Accounts\Tests\TestCase;

class AccountsServiceProviderTest extends TestCase
{
    public function test_it_publishes_views_to_the_vendor_directory(): void
    {
        $paths = ServiceProvider::pathsToPublish(AccountsServiceProvider::class, 'mfw-accounts-views');

        $this->assertCount(1, $paths);
        $this->assertSame(resource_path('views/vendor/mfw-accounts'), array_values($paths)[0]);
        $this->assertSame(
            realpath(__DIR__ . '/../../Resources/views'),
            realpath((string) array_key_first($paths)),
        );
    }

    public function test_it_does_not_register_the_legacy_modules_view_hint(): void
    {
        $viewHints = $this->app['view']->getFinder()->getHints()['mfw-accounts'];

        $this->assertNotContains(resource_path('views/modules/mfw-accounts'), $viewHints);
    }
}
