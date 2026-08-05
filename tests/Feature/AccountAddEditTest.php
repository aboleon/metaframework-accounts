<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use MetaFramework\Accounts\Enum\UserType;
use MetaFramework\Accounts\Models\Account;
use MetaFramework\Accounts\Tests\TestCase;

class AccountAddEditTest extends TestCase
{
    use DatabaseTransactions;

    public function test_clients_add_page_renders_form(): void
    {
        $this->actingAs($this->createSystemUser());

        $response = $this->get($this->accountsPath('clients/add'));

        $response->assertOk();
        $response->assertViewIs('mfw-accounts::clients.edit');
        $response->assertSee('id="account-client-form"', false);
    }

    public function test_clients_edit_page_renders_form_for_existing_account(): void
    {
        $this->actingAs($this->createSystemUser());
        $account = $this->createAccount();

        $response = $this->get($this->accountsPath('clients/edit/' . $account->id));

        $response->assertOk();
        $response->assertViewIs('mfw-accounts::clients.edit');
        $response->assertSee('id="account-client-form"', false);
        $this->assertMatchesRegularExpression(
            '/name="object_id"\s+value="' . preg_quote((string) $account->id, '/') . '"/',
            (string) $response->getContent(),
        );
    }

    public function test_clients_edit_page_renders_tabbed_layout_for_regular_account(): void
    {
        $this->actingAs($this->createSystemUser());
        $account = $this->createAccount();

        $response = $this->get($this->accountsPath('clients/edit/' . $account->id));

        $response->assertOk();
        $content = (string) $response->getContent();

        $this->assertMatchesRegularExpression('/id="account-tab-info"/', $content);
        $this->assertMatchesRegularExpression('/id="account-tab-address"/', $content);
        $this->assertMatchesRegularExpression('/class="nav-link d-none"[^>]*id="account-tab-company-info"/', $content);
        $this->assertMatchesRegularExpression('/class="nav-link d-none"[^>]*id="account-tab-company-agents"/', $content);
    }

    public function test_clients_edit_page_renders_company_tabs_for_company_account(): void
    {
        $this->actingAs($this->createSystemUser());
        $account = $this->createCompanyAccount();

        $response = $this->get($this->accountsPath('clients/edit/' . $account->id));

        $response->assertOk();
        $content = (string) $response->getContent();

        $this->assertMatchesRegularExpression('/class="nav-link"[^>]*id="account-tab-company-info"/', $content);
        $this->assertMatchesRegularExpression('/class="nav-link"[^>]*id="account-tab-company-agents"/', $content);
        $response->assertSee(__('mfw-accounts::ui.Agents'));
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
            'mfw_google_places' => $this->googleAddressPayload(),
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
        $this->assertStoredBillingAddress($clientId);
    }

    public function test_update_client_action_rejects_missing_email_with_ajax_message(): void
    {
        $this->assertMissingEmailValidationForAction('update_client');
    }

    public function test_update_client_info_action_rejects_missing_email_with_ajax_message(): void
    {
        $this->assertMissingEmailValidationForAction('update_client_info');
    }

    public function test_update_client_info_action_preserves_existing_email_when_it_is_omitted(): void
    {
        $this->actingAs($this->createSystemUser());
        $account = $this->createAccount();

        $response = $this->postJson(route('mfw-accounts.ajax'), [
            'action' => 'update_client_info',
            'object' => 'Account',
            'object_id' => $account->id,
            'first_name' => 'Edited',
            'last_name' => 'Client',
            'phone' => '+35970000005',
            'civ' => 'A',
            'locale' => 'fr',
        ]);

        $response->assertOk();
        $response->assertJsonPath('client_id', $account->id);
        $this->assertDatabaseHas('users', [
            'id' => $account->id,
            'email' => $account->email,
            'phone' => '+35970000005',
        ]);
    }

