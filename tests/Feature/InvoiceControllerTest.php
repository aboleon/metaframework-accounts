<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\View;
use MetaFramework\Accounts\Http\Controllers\InvoiceController;
use MetaFramework\Accounts\Models\Invoice;
use MetaFramework\Accounts\Tests\Feature\Concerns\InteractsWithAccountsControllerData;
use MetaFramework\Accounts\Tests\TestCase;

class InvoiceControllerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAccountsControllerData;

    public function test_index_returns_view_and_applies_paid_alias_filter(): void
    {
        $docType = $this->seedCashflowDocType([
            'id' => 5,
            'slug' => 'invoice',
            'name' => 'Invoice',
            'admin_name' => 'Invoice',
        ]);
        $client = $this->createAccount();
        $operator = $this->createSystemUser();

        $paidInvoice = $this->createInvoice([
            'account' => $client,
            'operator' => $operator,
            'doc_type' => $docType->id,
            'document_id' => 201,
            'paid' => 1,
            'amount' => 100,
            'vat' => 20,
        ]);
        $this->createInvoice([
            'account' => $client,
            'operator' => $operator,
            'doc_type' => $docType->id,
            'document_id' => 202,
            'paid' => null,
            'amount' => 200,
            'vat' => 40,
        ]);

        $request = Request::create('/mfw-accounts/invoices', 'GET', [
            'paid' => 'paid',
            'sort_by' => 'invoice_id',
            'sort_dir' => 'asc',
        ]);
        $this->app->instance('request', $request);

        $view = (new InvoiceController)->index();

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('mfw-accounts::invoices.index', $view->name());

        $data = $view->getData();
        $this->assertSame(1, $data['invoices']->total());
        $this->assertSame($paidInvoice->id, $data['invoices']->first()->id);
        $this->assertSame(1, $data['total']);
        $this->assertArrayHasKey($docType->id, $data['doc_types']);
    }

    public function test_store_creates_invoice_and_redirects_to_edit(): void
    {
        $this->actingAs($this->createSystemUser());
        $client = $this->createAccount();
        $docType = $this->seedCashflowDocType([
            'id' => 5,
            'slug' => 'invoice',
            'name' => 'Invoice',
            'admin_name' => 'Invoice',
        ]);

        // Work around current nextDocumentId() behavior that relies on an existing invoice row.
        $this->createInvoice([
            'account' => $client,
            'operator' => auth()->user(),
            'doc_type' => $docType->id,
            'document_id' => 1,
        ]);

        $response = $this->post(route('mfw-accounts.invoices.store'), [
            'account_id' => $client->id,
            'doc_type' => $docType->id,
            'pdf_locale' => 'bg',
        ]);

        $invoice = Invoice::query()->latest('id')->first();

        $this->assertNotNull($invoice);
        $response->assertRedirect(route('mfw-accounts.invoices.edit', $invoice));
        $response->assertSessionHas('session_message');
        $this->assertSame($client->id, (int) $invoice->account_id);
        $this->assertSame($docType->id, (int) $invoice->doc_type);
        $this->assertSame('bg', $invoice->pdf_locale);
    }

    public function test_store_validates_payload(): void
    {
        $this->actingAs($this->createSystemUser());

        $response = $this->from('/fake-invoices-create')
            ->post(route('mfw-accounts.invoices.store'), [
                'account_id' => 99999,
                'doc_type' => 99999,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['account_id', 'doc_type']);
    }

    public function test_edit_returns_view_with_invoice_context(): void
    {
        $docType = $this->seedCashflowDocType([
            'id' => 5,
            'slug' => 'invoice',
            'name' => 'Invoice',
            'admin_name' => 'Invoice',
        ]);
        $this->seedCurrency(['id' => 1, 'name' => 'Euro', 'code' => 'EUR', 'sign' => 'EUR']);

        $invoice = $this->createInvoice([
            'doc_type' => $docType->id,
            'document_id' => 210,
        ]);

        $view = (new InvoiceController)->edit($invoice);

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('mfw-accounts::invoices.edit', $view->name());

        $data = $view->getData();
        $this->assertSame($invoice->id, $data['data']->id);
        $this->assertSame($invoice->account_id, $data['client']->id);
        $this->assertArrayHasKey('accessor', $data);
        $this->assertArrayHasKey('expenseAccessor', $data);
        $this->assertArrayHasKey('bank_accounts', $data);
    }

    public function test_update_validates_payload(): void
    {
        $this->actingAs($this->createSystemUser());
        $docType = $this->seedCashflowDocType(['id' => 5, 'slug' => 'invoice']);
        $invoice = $this->createInvoice(['doc_type' => $docType->id]);

        $response = $this->from(route('mfw-accounts.invoices.edit', $invoice))
            ->put(route('mfw-accounts.invoices.update', $invoice), [
                'account_id' => 99999,
                'doc_type' => 99999,
                'invoice_date' => '',
                'currency' => 'abc',
                'amount' => ['not-a-number'],
                'vat' => ['not-a-number'],
                'quantity' => ['not-a-number'],
            ]);

        $response->assertRedirect(route('mfw-accounts.invoices.edit', $invoice));
        $response->assertSessionHasErrors(['account_id', 'doc_type', 'invoice_date', 'currency', 'amount.0', 'vat.0', 'quantity.0']);
    }

    public function test_update_rejects_multiline_quantity_lower_than_one(): void
    {
        $this->actingAs($this->createSystemUser());
        $docType = $this->seedCashflowDocType(['id' => 5, 'slug' => 'invoice']);
        $invoice = $this->createInvoice([
            'doc_type' => $docType->id,
            'document_id' => 255,
        ]);

        $response = $this->from(route('mfw-accounts.invoices.edit', $invoice))
            ->put(route('mfw-accounts.invoices.update', $invoice), $this->validUpdatePayload($invoice, [
                'amount' => [100, 5000],
                'vat' => [0, 0],
                'quantity' => [1, -1],
                'content' => ['Line A', 'Avans'],
            ]));

        $response->assertRedirect(route('mfw-accounts.invoices.edit', $invoice));
        $response->assertSessionHasErrors(['quantity.1']);
        $this->assertSame(0, $invoice->details()->count());
    }

    public function test_ajax_process_rejects_multiline_quantity_lower_than_one(): void
    {
        $this->actingAs($this->createSystemUser());
        $docType = $this->seedCashflowDocType(['id' => 5, 'slug' => 'invoice']);
        $invoice = $this->createInvoice([
            'doc_type' => $docType->id,
            'document_id' => 256,
        ]);

        $response = $this->postJson(route('mfw-accounts.ajax'), array_merge(
            $this->validUpdatePayload($invoice, [
                'amount' => [100, 5000],
                'vat' => [0, 0],
                'quantity' => [1, -1],
                'content' => ['Line A', 'Avans'],
            ]),
            [
                'object' => 'Invoices',
                'ajax_action' => 'process',
                'object_id' => $invoice->id,
                'id' => $invoice->id,
            ],
        ));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['quantity.1']);
        $this->assertSame(0, $invoice->details()->count());
    }

    public function test_update_updates_single_line_invoice_and_sets_date_paid(): void
    {
        $this->actingAs($this->createSystemUser());
        $docType = $this->seedCashflowDocType(['id' => 5, 'slug' => 'invoice']);
        $invoice = $this->createInvoice([
            'doc_type' => $docType->id,
            'document_id' => 250,
            'paid' => null,
        ]);

        $response = $this->put(route('mfw-accounts.invoices.update', $invoice), $this->validUpdatePayload($invoice, [
            'title' => 'Updated title',
            'paid' => 'date_paid',
            'amount' => [150],
            'vat' => [30],
            'content' => ['Consulting'],
            'quantity' => [1],
        ]));

        $response->assertRedirect(route('mfw-accounts.invoices.edit', $invoice));
        $response->assertSessionHas('session_message');

        $invoice->refresh();
        $this->assertSame('Updated title', $invoice->title);
        $this->assertSame(150.0, (float) $invoice->amount);
        $this->assertSame(30.0, (float) $invoice->vat);
        $this->assertSame(1, (int) $invoice->paid);
        $this->assertNotNull($invoice->date_paid);
    }

    public function test_update_creates_multiline_details_and_aggregates_totals(): void
    {
        $this->actingAs($this->createSystemUser());
        $docType = $this->seedCashflowDocType(['id' => 5, 'slug' => 'invoice']);
        $invoice = $this->createInvoice([
            'doc_type' => $docType->id,
            'document_id' => 260,
        ]);

        $response = $this->put(route('mfw-accounts.invoices.update', $invoice), $this->validUpdatePayload($invoice, [
            'amount' => [100, 50],
            'vat' => [20, 10],
            'quantity' => [1, 2],
            'content' => ['Line A', 'Line B'],
        ]));

        $response->assertRedirect(route('mfw-accounts.invoices.edit', $invoice));

        $invoice->refresh();
        $this->assertSame(150.0, (float) $invoice->amount);
        $this->assertSame(30.0, (float) $invoice->vat);
        $this->assertSame(2, $invoice->details()->count());
    }

    public function test_update_syncs_expense_associations_for_paid_invoice(): void
    {
        $this->actingAs($this->createSystemUser());
        $docType = $this->seedCashflowDocType(['id' => 5, 'slug' => 'invoice']);
        $invoice = $this->createInvoice([
            'doc_type' => $docType->id,
            'document_id' => 270,
            'paid' => null,
        ]);
        $expenseInvoice = $this->createInvoice([
            'doc_type' => $docType->id,
            'document_id' => 271,
            'paid' => 1,
        ]);

        $response = $this->put(route('mfw-accounts.invoices.update', $invoice), $this->validUpdatePayload($invoice, [
            'paid' => 'date_paid',
            'expense_associations' => [$expenseInvoice->id],
            'amount' => [100],
            'vat' => [20],
        ]));

        $response->assertRedirect(route('mfw-accounts.invoices.edit', $invoice));

        $this->assertDatabaseHas('mfw_accounts_invoice_expense_associations', [
            'parent_invoice_id' => $invoice->id,
            'associated_invoice_id' => $expenseInvoice->id,
        ]);
    }

    public function test_duplicate_creates_duplicata_invoice(): void
    {
        $this->actingAs($this->createSystemUser());
        $this->seedCashflowDocType(['id' => 5, 'slug' => 'invoice', 'name' => 'Invoice']);
        $duplicataType = $this->seedCashflowDocType(['slug' => 'duplicata', 'name' => 'Duplicata']);
        $invoice = $this->createInvoice([
            'doc_type' => 5,
            'document_id' => 300,
            'hash' => str_repeat('a', 40),
        ]);

        $response = $this->post(route('mfw-accounts.invoices.duplicate', $invoice));

        $duplicate = Invoice::query()->where('duplicata', 1)->latest('id')->first();

        $this->assertNotNull($duplicate);
        $response->assertRedirect(route('mfw-accounts.invoices.edit', $duplicate));
        $this->assertSame($invoice->document_id, (int) $duplicate->document_id);
        $this->assertSame($invoice->account_id, (int) $duplicate->account_id);
        $this->assertSame($duplicataType->id, (int) $duplicate->doc_type);
        $this->assertNotSame($invoice->hash, $duplicate->hash);
    }

    public function test_mail_preview_returns_view_and_sets_invoice_locale(): void
    {
        $this->seedCurrency(['id' => 1, 'name' => 'Euro', 'code' => 'EUR', 'sign' => 'EUR']);

        $invoice = $this->createInvoice([
            'document_id' => 400,
            'hash' => str_repeat('b', 40),
            'pdf_locale' => 'bg',
        ]);

        app()->setLocale('fr');

        $view = (new InvoiceController)->mailPreview($invoice->hash);

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('mfw-accounts::mails.invoice', $view->name());
        $this->assertSame('bg', app()->getLocale());
        $data = $view->getData();
        $this->assertSame($invoice->id, $data['invoice']->id);
        $this->assertSame('bg', $data['locale']);
        $this->assertNotEmpty($data['pdf_url']);
    }

    public function test_mail_preview_renders_single_line_total_using_quantity_without_redividing_casted_prices(): void
    {
        $this->seedCurrency(['id' => 1, 'name' => 'Euro', 'code' => 'EUR', 'sign' => 'EUR']);
        Blade::anonymousComponentPath(__DIR__ . '/../stubs/views/components');

        $invoice = $this->createInvoice([
            'document_id' => 401,
            'hash' => str_repeat('c', 40),
            'pdf_locale' => 'fr',
            'amount' => 311,
            'quantity' => 2,
            'vat' => 0,
        ]);

        $view = (new InvoiceController)->mailPreview($invoice->hash);
        $html = $view->render();

        $this->assertStringContainsString('622 EUR', $html);
        $this->assertStringNotContainsString('3,11 EUR', $html);
    }

    private function validUpdatePayload(Invoice $invoice, array $overrides = []): array
    {
        $payload = [
            'account_id' => $invoice->account_id,
            'doc_type' => $invoice->doc_type,
            'document_id' => $invoice->document_id,
            'invoice_date' => '23/02/2026',
            'currency' => (int) $invoice->currency,
            'title' => $invoice->title,
            'notes' => 'Updated notes',
            'pdf_locale' => 'fr',
            'paid' => null,
            'amount' => [100],
            'vat' => [20],
            'vat_id' => [null],
            'quantity' => [1],
            'content' => ['Line'],
        ];

        return array_replace($payload, $overrides);
    }
}
