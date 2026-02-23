<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use MetaFramework\Mailer\Http\Controllers\MailController;
use MetaFramework\Polyglote\Traits\CyrillicContentTrait;
use MetaFramework\Support\Traits\Responses;
use MetaFramework\Traits\NumericInputNormalizer;
use MetaFramework\Accounts\Accessors\InvoiceExpenseAccessor;
use MetaFramework\Accounts\Exports\InvoicesIndexExport;
use MetaFramework\Accounts\Http\Controllers\InvoiceController;
use MetaFramework\Accounts\Mailer\Invoice as InvoiceMailer;
use MetaFramework\Accounts\Models\Invoice;
use MetaFramework\Accounts\Validators\AccountMailValidator;
use Throwable;

class InvoiceActions
{
    use CyrillicContentTrait;
    use NumericInputNormalizer;
    use Responses;

    public function add(Request $request): array
    {
        $this->enableAjaxMode();

        $invoice = new InvoiceController()->createFromRequest($request);

        $this->responseSuccess(__('mfw-accounts::ui.document_is_saved'));
        $this->response['callback']    = 'invoice.update';
        $this->response['callback_id'] = $invoice->id;
        $this->response['redirect']    = $request->has('saveandinsert');

        return $this->fetchResponse();
    }

    public function process(): array
    {
        $this->enableAjaxMode();

        $controller = new InvoiceController;
        if (request('paid') === 'date_paid' && !request()->filled('date_paid')) {
            request()->merge(['date_paid' => now()]);
        }
        $validated = $controller->validateInvoice();
        $invoiceId = (int) (request('object_id') ?? request('id'));
        $invoice   = Invoice::findOrFail($invoiceId);

        DB::transaction(function () use ($controller, $invoice, $validated) {
            $controller->persistInvoice($invoice, $validated);
        });

        $this->responseSuccess(__('mfw-accounts::ui.document_is_saved'));
        $this->response['callback']    = 'invoice.update';
        $this->response['callback_id'] = $invoice->id;
        $this->response['redirect']    = request()->has('saveandinsert');
        $this->responseElement('can_edit_expenses', $invoice->canEditExpenses());
        $this->responseElement('is_paid', !is_null($invoice->paid) || !is_null($invoice->date_paid));
        $this->responseElement('date_paid', $invoice->date_paid);

        $expenseAccessor = new InvoiceExpenseAccessor($invoice);
        $hasSummary      = $expenseAccessor->hasExpenseData();
        $summaryHtml     = $hasSummary
            ? view('mfw-accounts::components.expenses-summary', [
                'invoice'         => $invoice,
                'expenseAccessor' => $expenseAccessor,
            ])->render()
            : '';
        $this->responseElement('has_summary', $hasSummary);
        $this->responseElement('summary', $summaryHtml);

        return $this->fetchResponse();
    }

    public function sendByMail(): array
    {
        return new MailController()
            ->ajaxMode()
            ->distribute(InvoiceMailer::class, (string) request('hash'))
            ->fetchResponse();
    }

    public function validateAccountEmail(Request $request): array
    {
        $this->enableAjaxMode();

        $hash = trim((string) request('hash'));
        if ($hash === '') {
            $this->responseError(__('mfw-accounts::mailer/invoice.not_found'));

            return $this->fetchResponse();
        }

        $invoice = Invoice::query()
            ->with(['client', 'details'])
            ->where('hash', $hash)
            ->first();

        if (!$invoice || !$invoice->client) {
            $this->responseError(__('mfw-accounts::mailer/invoice.not_found'));

            return $this->fetchResponse();
        }

        $validator        = new AccountMailValidator;
        $result           = $validator->validate($invoice->client);
        $locale           = $invoice->pdf_locale ?? app()->getLocale();
        $hasCyrillic      = $this->invoiceHasCyrillicContent($invoice, $locale);
        $isCyrillicLocale = $this->isCyrillicLocale($locale);
        $languageLabel    = $this->formatLanguageLabel($this->resolveLanguageLabel($locale));

        $this->responseElement('data', [
            'email'              => $result['email'],
            'is_valid'           => $result['is_valid'],
            'is_fake'            => $result['is_fake'],
            'locale'             => $locale,
            'has_cyrillic'       => $hasCyrillic,
            'is_cyrillic_locale' => $isCyrillicLocale,
            'edit_url'           => route('mfw-accounts.clients.edit', $invoice->client->id),
            'labels'             => [
                'email'               => __('mfw-auth.email'),
                'valid_email'         => __('mfw-accounts::ui.ClientValidEmail'),
                'invalid_email'       => __('mfw-accounts::ui.ClientInvalidEmail'),
                'invalid_email_short' => __('mfw-accounts::ui.ClientInvalidEmailShort'),
                'edit'                => __('mfw-accounts::ui.edit'),
                'cyrillic_warning'    => __('mfw-accounts::mailer/invoice.cyrillic_warning', ['language' => $languageLabel]),
            ],
        ]);

        return $this->fetchResponse();
    }

