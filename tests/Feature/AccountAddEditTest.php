<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use MetaFramework\Accounts\Models\Account;
use MetaFramework\Accounts\Tests\TestCase;

class AccountAddEditTest extends TestCase
{
    use DatabaseTransactions;

    public function test_clients_add_page_renders_form(): void
    {
        $this->actingAs($this->createSystemUser());

        $response = $this->get('/panel/mfw-accounts/clients/add');

        $response->assertOk();
        $response->assertViewIs('mfw-accounts::clients.edit');
        $response->assertSee('id="account-client-form"', false);
    }

    public function test_clients_edit_page_renders_form_for_existing_account(): void
    {
        $this->actingAs($this->createSystemUser());
        $account = $this->createAccount();

        $response = $this->get('/panel/mfw-accounts/clients/edit/' . $account->id);

        $response->assertOk();
        $response->assertViewIs('mfw-accounts::clients.edit');
        $response->assertSee('id="account-client-form"', false);
        $this->assertMatchesRegularExpression(
            '/name="object_id"\s+value="' . preg_quote((string) $account->id, '/') . '"/',
            (string) $response->getContent(),
        );
    }

    public function test_update_client_action_creates_account_from_ajax_add_flow(): void
    {
        $this->actingAs($this->createSystemUser());

        $email = 'mfw-accounts-add-' . Str::uuid() . '@example.com';
        $response = $this->postJson(route('mfw-accounts.ajax'), [
            'action' => 'update_client',
            'object' => 'Account',
            'first_name' => 'Alice',
            'last_name' => 'Martin',
            'email' => $email,
            'phone' => '+35970000001',
            'civ' => 'A',
            'locale' => 'fr',
        ]);

        $response->assertOk();
        $response->assertJsonPath('callback', 'redirectClientEdit');
        $response->assertJsonPath('redirect_delay', 3);
        $response->assertJsonStructure([
            'mfw_ajax_messages' => [
                ['success'],
            ],
            'client_id',
        ]);

        $clientId = (int) $response->json('client_id');
        $this->assertGreaterThan(0, $clientId);
        $this->assertDatabaseHas('users', [
            'id' => $clientId,
            'email' => $email,
            'phone' => '+35970000001',
            'locale' => 'fr',
        ]);
    }

    public function test_update_client_action_updates_existing_account_from_ajax_edit_flow(): void
    {
        $this->actingAs($this->createSystemUser());
        $account = $this->createAccount();
        $email = 'mfw-accounts-edit-' . Str::uuid() . '@example.com';

        $response = $this->postJson(route('mfw-accounts.ajax'), [
            'action' => 'update_client',
            'object' => 'Account',
            'object_id' => $account->id,
            'first_name' => 'Edited',
            'last_name' => 'Client',
            'email' => $email,
            'phone' => '+35970000002',
            'civ' => 'B',
            'locale' => 'bg',
        ]);

        $response->assertOk();
        $response->assertJsonPath('client_id', $account->id);
        $response->assertJsonMissingPath('callback');
        $response->assertJsonStructure([
            'mfw_ajax_messages' => [
                ['success'],
            ],
            'client_id',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $account->id,
            'email' => $email,
            'phone' => '+35970000002',
            'civ' => 'B',
            'locale' => 'bg',
        ]);
    }

    private function createSystemUser(): User
    {
        return User::query()->create([
            'type' => 'system',
            'email' => 'mfw-accounts-system-' . Str::uuid() . '@example.com',
            'password' => 'password',
            'first_name' => 'mfw-accounts',
            'last_name' => 'System',
        ]);
    }

    private function createAccount(): Account
    {
        return Account::query()->create([
            'email' => 'mfw-accounts-account-' . Str::uuid() . '@example.com',
            'password' => 'password',
            'first_name' => 'Existing',
            'last_name' => 'Client',
            'phone' => '+35970000000',
            'locale' => 'fr',
            'civ' => 'A',
        ]);
    }
}
