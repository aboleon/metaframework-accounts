<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Feature\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MetaFramework\Accounts\Models\Account;
use MetaFramework\Accounts\Models\CashflowDocTypes;
use MetaFramework\Accounts\Models\Invoice;
use MetaFramework\Accounts\Models\PayMeans;
use PDO;
use ReflectionProperty;

trait InteractsWithAccountsControllerData
{
    protected function enableMultilangForTests(): void
    {
        $this->setMultilangForTests(true);
    }

    protected function disableMultilangForTests(): void
    {
        $this->setMultilangForTests(false);
    }

    private function setMultilangForTests(bool $enabled): void
    {
        config()->set('mfw.translatable.multilang', $enabled);
        config()->set('mfw.translatable.active_locales', config('mfw.translatable.locales', ['fr', 'bg', 'en']));

        Cache::forget('mfw.multilang');

        $property = new ReflectionProperty(\MetaFramework\Accessors\Locale::class, 'multilangCache');
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    protected function registerSqliteDateFormatFunction(): void
    {
        $pdo = DB::connection()->getPdo();

        if (!$pdo instanceof PDO || !method_exists($pdo, 'sqliteCreateFunction')) {
            return;
        }

        $pdo->sqliteCreateFunction('date_format', function (?string $value, string $format): ?string {
            if (!$value) {
                return null;
            }

            try {
                $date = new \DateTimeImmutable($value);
            } catch (\Throwable) {
                return null;
            }

            return match ($format) {
                '%Y' => $date->format('Y'),
                default => $date->format('Y-m-d'),
            };
        }, 2);
    }

    protected function createSystemUser(array $overrides = []): User
    {
        return User::query()->create(array_merge([
            'type' => 'system',
            'email' => 'mfw-accounts-system-' . Str::uuid() . '@example.com',
            'password' => 'password',
            'first_name' => 'mfw-accounts',
            'last_name' => 'System',
        ], $overrides));
    }

    protected function createAccount(array $overrides = []): Account
    {
        return Account::query()->create(array_merge([
            'email' => 'mfw-accounts-account-' . Str::uuid() . '@example.com',
            'password' => 'password',
            'first_name' => 'Client',
            'last_name' => 'Example',
            'phone' => '+35970000000',
            'locale' => 'fr',
            'civ' => 'A',
        ], $overrides));
    }

    protected function seedCurrency(array $overrides = []): void
    {
        $payload = array_merge([
            'id' => 1,
            'name' => 'Euro',
            'code' => 'EUR',
            'sign' => 'EUR',
            'default' => 1,
            'conversion_rate' => 1,
        ], $overrides);

        DB::table('mfw_accounts_currencies')->updateOrInsert(
            ['id' => $payload['id']],
            $payload,
        );
    }

    protected function seedCashflowDocType(array $overrides = []): CashflowDocTypes
    {
        return CashflowDocTypes::query()->create(array_merge([
            'slug' => 'invoice-' . Str::random(6),
            'admin_name' => 'Invoice',
            'name' => 'Invoice',
            'default' => 0,
            'numerotation' => 'generic',
        ], $overrides));
    }

    protected function seedPayMean(array $overrides = []): PayMeans
    {
        return PayMeans::query()->create(array_merge([
            'name' => 'Wire transfer',
            'type' => 'bank',
        ], $overrides));
    }

    protected function seedBankAccount(array $overrides = []): int
    {
        $payload = array_merge([
            'id' => 1,
            'BIC' => 'BICCODE',
            'IBAN' => 'BE12345678901234567890',
        ], $overrides);

        DB::table('mfw_accounts_bank_accounts')->updateOrInsert(
            ['id' => $payload['id']],
            $payload,
        );

        return (int) $payload['id'];
    }

    protected function createInvoice(array $overrides = []): Invoice
    {
        $client = $overrides['account'] ?? null;
        $operator = $overrides['operator'] ?? null;

        unset($overrides['account'], $overrides['operator']);

        if (!$client instanceof Account) {
            $client = $this->createAccount();
        }

        if (!$operator instanceof User) {
            $operator = $this->createSystemUser();
        }

        $this->seedCurrency();

        return Invoice::query()->create(array_merge([
            'document_id' => 1,
            'account_id' => $client->id,
            'title' => 'Invoice title',
            'content' => 'Line',
            'amount' => 100,
            'quantity' => 1,
            'vat' => 20,
            'currency' => 1,
            'doc_type' => 1,
            'hash' => Str::random(40),
            'pdf_locale' => 'fr',
            'invoice_date' => '15/01/2026',
            'user' => $operator->id,
        ], $overrides));
    }
}