    private function invoiceHasCyrillicContent(Invoice $invoice, string $locale): bool
    {
        $currentLocale = app()->getLocale();
        app()->setLocale($locale);

        $client = $invoice->client;
        $chunks = [
            $client?->first_name,
            $client?->last_name,
            $invoice->title,
            $invoice->content,
        ];

        if ($invoice->details) {
            foreach ($invoice->details as $detail) {
                $chunks[] = $detail->content ?? null;
            }
        }

        $text = collect($chunks)
            ->filter(fn ($chunk) => $chunk !== null && trim((string) $chunk) !== '')
            ->implode(' ');

        app()->setLocale($currentLocale);

        return $text !== '' && $this->hasCyrillic($text);
    }

    private function resolveLanguageLabel(string $locale): string
    {
        $raw = Lang::get('lang.' . $locale);

        if (is_array($raw)) {
            if (isset($raw['label']) && is_string($raw['label'])) {
                return $raw['label'];
            }

            $first = reset($raw);
            if (is_string($first)) {
                return $first;
            }
        }

        if (is_string($raw) && $raw !== 'lang.' . $locale) {
            return $raw;
        }

        return $locale;
    }

    private function formatLanguageLabel(string $label): string
    {
        $lower = function_exists('mb_strtolower')
            ? mb_strtolower($label)
            : strtolower($label);

        return '<strong>' . $lower . '</strong>';
    }

    public function updateExpenses(): array
    {
        $this->enableAjaxMode();

        $invoiceId = (int) request('invoice_id');
        $invoice   = Invoice::findOrFail($invoiceId);

        if (!$invoice->paid || $invoice->duplicata || $invoice->isAssociatedToExpense()) {
            $this->responseError(__('mfw-accounts::ui.expenses.cannot_edit'));

            return $this->fetchResponse();
        }
        $expenses   = $this->normalizeNumericValue(request('expenses'));

        $protocolRef           = trim((string) request('expense_protocol_ref'));
        $protocolRef           = $protocolRef !== '' ? $protocolRef : null;
        $expenseAssociations   = request('expense_associations', []);
        $previousAssociatedIds = $invoice
            ->expenseAssociatedInvoices()
            ->pluck($invoice->getTable() . '.id')
            ->toArray();

        // Save data
        DB::beginTransaction();

        try {
            $invoice->expenses             = $expenses;
            $invoice->expense_protocol_ref = $protocolRef;
            $invoice->save();

            $invoice->expenseAssociatedInvoices()->sync($expenseAssociations);

            $invoice->load(['expenseAssociatedInvoices', 'currencyType']);
            $expenseAccessor = new InvoiceExpenseAccessor($invoice);

            if (request()->has('no_expenses') || floatval($expenses) > 0) {

                $invoice->net_gain         = $expenseAccessor->calculateNetProfit();
                $totalIncome               = $expenseAccessor->calculateIncome();
                $invoice->net_gain_percent = !$totalIncome > 0
                    ? 0
                    : round(($invoice->net_gain / $totalIncome) * 100, 2);
            } else {
                $invoice->net_gain         = 0;
                $invoice->net_gain_percent = 0;
            }
            $invoice->save();

            if (!empty($expenseAssociations)) {
                Invoice::query()->whereIn('id', $expenseAssociations)->update([
                    'net_gain'         => 0,
                    'net_gain_percent' => 0,
                ]);
            }

            $removedAssociations = array_values(array_diff($previousAssociatedIds, $expenseAssociations));
            if (!empty($removedAssociations)) {
                $removedInvoices = Invoice::query()
                    ->with(['expenseAssociatedInvoices', 'currencyType'])
                    ->whereIn('id', $removedAssociations)
                    ->get();

                foreach ($removedInvoices as $removedInvoice) {
                    $removedAccessor                  = new InvoiceExpenseAccessor($removedInvoice);
                    $removedInvoice->net_gain         = $removedAccessor->calculateNetProfit();
                    $removedTotal                     = $removedAccessor->calculateIncome();
                    $removedInvoice->net_gain_percent = $removedTotal == 0.0
                        ? null
                        : round(($removedInvoice->net_gain / $removedTotal) * 100, 2);
                    $removedInvoice->save();
                }
            }
            DB::commit();
        } catch (Throwable $exception) {
            $this->responseException($exception);
            DB::rollBack();
        }

        $invoice->load(['expenseAssociatedInvoices', 'currencyType']);
        $expenseAccessor = new InvoiceExpenseAccessor($invoice);
        $hasSummary      = $expenseAccessor->hasExpenseData();
        $summaryHtml     = $hasSummary
            ? view('mfw-accounts::components.expenses-summary', [
                'invoice'         => $invoice,
                'expenseAccessor' => $expenseAccessor,
            ])->render()
            : '';

        $this->responseSuccess(__('mfw-accounts::ui.expenses.saved'));
        $this->responseElement('callback', 'expenses.update');
        $this->responseElement('has_summary', $hasSummary);
        $this->responseElement('summary', $summaryHtml);

        return $this->fetchResponse();
    }

