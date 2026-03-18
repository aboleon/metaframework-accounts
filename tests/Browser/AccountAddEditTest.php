<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Browser;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use MetaFramework\Accounts\Models\Account;
use Tests\DuskTestCase;

class AccountAddEditTest extends DuskTestCase
{
    public function test_add_flow_creates_account_and_redirects_to_edit_page(): void
    {
        $systemUser = $this->createSystemUser();
        $email = 'dusk-mfw-accounts-add-' . Str::uuid() . '@example.com';
        $createdClientId = 0;
        $accountsPath = $this->accountsPath();
        $resourceEditPattern = '#^' . preg_quote($accountsPath . '/clients/', '#') . '\d+/edit$#';

        $this->browse(function (Browser $browser) use ($systemUser, $email, &$createdClientId, $accountsPath, $resourceEditPattern): void {
            $browser->loginAs($systemUser)
                ->visit($accountsPath . '/clients/add')
                ->waitFor('#account-client-form')
                ->type('first_name', 'Dusk')
                ->type('last_name', 'Created')
                ->type('phone', '+35981111111')
                ->type('email', $email)
                ->select('locale', 'fr');

            $browser->script("document.querySelector('#account-client-form .ajaxable').click();");

            $browser
                ->waitUsing(15, 250, function () use ($browser, $resourceEditPattern): bool {
                    $path = (string) parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);

                    return preg_match($resourceEditPattern, $path) === 1;
                }, 'Timed out waiting for redirect to the client edit page.')
                ->assertPathBeginsWith($accountsPath . '/clients/');

            $path = (string) parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH);
            preg_match('#^' . preg_quote($accountsPath . '/clients/', '#') . '(\d+)/edit$#', $path, $matches);
            $createdClientId = (int) ($matches[1] ?? 0);
        });

        $this->assertGreaterThan(0, $createdClientId);
        $this->assertDatabaseHas('users', [
            'id' => $createdClientId,
            'email' => $email,
            'phone' => '+35981111111',
            'locale' => 'fr',
        ]);
    }

    public function test_edit_flow_updates_existing_account_from_client_edit_page(): void
    {
        $systemUser = $this->createSystemUser();
        $account = $this->createAccount();
        $newEmail = 'dusk-mfw-accounts-edit-' . Str::uuid() . '@example.com';
        $newPhone = '+35982222222';
        $accountsPath = $this->accountsPath();

        $this->browse(function (Browser $browser) use ($systemUser, $account, $newEmail, $newPhone, $accountsPath): void {
            $browser->loginAs($systemUser)
                ->visit($accountsPath . '/clients/edit/' . $account->id)
                ->waitFor('#account-client-form')
                ->type('phone', $newPhone)
                ->type('email', $newEmail)
                ->select('locale', 'bg');

            $browser->script("document.querySelector('#account-client-form .ajaxable').click();");

            $browser
                ->waitUsing(8, 250, function () use ($browser, $account, $accountsPath): bool {
                    return (string) parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH)
                        === $accountsPath . '/clients/' . $account->id . '/edit';
                }, 'Timed out waiting for account edit page to remain open after save.')
                ->assertPathIs($accountsPath . '/clients/' . $account->id . '/edit');
        });

        $this->assertDatabaseHas('users', [
            'id' => $account->id,
            'email' => $newEmail,
            'phone' => $newPhone,
            'locale' => 'bg',
        ]);
    }

    private function createSystemUser(): User
    {
        return User::query()->create([
            'type' => 'system',
            'email' => 'dusk-mfw-accounts-system-' . Str::uuid() . '@example.com',
            'password' => 'password',
            'first_name' => 'Dusk',
            'last_name' => 'System',
        ]);
    }

    private function createAccount(): Account
    {
        return Account::query()->create([
            'email' => 'dusk-mfw-accounts-account-' . Str::uuid() . '@example.com',
            'password' => 'password',
            'first_name' => 'Initial',
            'last_name' => 'Account',
            'phone' => '+35980000000',
            'locale' => 'fr',
            'civ' => 'A',
        ]);
    }

    private function accountsPath(): string
    {
        $prefix = trim((string) config('mfw-accounts.route_prefix', 'mfw-accounts'), '/');

        return '/' . ($prefix !== '' ? $prefix : 'mfw-accounts');
    }
}
