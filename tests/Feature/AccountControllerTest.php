<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MetaFramework\Accounts\Models\Account;
use MetaFramework\Accounts\Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_store_creates_client_and_redirects_to_edit(): void
    {
        $this->actingAs($this->createSystemUser());

        $email = 'account-controller-store-' . Str::uuid() . '@example.com';

        $response = $this->post(route('mfw-accounts.clients.store'), [
            'first_name' => 'Alice',
            'last_name' => 'Martin',
            'email' => $email,
            'phone' => '+35970010001',
            'civ' => 'A',
            'locale' => 'fr',
        ]);

        $client = Account::query()->where('email', $email)->first();

        $this->assertNotNull($client);
        $response->assertRedirect(route('mfw-accounts.clients.edit', $client));
        $response->assertSessionHas('session_message');

        $this->assertDatabaseHas('users', [
            'id' => $client->id,
            'email' => $email,
            'phone' => '+35970010001',
            'civ' => 'A',
            'locale' => 'fr',
        ]);

        $this->assertNotEmpty($client->password);
    }

    public function test_store_validates_payload_with_form_request(): void
    {
        $this->actingAs($this->createSystemUser());

        $response = $this->from(route('mfw-accounts.clients.create'))->post(route('mfw-accounts.clients.store'), [
            'first_name' => 'Alice',
            'email' => 'not-an-email',
            'locale' => 'toolong',
        ]);

        $response->assertRedirect(route('mfw-accounts.clients.create'));
        $response->assertSessionHasErrors(['email', 'locale']);
    }

    public function test_update_updates_existing_client_and_redirects_to_edit(): void
    {
        $this->actingAs($this->createSystemUser());

        $client = $this->createAccount();
        $email = 'account-controller-update-' . Str::uuid() . '@example.com';

        $response = $this->put(route('mfw-accounts.clients.update', $client), [
            'first_name' => 'Edited',
            'last_name' => 'Client',
            'email' => $email,
            'phone' => '+35970010002',
            'civ' => 'B',
            'locale' => 'bg',
        ]);

        $response->assertRedirect(route('mfw-accounts.clients.edit', $client));
        $response->assertSessionHas('session_message');

        $this->assertDatabaseHas('users', [
            'id' => $client->id,
            'email' => $email,
            'phone' => '+35970010002',
            'civ' => 'B',
            'locale' => 'bg',
        ]);
    }

    public function test_update_validates_payload_with_form_request(): void
    {
        $this->actingAs($this->createSystemUser());

        $client = $this->createAccount();

        $response = $this->from(route('mfw-accounts.clients.edit', $client))->put(route('mfw-accounts.clients.update', $client), [
            'email' => 'invalid-email',
            'locale' => 'toolong',
        ]);

        $response->assertRedirect(route('mfw-accounts.clients.edit', $client));
        $response->assertSessionHasErrors(['email', 'locale']);
    }

    public function test_index_uses_configured_account_model_for_offer_counts(): void
    {
        $this->actingAs($this->createSystemUser());

        config()->set('mfw-accounts.models.account', \App\Models\Account::class);
        $client = \App\Models\Account::query()->create([
            'email' => 'mfw-accounts-offers-' . Str::uuid() . '@example.com',
            'password' => 'password',
            'first_name' => 'Offer',
            'last_name' => 'Client',
            'phone' => '+35970000003',
            'locale' => 'fr',
            'civ' => 'A',
        ]);

        $offerOneId = DB::table('seller_offers')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $offerTwoId = DB::table('seller_offers')->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('seller_offer_accounts')->insert([
            [
                'offer_id' => $offerOneId,
                'account_id' => $client->id,
            ],
            [
                'offer_id' => $offerTwoId,
                'account_id' => $client->id,
            ],
        ]);

        $response = $this->get(route('mfw-accounts.clients.index'));

        $response->assertOk();
        $response->assertViewHas('clients', function ($clients) use ($client): bool {
            return $clients->getCollection()->firstWhere('id', $client->id)?->offers_count === 2;
        });
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