    public function export(): JsonResponse
    {
        $filters = request()->all();
        $format = (string) (request('format') ?? 'json');
        $sortBy = (string) ($filters['sort_by'] ?? '');
        $sortDir = strtolower((string) ($filters['sort_dir'] ?? 'desc'));
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'desc';

        if (($filters['paid'] ?? null) === 'paid') {
            $filters['paid'] = 'yes';
        } elseif (($filters['paid'] ?? null) === 'unpaid') {
            $filters['paid'] = 'no';
        } elseif (($filters['paid'] ?? null) === 'any') {
            unset($filters['paid']);
        }

        $query = Invoice::query()
            ->whereNull('duplicata')
            ->with(['docType', 'expenseAssociatedInvoices'])
            ->filters($filters)
            ->whereNotIn('id', function ($subquery) {
                $subquery->select('associated_invoice_id')
                    ->from('mfw_accounts_invoice_expense_associations');
            });

        $sortMap = [
            'invoice_id' => 'document_id',
            'date' => 'invoice_date',
            'amount' => 'amount',
            'net_gain' => 'net_gain',
            'net_gain_percent' => 'net_gain_percent',
        ];

        if (array_key_exists($sortBy, $sortMap)) {
            $query->orderBy($sortMap[$sortBy], $sortDir)->orderByDesc('id');
        } else {
            $query->orderByDesc('id');
        }

        $mainInvoices = $query->get();
        $rows = [];
        $summary = [
            'amount_eur' => 0.0,
            'payable_vat_eur' => 0.0,
            'expenses_eur' => 0.0,
            'net_gain_eur' => 0.0,
        ];

        foreach ($mainInvoices as $invoice) {
            $associated = $invoice->expenseAssociatedInvoices ?? collect();
            $invoiceCollection = $associated->prepend($invoice);

            $totalIncomeEur = $invoiceCollection->reduce(function (float $carry, Invoice $item): float {
                $amount = $item->amount + $item->vat;
                $amountEur = Invoice::convertAmountToReporting($amount, (int) $item->currency);

                return $carry + $amountEur;
            }, 0.0);

            $netGain = $invoice->net_gain ?? 0.0;
            $netGainEur = Invoice::convertAmountToReporting($netGain, (int) $invoice->currency);

            $expenses = $invoice->expenses ?? 0.0;
            $expensesEur = Invoice::convertAmountToReporting($expenses, (int) $invoice->currency);
            $payableVatEur = $netGainEur > 0.0 ? $netGainEur * 0.2 : 0.0;
            $netGainPercent = $invoice->net_gain_percent ?? 0.0;

            $totalIncomeEur = round($totalIncomeEur, 2);
            $payableVatEur = round($payableVatEur, 2);
            $expensesEur = round($expensesEur, 2);
            $netGainEur = round($netGainEur, 2);

            $documentIds = $invoiceCollection
                ->pluck('document_id')
                ->filter()
                ->unique()
                ->values()
                ->implode(', ');

            $docTypeLabel = $invoice->docType?->admin_name ?: $invoice->docType?->name;
            $rows[] = [
                'doc_type' => $docTypeLabel ?: $invoice->doc_type,
                'date' => $invoice->invoice_date,
                'invoice_id' => $documentIds,
                'amount_eur' => $totalIncomeEur,
                'payable_vat_eur' => $payableVatEur,
                'expenses_eur' => $expensesEur,
                'net_gain_eur' => $netGainEur,
                'net_gain_percent' => $netGainPercent,
            ];

            $summary['amount_eur'] += $totalIncomeEur;
            $summary['payable_vat_eur'] += $payableVatEur;
            $summary['expenses_eur'] += $expensesEur;
            $summary['net_gain_eur'] += $netGainEur;
        }

        $summary['amount_eur'] = round($summary['amount_eur'], 2);
        $summary['payable_vat_eur'] = round($summary['payable_vat_eur'], 2);
        $summary['expenses_eur'] = round($summary['expenses_eur'], 2);
        $summary['net_gain_eur'] = round($summary['net_gain_eur'], 2);
        $summary['net_gain_percent'] = $summary['amount_eur'] > 0.0
            ? round(($summary['net_gain_eur'] / $summary['amount_eur']) * 100, 2)
            : 0.0;
        $summary['label'] = __('mfw-accounts::ui.expenses.summary');

        if ($format === 'xlsx') {
            $filename = 'invoices-export-' . now()->format('Ymd-His') . '.xlsx';
            $content = Excel::raw(new InvoicesIndexExport($rows, $summary), ExcelWriter::XLSX);

            return response()->json([
                'download' => [
                    'filename' => $filename,
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'content' => base64_encode($content),
                ],
            ]);
        }

        return response()->json([
            'rows' => $rows,
            'summary' => $summary,
        ]);
    }
}
