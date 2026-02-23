<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use MetaFramework\Accounts\Console\InstallFrontAccount;
use MetaFramework\Accounts\Models\Currency;
use Throwable;

class AccountsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../Config/mfw-accounts.php', 'mfw-accounts');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../Routes/web.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        $this->registerViews();
        $this->registerTranslations();
        $this->registerBladeComponents();
        $this->shareCurrencies();

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallFrontAccount::class,
            ]);

            $this->publishes([
                __DIR__ . '/../../Config/mfw-accounts.php' => config_path('mfw-accounts.php'),
            ], 'mfw-accounts-config');

            $this->publishes([
                __DIR__ . '/../../Resources/views' => resource_path('views/modules/mfw-accounts'),
            ], 'mfw-accounts-views');

            $this->publishes([
                __DIR__ . '/../../Resources/lang' => lang_path('modules/mfw-accounts'),
            ], 'mfw-accounts-translations');

            $this->publishes([
                __DIR__ . '/../../Resources/public' => public_path('vendor/mfw-accounts'),
            ], 'mfw-accounts-assets');

            $this->publishes([
                __DIR__ . '/../../publishables/account/' => base_path(),
            ], 'mfw-accounts-front');

            // BC alias for the previous MetaFramework tag name.
            $this->publishes([
                __DIR__ . '/../../publishables/account/' => base_path(),
            ], 'mfw-account');
        }
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/mfw-accounts');
        $sourcePath = __DIR__ . '/../../Resources/views';

        $this->loadViewsFrom(array_merge(array_map(static function (string $path): string {
            return $path . '/modules/mfw-accounts';
        }, Config::get('view.paths')), [$sourcePath]), 'mfw-accounts');

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'mfw-accounts-views');
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/mfw-accounts');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'mfw-accounts');

            return;
        }

        $this->loadTranslationsFrom(__DIR__ . '/../../Resources/lang', 'mfw-accounts');
    }

    protected function registerBladeComponents(): void
    {
        Blade::componentNamespace('MetaFramework\\Accounts\\View\\Components', 'mfw-accounts');
    }

    protected function shareCurrencies(): void
    {
        $currencies = collect();

        try {
            $currencies = Cache::rememberForever('currencies', static function () {
                return Currency::query()->orderBy('code')->get();
            });
        } catch (Throwable $exception) {
            Log::warning('AccountsServiceProvider: unable to cache currencies.', [
                'error' => $exception->getMessage(),
            ]);
        }

        View::share('currencies', $currencies);
    }
}
