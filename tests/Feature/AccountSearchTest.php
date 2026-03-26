<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use MetaFramework\Accounts\Enum\UserType;
use MetaFramework\Accounts\Models\Account;
use MetaFramework\Accounts\Tests\TestCase;

class AccountSearchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_find_account_by_keywords_can_be_filtered_by_account_type(): void
    {
        $this->actingAs($this->createSystemUser());

        $account = $this->createSearchableAccount(UserType::ACCOUNT, 'Searchable', 'Account');
        $company = $this->createSearchableAccount(UserType::COMPANY, 'Searchable', 'Company');
        $agent = $this->createSearchableAccount(UserType::AGENT, 'Searchable', 'Agent');

        $response = $this->postJson(route('mfw-accounts.ajax'), [
            'action' => 'findAccountByKeywords',
            'data' => 'Searchable',
            'account_type' => UserType::COMPANY->value,
        ]);

        $response->assertOk();
        $this->assertSame([$company->id], collect($response->json('accounts'))->pluck('id')->all());
        $this->assertNotContains($account->id, collect($response->json('accounts'))->pluck('id')->all());
        $this->assertNotContains($agent->id, collect($response->json('accounts'))->pluck('id')->all());
    }

    public function test_company_search_returns_company_display_name_instead_of_user_names(): void
    {
        $this->actingAs($this->createSystemUser());

        $company = $this->createSearchableAccount(UserType::COMPANY, 'Jane', 'Smith');
        $company->business()->create([
            'name' => 'Acme Travel',
        ]);

        $response = $this->postJson(route('mfw-accounts.ajax'), [
            'action' => 'findAccountByKeywords',
            'data' => 'Acme',
            'account_type' => UserType::COMPANY->value,
        ]);

        $response->assertOk();
        $response->assertJsonPath('accounts.0.id', $company->id);
        $response->assertJsonPath('accounts.0.display_name', 'Acme Travel');
        $response->assertJsonPath('accounts.0.first_name', null);
        $response->assertJsonPath('accounts.0.last_name', null);
        $response->assertJsonPath('accounts.0.business', 'Acme Travel');
    }

    private function createSystemUser(): User
    {
        return User::query()->create([
            'type' => UserType::SYSTEM->value,
            'email' => 'mfw-accounts-system-' . Str::uuid() . '@example.com',
            'password' => 'password',
            'first_name' => 'mfw-accounts',
            'last_name' => 'System',
        ]);
    }

    private function createSearchableAccount(UserType $type, string $firstName, string $lastName): Account
    {
        return Account::query()->create([
            'type' => $type->value,
            'email' => strtolower($type->value) . '-' . Str::uuid() . '@example.com',
            'password' => 'password',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'locale' => 'fr',
        ]);
    }
}
