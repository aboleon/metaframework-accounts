<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\View\View;
use MetaFramework\Accounts\Http\Controllers\PayMeanController;
use MetaFramework\Accounts\Models\PayMeansChannels;
use MetaFramework\Accounts\Tests\Feature\Concerns\InteractsWithAccountsControllerData;
use MetaFramework\Accounts\Tests\TestCase;

class PayMeanControllerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAccountsControllerData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableMultilangForTests();
    }

    public function test_index_create_and_edit_return_expected_views(): void
    {
        $payMean = $this->seedPayMean([
            'name' => ['fr' => 'Virement', 'bg' => 'Bank transfer'],
            'type' => 'bank',
        ]);
        PayMeansChannels::query()->create(['pay_mean_id' => $payMean->id]);

        $controller = new PayMeanController;

        $index = $controller->index();
        $this->assertInstanceOf(View::class, $index);
        $this->assertSame('mfw-accounts::paymeans.index', $index->name());
        $indexData = $index->getData()['data'];
        $this->assertSame(1, $indexData->count());
        $this->assertSame(1, (int) $indexData->first()->channels_count);

        $create = $controller->create();
        $this->assertSame('mfw-accounts::paymeans.edit', $create->name());
        $this->assertSame(route('mfw-accounts.pay-means.store'), $create->getData()['route']);

        $edit = $controller->edit($payMean);
        $this->assertSame('mfw-accounts::paymeans.edit', $edit->name());
        $this->assertSame(route('mfw-accounts.pay-means.update', $payMean), $edit->getData()['route']);
    }

    public function test_store_creates_pay_mean(): void
    {
        $this->actingAs($this->createSystemUser());

        $response = $this->post(route('mfw-accounts.pay-means.store'), [
            'name' => [
                'fr' => 'Carte',
                'bg' => 'Card',
            ],
            'type' => 'card',
        ]);

        $response->assertRedirect(route('mfw-accounts.pay-means.index'));
        $response->assertSessionHas('session_response');

        $payMean = \MetaFramework\Accounts\Models\PayMeans::query()->latest('id')->first();

        $this->assertNotNull($payMean);
        $this->assertSame('card', $payMean->type);
        $this->assertSame('Carte', $payMean->translation('name', 'fr'));
    }

    public function test_store_validates_payload(): void
    {
        $this->actingAs($this->createSystemUser());

        $response = $this->from(route('mfw-accounts.pay-means.create'))
            ->post(route('mfw-accounts.pay-means.store'), [
                'name' => 'invalid',
            ]);

        $response->assertRedirect(route('mfw-accounts.pay-means.create'));
        $response->assertSessionHasErrors(['name']);
    }

    public function test_update_updates_pay_mean(): void
    {
        $this->actingAs($this->createSystemUser());
        $payMean = $this->seedPayMean([
            'name' => ['fr' => 'Old'],
            'type' => 'bank',
        ]);

        $response = $this->put(route('mfw-accounts.pay-means.update', $payMean), [
            'name' => [
                'fr' => 'Espèces',
                'bg' => 'Cash',
            ],
            'type' => 'cash',
        ]);

        $response->assertRedirect(route('mfw-accounts.pay-means.index'));
        $response->assertSessionHas('session_response');

        $payMean->refresh();
        $this->assertSame('cash', $payMean->type);
        $this->assertSame('Espèces', $payMean->translation('name', 'fr'));
    }

    public function test_destroy_deletes_pay_mean_without_associated_invoices(): void
    {
        $this->actingAs($this->createSystemUser());
        $payMean = $this->seedPayMean();

        $response = $this->delete(route('mfw-accounts.pay-means.destroy', $payMean));

        $response->assertRedirect(route('mfw-accounts.pay-means.index'));
        $response->assertSessionHas('session_response');
        $this->assertDatabaseMissing('mfw_accounts_pay_means', ['id' => $payMean->id]);
    }

    public function test_destroy_rejects_pay_mean_with_associated_invoices(): void
    {
        $this->actingAs($this->createSystemUser());
        $payMean = $this->seedPayMean();
        $this->createInvoice([
            'pay_mean' => $payMean->id,
            'document_id' => 30,
        ]);

        $response = $this->from(route('mfw-accounts.pay-means.index'))
            ->delete(route('mfw-accounts.pay-means.destroy', $payMean));

        $response->assertRedirect(route('mfw-accounts.pay-means.index'));
        $response->assertSessionHas('session_response');
        $this->assertDatabaseHas('mfw_accounts_pay_means', ['id' => $payMean->id]);
    }
}
