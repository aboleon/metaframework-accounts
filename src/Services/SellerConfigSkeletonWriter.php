<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Services;

use Illuminate\Support\Facades\File;
use MetaFramework\Accounts\Models\AccountBusiness;

class SellerConfigSkeletonWriter
{
    /**
     * @return array{relative_path:string, created:bool, renamed:bool}|null
     */
    public function ensure(AccountBusiness $business, ?string $previousSlug = null): ?array
    {
        $slug = trim((string) ($business->seller_slug ?? ''));
        if (!(bool) ($business->is_seller ?? false) || $slug === '') {
            return null;
        }

        $directory = config_path('mfw-accounts/sellers');
        $path = $directory . DIRECTORY_SEPARATOR . $slug . '.php';
        $relativePath = 'config/mfw-accounts/sellers/' . $slug . '.php';

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $previousSlug = trim((string) $previousSlug);
        if ($previousSlug !== '' && $previousSlug !== $slug) {
            $previousPath = $directory . DIRECTORY_SEPARATOR . $previousSlug . '.php';

            if (File::exists($previousPath) && !File::exists($path)) {
                File::move($previousPath, $path);

                return [
                    'relative_path' => $relativePath,
                    'created' => false,
                    'renamed' => true,
                ];
            }
        }

        if (!File::exists($path)) {
            File::put($path, $this->stub($business));

            return [
                'relative_path' => $relativePath,
                'created' => true,
                'renamed' => false,
            ];
        }

        return [
            'relative_path' => $relativePath,
            'created' => false,
            'renamed' => false,
        ];
    }

    private function stub(AccountBusiness $business): string
    {
        $name = str_replace("'", "\\'", (string) ($business->translation('name', app()->getLocale()) ?: $business->seller_slug));

        return <<<PHP
<?php

declare(strict_types=1);

return [
    'name' => '{$name}',
    'slug' => '{$business->seller_slug}',
    'account_id' => {$business->user_id},

    // Put whatever you need here for seller-specific conditions.
    'hotels' => [],
    'rentacar' => [],
    'services' => [],
    'products' => [],
];
PHP;
    }
}