    public function test_update_client_info_action_creates_account_and_address_from_ajax_create_flow(): void
    {
        $this->actingAs($this->createSystemUser());

        $email = 'mfw-accounts-info-add-' . Str::uuid() . '@example.com';
        $response = $this->postJson(route('mfw-accounts.ajax'), [
            'action' => 'update_client_info',
            'object' => 'Account',
            'first_name' => 'Alice',
            'last_name' => 'Martin',
            'email' => $email,
            'phone' => '+35970000004',
            'civ' => 'A',
            'locale' => 'fr',
            'mfw_google_places' => $this->googleAddressPayload(),
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
            'phone' => '+35970000004',
            'locale' => 'fr',
        ]);
        $this->assertStoredBillingAddress($clientId);
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

    public function test_update_client_address_action_persists_full_google_places_payload(): void
    {
        $this->actingAs($this->createSystemUser());
        $account = $this->createAccount();

        $response = $this->postJson(route('mfw-accounts.ajax'), [
            'action' => 'update_client_address',
            'object_id' => $account->id,
            'mfw_google_places' => $this->googleAddressPayload(),
        ]);

        $response->assertOk();
        $response->assertJsonPath('client_id', $account->id);
        $response->assertJsonStructure([
            'mfw_ajax_messages' => [
                ['success'],
            ],
            'client_id',
        ]);

        $this->assertStoredBillingAddress($account->id);
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

    private function createCompanyAccount(): Account
    {
        $account = Account::query()->create([
            'type' => UserType::COMPANY->value,
            'email' => 'mfw-accounts-company-' . Str::uuid() . '@example.com',
            'password' => 'password',
            'first_name' => 'Partner',
            'last_name' => 'Company',
            'phone' => '+35970000003',
            'locale' => 'fr',
            'civ' => 'A',
        ]);

        $account->business()->create([
            'name' => 'Partner Company',
            'vat_number' => 'BG123456789',
            'reg_number' => 'REG-001',
        ]);

        return $account->refresh();
    }

    private function assertMissingEmailValidationForAction(string $action): void
    {
        $this->actingAs($this->createSystemUser());

        $clientCount = Account::query()->count();

        $response = $this->postJson(route('mfw-accounts.ajax'), [
            'action' => $action,
            'object' => 'Account',
            'first_name' => 'Mohamed',
            'last_name' => 'Zennar',
            'phone' => 'mzennar@hotmail.com',
            'civ' => 'A',
            'locale' => 'fr',
        ]);

        $response->assertOk();
        $response->assertJsonPath('error', true);
        $response->assertJsonPath('mfw_ajax_messages.0.danger', __('mfw-accounts::ui.client.email_required'));
        $response->assertJsonMissingPath('client_id');

        $this->assertSame($clientCount, Account::query()->count());
    }

    /**
     * @return array<string, string>
     */
    private function googleAddressPayload(): array
    {
        return [
            'street_number' => '12',
            'route' => 'Rue de Test',
            'locality' => 'Paris',
            'postal_code' => '75001',
            'country_code' => 'FR',
            'place_id' => 'test-place-id',
            'text_address' => '12 Rue de Test, 75001 Paris, France',
            'lat' => '48.8566',
            'lon' => '2.3522',
            'company' => 'Acme',
            'complementary' => 'Batiment A',
            'administrative_area_level_1' => 'Ile-de-France',
            'administrative_area_level_2' => 'Paris',
        ];
    }

    private function assertStoredBillingAddress(int $accountId): void
    {
        $this->assertDatabaseHas('mfw_accounts_account_address', [
            'user_id' => $accountId,
            'street_number' => '12',
            'postal_code' => '75001',
            'country_code' => 'FR',
            'place_id' => 'test-place-id',
            'text_address' => '12 Rue de Test, 75001 Paris, France',
            'company' => 'Acme',
            'complementary' => 'Batiment A',
            'billing' => 1,
        ]);
    }

    private function accountsPath(string $suffix = ''): string
    {
        $prefix = trim((string) config('mfw-accounts.route_prefix', 'mfw-accounts'), '/');
        $path = '/' . ($prefix !== '' ? $prefix : 'mfw-accounts');

        return $suffix !== '' ? $path . '/' . ltrim($suffix, '/') : $path;
    }
}
