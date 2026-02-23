<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\View\View;
use MetaFramework\Accounts\Http\Controllers\CashflowDocTypeController;
use MetaFramework\Accounts\Tests\Feature\Concerns\InteractsWithAccountsControllerData;
use MetaFramework\Accounts\Tests\TestCase;

class CashflowDocTypeControllerTest extends TestCase
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
        $docType = $this->seedCashflowDocType([
            'slug' => 'invoice',
            'name' => ['fr' => 'Facture', 'bg' => 'Faktura'],
            'admin_name' => ['fr' => 'Invoice', 'bg' => 'Invoice BG'],
        ]);
        $this->createInvoice([
            'doc_type' => $docType->id,
            'document_id' => 10,
        ]);

        $controller = new CashflowDocTypeController;

        $index = $controller->index();
        $this->assertInstanceOf(View::class, $index);
        $this->assertSame('mfw-accounts::cashflow.doctypes.index', $index->name());
        $indexData = $index->getData()['data'];
        $this->assertSame(1, $indexData->count());
        $this->assertSame(1, (int) $indexData->first()->invoices_count);

        $create = $controller->create();
        $this->assertSame('mfw-accounts::cashflow.doctypes.edit', $create->name());
        $this->assertSame(route('mfw-accounts.cashflow-doctypes.store'), $create->getData()['route']);

        $edit = $controller->edit($docType);
        $this->assertSame('mfw-accounts::cashflow.doctypes.edit', $edit->name());
        $this->assertSame(route('mfw-accounts.cashflow-doctypes.update', $docType), $edit->getData()['route']);
    }

    public function test_store_creates_doc_type_and_generates_slug(): void
    {
        $this->actingAs($this->createSystemUser());

        $response = $this->post(route('mfw-accounts.cashflow-doctypes.store'), [
            'name' => [
                'fr' => 'Facture Client',
                'bg' => 'Faktura',
            ],
            'admin_name' => [
                'fr' => 'Facture Client',
            ],
            'numerotation' => 'generic',
            'default' => 1,
        ]);

        $response->assertRedirect(route('mfw-accounts.cashflow-doctypes.index'));
        $response->assertSessionHas('session_response');

        $docType = \MetaFramework\Accounts\Models\CashflowDocTypes::query()->latest('id')->first();

        $this->assertNotNull($docType);
        $this->assertSame('facture-client', $docType->slug);
        $this->assertSame('Facture Client', $docType->translation('name', 'fr'));
        $this->assertSame('generic', $docType->numerotation);
    }

    public function test_store_validates_payload(): void
    {
        $this->actingAs($this->createSystemUser());

        $response = $this->from(route('mfw-accounts.cashflow-doctypes.create'))
            ->post(route('mfw-accounts.cashflow-doctypes.store'), [
                'name' => 'not-an-array',
                'numerotation' => 'invalid',
            ]);

        $response->assertRedirect(route('mfw-accounts.cashflow-doctypes.create'));
        $response->assertSessionHasErrors(['name', 'numerotation']);
    }

    public function test_update_updates_doc_type_and_resolves_slug_collision(): void
    {
        $this->actingAs($this->createSystemUser());
        $this->seedCashflowDocType([
            'slug' => 'services',
            'name' => ['fr' => 'Services'],
            'admin_name' => ['fr' => 'Services'],
        ]);
        $target = $this->seedCashflowDocType([
            'slug' => 'other',
            'name' => ['fr' => 'Autre'],
            'admin_name' => ['fr' => 'Autre'],
        ]);

        $response = $this->put(route('mfw-accounts.cashflow-doctypes.update', $target), [
            'name' => [
                'fr' => 'Services',
                'bg' => 'Services BG',
            ],
            'admin_name' => [
                'fr' => 'Services',
            ],
            'numerotation' => 'own',
        ]);

        $response->assertRedirect(route('mfw-accounts.cashflow-doctypes.index'));
        $response->assertSessionHas('session_response');

        $target->refresh();
        $this->assertSame('services-2', $target->slug);
        $this->assertSame('Services', $target->translation('name', 'fr'));
        $this->assertSame('own', $target->numerotation);
    }

    public function test_destroy_deletes_doc_type_without_associated_invoices(): void
    {
        $this->actingAs($this->createSystemUser());
        $docType = $this->seedCashflowDocType();

        $response = $this->delete(route('mfw-accounts.cashflow-doctypes.destroy', $docType));

        $response->assertRedirect(route('mfw-accounts.cashflow-doctypes.index'));
        $response->assertSessionHas('session_response');
        $this->assertDatabaseMissing('mfw_accounts_cashflow_doc_types', ['id' => $docType->id]);
    }

    public function test_destroy_rejects_doc_type_with_associated_invoices(): void
    {
        $this->actingAs($this->createSystemUser());
        $docType = $this->seedCashflowDocType();
        $this->createInvoice([
            'doc_type' => $docType->id,
            'document_id' => 22,
        ]);

        $response = $this->from(route('mfw-accounts.cashflow-doctypes.index'))
            ->delete(route('mfw-accounts.cashflow-doctypes.destroy', $docType));

        $response->assertRedirect(route('mfw-accounts.cashflow-doctypes.index'));
        $response->assertSessionHas('session_response');
        $this->assertDatabaseHas('mfw_accounts_cashflow_doc_types', ['id' => $docType->id]);
    }
}
