<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MetaFramework\Accounts\Providers\AccountsServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [AccountsServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('app.locale', 'fr');
        $app['config']->set('app.fallback_locale', 'fr');
        $app['config']->set('mfw.translatable.locales', ['fr', 'bg', 'en']);
        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.defaults.passwords', 'users');
        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => User::class,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    private function setUpDatabase(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id')->nullable();
                $table->string('type')->nullable();
                $table->longText('first_name')->nullable();
                $table->longText('last_name')->nullable();
                $table->string('email')->nullable()->unique();
                $table->string('password')->nullable();
                $table->string('phone')->nullable();
                $table->string('civ')->nullable();
                $table->string('locale', 5)->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('seller_offers')) {
            Schema::create('seller_offers', function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('seller_offer_accounts')) {
            Schema::create('seller_offer_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('offer_id')->constrained('seller_offers')->cascadeOnDelete();
                $table->foreignId('account_id')->constrained('users')->cascadeOnDelete();
            });
        }

        $this->artisan('migrate', ['--database' => 'testing', '--force' => true])->run();
    }
}
