<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;
use MetaFramework\Accounts\Console\InstallFrontAccount;
use MetaFramework\Accounts\Models\Currency;
use MetaFramework\Accounts\Support\AccountModel;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class AccountsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../Config/mfw-accounts.php', 'mfw-accounts');
        $this->mergeConfigFrom(__DIR__ . '/../../publishables/config/mfw-user-types.php', 'mfw-user-types');
    }

    public function boot(): void
    {
        Route::bind('client', function (mixed $value) {
            $accountClass = AccountModel::className();

            return $accountClass::query()->findOrFail($value);
        });

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
                __DIR__ . '/../../publishables/config/mfw-user-types.php' => config_path('mfw-user-types.php'),
            ], 'mfw-user-types');

            $this->publishes([
                __DIR__ . '/../../publishables/config/mfw-user-types.php' => config_path('mfw-user-types.php'),
            ], 'mfw-accounts-user-types');

            $this->publishes([
                __DIR__ . '/../../Resources/views' => resource_path('views/vendor/mfw-accounts'),
            ], 'mfw-accounts-views');

            $this->publishes($this->translationPublishPaths(), 'mfw-accounts-translations');

            $this->publishes([
                __DIR__ . '/../../publishables/public' => public_path('vendor/mfw-accounts'),
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
        $sourcePath = __DIR__ . '/../../Resources/views';
        $viewPath = resource_path('views/vendor/mfw-accounts');

        $this->loadViewsFrom($sourcePath, 'mfw-accounts');

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'mfw-accounts-views');
    }

    protected function registerTranslations(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../Resources/lang', 'mfw-accounts');

        $this->callAfterResolving('translator', function ($translator): void {
            if (!$translator instanceof Translator) {
                return;
            }

            $this->loadLocaleFirstTranslationOverrides($translator);
        });
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

    private function translationPublishPaths(): array
    {
        $sourceRoot = __DIR__ . '/../../Resources/lang';
        $paths = [];

        foreach (glob($sourceRoot . '/*', GLOB_ONLYDIR) ?: [] as $localeDir) {
            $locale = basename($localeDir);

            if ($locale === '' || str_starts_with($locale, '.')) {
                continue;
            }

            $paths[$localeDir] = lang_path($locale . '/mfw-accounts');
        }

        return $paths;
    }

    private function loadLocaleFirstTranslationOverrides(Translator $translator): void
    {
        foreach (glob(lang_path('*/mfw-accounts'), GLOB_ONLYDIR) ?: [] as $localePath) {
            $locale = basename(dirname($localePath));
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($localePath));

            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
                    continue;
                }

                $fullPath = $fileInfo->getPathname();
                $relative = str_replace('\\', '/', substr($fullPath, strlen($localePath) + 1));
                $group = preg_replace('/\.php$/', '', $relative);
                if (!is_string($group) || $group === '') {
                    continue;
                }

                $overrideLines = require $fullPath;
                if (!is_array($overrideLines)) {
                    continue;
                }

                $baseLines = $translator->getLoader()->load($locale, $group, 'mfw-accounts');
                $mergedLines = array_replace_recursive($baseLines, $overrideLines);
                $flatLines = Arr::dot($mergedLines, $group . '.');

                if ($flatLines !== []) {
                    $translator->addLines($flatLines, $locale, 'mfw-accounts');
                }
            }
        }
    }
}
