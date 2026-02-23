<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\View\View;
use MetaFramework\Accounts\Http\Controllers\VatController;
use MetaFramework\Accounts\Models\Vat;
use MetaFramework\Accounts\Tests\Feature\Concerns\InteractsWithAccountsControllerData;
use MetaFramework\Accounts\Tests\TestCase;

class VatControllerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAccountsControllerData;

    public function test_index_create_and_edit_return_expected_views(): void
    {
        $vat = Vat::query()->create([
            'rate' => 20,
            'default' => 1,
        ]);

        $controller = new VatController;

        $index = $controller->index();
        $this->assertInstanceOf(View::class, $index);
        $this->assertSame('mfw-accounts::vat.index', $index->name());
        $this->assertSame(1, $index->getData()['data']->count());

        $create = $controller->create();
        $this->assertSame('mfw-accounts::vat.edit', $create->name());
        $this->assertSame(route('mfw-accounts.vat.store'), $create->getData()['route']);

        $edit = $controller->edit($vat);
        $this->assertSame('mfw-accounts::vat.edit', $edit->name());
        $this->assertSame(route('mfw-accounts.vat.update', $vat), $edit->getData()['route']);
    }

    public function test_store_creates_vat_and_redirects(): void
    {
        $this->actingAs($this->createSystemUser());

        $response = $this->post(route('mfw-accounts.vat.store'), [
            'vat' => [
                'rate' => 20,
                'default' => 1,
            ],
        ]);

        $response->assertRedirect(route('mfw-accounts.vat.index'));
        $response->assertSessionHas('session_response');

        $vat = Vat::query()->latest('id')->first();

        $this->assertNotNull($vat);
        $this->assertSame(20.0, (float) $vat->rate);
        $this->assertSame(1, (int) $vat->default);
    }

    public function test_store_validates_payload(): void
    {
        $this->actingAs($this->createSystemUser());

        $response = $this->from(route('mfw-accounts.vat.create'))
            ->post(route('mfw-accounts.vat.store'), [
                'vat' => [
                    'rate' => 'not-numeric',
                ],
            ]);

        $response->assertRedirect(route('mfw-accounts.vat.create'));
        $response->assertSessionHasErrors(['vat.rate']);
    }

    public function test_update_updates_vat(): void
    {
        $this->actingAs($this->createSystemUser());
        $vat = Vat::query()->create([
            'rate' => 20,
            'default' => null,
        ]);

        $response = $this->put(route('mfw-accounts.vat.update', $vat), [
            'vat' => [
                'rate' => 21,
                'default' => 1,
            ],
        ]);

        $response->assertRedirect(route('mfw-accounts.vat.index'));
        $response->assertSessionHas('session_response');

        $vat->refresh();
        $this->assertSame(21.0, (float) $vat->rate);
        $this->assertSame(1, (int) $vat->default);
    }

    public function test_update_validates_payload(): void
    {
        $this->actingAs($this->createSystemUser());
        $vat = Vat::query()->create(['rate' => 20]);

        $response = $this->from(route('mfw-accounts.vat.edit', $vat))
            ->put(route('mfw-accounts.vat.update', $vat), [
                'vat' => [
                    'rate' => 'invalid',
                ],
            ]);

        $response->assertRedirect(route('mfw-accounts.vat.edit', $vat));
        $response->assertSessionHasErrors(['vat.rate']);
    }

    public function test_destroy_deletes_vat_and_handles_default_branch(): void
    {
        $this->actingAs($this->createSystemUser());
        $defaultVat = Vat::query()->create(['rate' => 20, 'default' => 1]);
        $fallbackVat = Vat::query()->create(['rate' => 10, 'default' => 1]);

        $response = $this->from(route('mfw-accounts.vat.index'))
            ->delete(route('mfw-accounts.vat.destroy', $defaultVat));

        $response->assertRedirect(route('mfw-accounts.vat.index'));
        $this->assertDatabaseMissing('mfw_accounts_vat', ['id' => $defaultVat->id]);
        $fallbackVat->refresh();
        $this->assertNull($fallbackVat->default);
    }
}
