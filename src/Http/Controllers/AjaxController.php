<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MetaFramework\Support\Traits\Ajax;
use MetaFramework\Accounts\Actions\AccountActions;
use MetaFramework\Accounts\Actions\InvoiceActions;
use MetaFramework\Accounts\Models\Account;
use MetaFramework\Accounts\Models\Invoice;

class AjaxController
{
    use Ajax;

    public function __invoke(Request $request): array|JsonResponse
    {
        // Normalize legacy ajax_action to the MetaFramework "action" key.
        if ($request->filled('ajax_action') && !$request->filled('action')) {
            $request->merge(['action' => $request->ajax_action]);
        }

        return $this->distribute($request);
    }

    public function add(Request $request): array
    {
        $response = (new InvoiceActions)->add($request);
        $this->logAjaxActivity(Invoice::class, (int) ($response['callback_id'] ?? 0), __FUNCTION__);

        return $response;
    }

    public function process(): array
    {
        $invoiceId = (int) (request('object_id') ?? request('id') ?? 0);
        $response = (new InvoiceActions)->process();
        $this->logAjaxActivity(Invoice::class, $invoiceId, __FUNCTION__);

        return $response;
    }

    public function sendInvoiceFromModal(): array
    {
        $hash = trim((string) request('hash'));
        $invoiceId = $hash !== ''
            ? (int) Invoice::query()->where('hash', $hash)->value('id')
            : 0;
        $response = new InvoiceActions()->sendByMail();
        $this->logAjaxActivity(Invoice::class, $invoiceId, __FUNCTION__);

        return $response;
    }

    public function validateAccountEmail(Request $request): array
    {
        $hash = trim((string) request('hash'));
        $invoiceId = $hash !== ''
            ? (int) Invoice::query()->where('hash', $hash)->value('id')
            : 0;
        $response = (new InvoiceActions)->validateAccountEmail($request);
        $this->logAjaxActivity(Invoice::class, $invoiceId, __FUNCTION__);

        return $response;
    }

    public function findAccountByKeywords(Request $request): array
    {
        $response = new AccountActions()->ajaxMode()->findAccountByKeywords($request);
        $this->logAjaxActivity(Account::class, 0, __FUNCTION__);

        return $response;
    }

    public function update_client(): array
    {
        $response = new AccountActions()
            ->ajaxMode()
            ->updateClientData()
            ->fetchResponse();
        $accountId = (int) ($response['client_id'] ?? request('object_id') ?? 0);
        $this->logAjaxActivity(Account::class, $accountId, __FUNCTION__);

        return $response;
    }

    public function update_address_translations(): array
    {
        $response = new AccountActions()
            ->ajaxMode()
            ->updateAddressTranslations()
            ->fetchResponse();
        $this->logAjaxActivity(Account::class, (int) request('object_id'), __FUNCTION__);

        return $response;
    }

    public function update_expenses(): array
    {
        $invoiceId = (int) request('invoice_id');
        $response = (new InvoiceActions)->updateExpenses();
        $this->logAjaxActivity(Invoice::class, $invoiceId, __FUNCTION__);

        return $response;
    }

    public function export_invoices(): JsonResponse
    {
        return (new InvoiceActions)->export();
    }

    private function logAjaxActivity(string $modelClass, int $modelId, string $method): void
    {
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'user_id' => auth()->id(),
                'model' => $modelClass,
                'model_id' => $modelId,
                'method' => $method,
                'timestamp' => now()->toDateTimeString(),
            ])
            ->log($method);
    }
}
