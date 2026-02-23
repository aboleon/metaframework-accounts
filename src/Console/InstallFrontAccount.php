<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallFrontAccount extends Command
{
    protected $signature = 'mfw-accounts:front';

    protected $description = 'Publish and wire the front account login/dashboard skeleton';

    public function handle(): int
    {
        $this->newLine();
        $this->comment('Publishing Front Account Package...');
        $this->comment('------------------------------------------');

        $this->call('vendor:publish', [
            '--provider' => 'MetaFramework\\Accounts\\Providers\\AccountsServiceProvider',
            '--tag' => 'mfw-user-types',
        ]);
        $this->ensureUserTypesConfiguration();

        $this->call('vendor:publish', [
            '--provider' => 'MetaFramework\\Accounts\\Providers\\AccountsServiceProvider',
            '--tag' => 'mfw-accounts-front',
        ]);

        $this->ensureAccountRoutesAreLoaded();
        $this->ensureAccountGuardConfiguration();

        $this->comment('Front Account Package published successfully.');

        return self::SUCCESS;
    }

    private function ensureAccountRoutesAreLoaded(): void
    {
        $routeFilePath = base_path('routes/web.php');

        if (! File::exists($routeFilePath)) {
            $this->warn('routes/web.php was not found; skipping account routes registration.');

            return;
        }

        $existingContent = File::get($routeFilePath);
        $routeRequire = "require __DIR__ . '/account.php';";

        if (preg_match("/require\\s+__DIR__\\s*\\.\\s*['\"]\\/account\\.php['\"];/", $existingContent) === 1) {
            return;
        }

        $updatedContent = preg_replace(
            "/require\\s+__DIR__\\s*\\.\\s*['\"]\\/auth\\.php['\"];/",
            "$0\n" . $routeRequire,
            $existingContent,
            1,
            $authReplacementCount,
        );

        if ($authReplacementCount === 0 || ! is_string($updatedContent)) {
            $updatedContent = preg_replace(
                "/include\\s+__DIR__\\s*\\.\\s*['\"]\\/panel\\/routes\\.php['\"];/",
                $routeRequire . "\n$0",
                $existingContent,
                1,
                $panelReplacementCount,
            );

            if ($panelReplacementCount === 0 || ! is_string($updatedContent)) {
                $updatedContent = rtrim($existingContent) . PHP_EOL . PHP_EOL . $routeRequire . PHP_EOL;
            }
        }

        File::put($routeFilePath, $updatedContent);
    }

    private function ensureUserTypesConfiguration(): void
    {
        $targetPath = config_path('mfw-user-types.php');
        $sourcePath = __DIR__ . '/../../publishables/config/mfw-user-types.php';

        if (! File::exists($sourcePath)) {
            $this->warn('Package mfw-user-types config template was not found; skipping user types config repair.');

            return;
        }

        if (! File::exists($targetPath)) {
            File::ensureDirectoryExists(dirname($targetPath));
            File::copy($sourcePath, $targetPath);
            $this->info('Published config/mfw-user-types.php');

            return;
        }

        $existingContent = File::get($targetPath);

        if (! $this->mfwUserTypesConfigNeedsRepair($existingContent)) {
            return;
        }

        File::put($targetPath, File::get($sourcePath));
        $this->warn('Repaired config/mfw-user-types.php to the package-owned SYSTEM/ACCOUNT definition.');
    }

    private function mfwUserTypesConfigNeedsRepair(string $content): bool
    {
        if (str_contains($content, 'App\\Enum\\UserType')) {
            return true;
        }

        $requiredFragments = [
            'use MetaFramework\\Accounts\\Enum\\UserType;',
            "'column' => 'type'",
            "'default' => UserType::default()",
            'UserType::SYSTEM->value',
            'UserType::ACCOUNT->value',
            "'web' => UserType::SYSTEM->value",
            "'account' => UserType::ACCOUNT->value",
        ];

        foreach ($requiredFragments as $fragment) {
            if (! str_contains($content, $fragment)) {
                return true;
            }
        }

        return false;
    }

    private function ensureAccountGuardConfiguration(): void
    {
        $authConfigPath = config_path('auth.php');

        if (! File::exists($authConfigPath)) {
            $this->warn('config/auth.php was not found; skipping account guard setup.');

            return;
        }

        $existingContent = File::get($authConfigPath);
        $updatedContent = $this->ensureAuthSectionEntry(
            $existingContent,
            'guards',
            'account',
            "            'driver' => 'session',\n            'provider' => 'accounts',",
        );

        $updatedContent = $this->ensureAuthSectionEntry(
            $updatedContent,
            'providers',
            'accounts',
            "            'driver' => 'eloquent',\n            'model' => env('AUTH_ACCOUNT_MODEL', MetaFramework\\\\Accounts\\\\Models\\\\Account::class),",
        );

        $updatedContent = $this->ensureAuthSectionEntry(
            $updatedContent,
            'passwords',
            'accounts',
            "            'provider' => 'accounts',\n            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),\n            'expire' => 60,\n            'throttle' => 60,",
        );

        if ($updatedContent !== $existingContent) {
            File::put($authConfigPath, $updatedContent);
        }
    }

    private function ensureAuthSectionEntry(string $content, string $section, string $entryKey, string $entryBody): string
    {
        $sectionPosition = strpos($content, "'" . $section . "' => [");

        if ($sectionPosition === false) {
            $this->warn("Unable to locate auth config section [{$section}].");

            return $content;
        }

        $openBracketPosition = strpos($content, '[', $sectionPosition);

        if ($openBracketPosition === false) {
            $this->warn("Unable to parse auth config section [{$section}].");

            return $content;
        }

        $closeBracketPosition = $this->findMatchingBracketPosition($content, $openBracketPosition);

        if ($closeBracketPosition === null) {
            $this->warn("Unable to resolve closing bracket for auth config section [{$section}].");

            return $content;
        }

        $sectionContent = substr($content, $openBracketPosition + 1, $closeBracketPosition - $openBracketPosition - 1);

        if (preg_match("/'" . preg_quote($entryKey, '/') . "'\\s*=>\\s*\\[/", $sectionContent) === 1) {
            return $content;
        }

        $entry = "\n        '" . $entryKey . "' => [\n" . $entryBody . "\n        ],";

        return substr($content, 0, $closeBracketPosition) . $entry . substr($content, $closeBracketPosition);
    }

    private function findMatchingBracketPosition(string $content, int $openBracketPosition): ?int
    {
        $depth = 0;
        $contentLength = strlen($content);

        for ($index = $openBracketPosition; $index < $contentLength; $index++) {
            $character = $content[$index];

            if ($character === '[') {
                $depth++;

                continue;
            }

            if ($character === ']') {
                $depth--;

                if ($depth === 0) {
                    return $index;
                }
            }
        }

        return null;
    }
}
