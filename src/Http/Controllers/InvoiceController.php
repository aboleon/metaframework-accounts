<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Controllers;

use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use MetaFramework\Traits\NumericInputNormalizer;
use MetaFramework\Accounts\Accessors\InvoiceAccessor;
use MetaFramework\Accounts\Accessors\InvoiceExpenseAccessor;
use MetaFramework\Accounts\Models\BankAccounts;
use MetaFramework\Accounts\Models\CashflowDocTypes;
use MetaFramework\Accounts\Models\Currency;
use MetaFramework\Accounts\Models\Invoice;
use MetaFramework\Accounts\Models\InvoiceStructure;

class InvoiceController extends Controller
{
    use NumericInputNormalizer;

    public function index(): View
    {
        $filters = request()->all();
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
            ->with(['client', 'createdBy', 'currencyType', 'docType', 'expenseAssociatedInvoices'])
            ->filters($filters);

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

        $total = $query->count();
        $bgnId = Invoice::BGN_CURRENCY_ID;
        $rate = Invoice::BGN_TO_EUR_RATE;
        $totalAmountEur = (clone $query)
            ->toBase()
            ->reorder()
            ->selectRaw(
                "SUM(CASE WHEN currency = {$bgnId} THEN (amount / 100.0) / {$rate} ELSE (amount / 100.0) END) as total_eur"
            )
            ->value('total_eur');
        $summaryInvoices = (clone $query)
            ->select(['id', 'currency', 'expenses', 'amount', 'vat', 'net_gain', 'net_gain_percent'])
            ->whereNotIn('id', function ($subquery) {
                $subquery->select('associated_invoice_id')
                    ->from('mfw_accounts_invoice_expense_associations');
            })
            ->get();
        $summaryTotals = $summaryInvoices->reduce(
            function (array $carry, Invoice $invoice): array {
                $accessor = new InvoiceAccessor($invoice);
                $carry['expenses_eur'] += $accessor->expensesInEur();
                $netGain = $invoice->net_gain ?? 0.0;
                $netGainEur = $invoice->currency == Invoice::BGN_CURRENCY_ID
                    ? $netGain / Invoice::BGN_TO_EUR_RATE
                    : $netGain;
                $carry['net_gain_eur'] += $netGainEur;
                if ($netGain > 0.0) {
                    $carry['payable_vat_eur'] += $netGainEur * 0.2;
                }
                if ($invoice->net_gain_percent !== null) {
                    $carry['net_gain_percent_sum'] += $invoice->net_gain_percent;
                    $carry['net_gain_percent_count']++;
                }

                return $carry;
            },
            [
                'expenses_eur' => 0.0,
                'payable_vat_eur' => 0.0,
                'net_gain_eur' => 0.0,
                'net_gain_percent_sum' => 0.0,
                'net_gain_percent_count' => 0,
            ]
        );
        $summaryTotals['net_gain_percent'] = $summaryTotals['net_gain_percent_count'] > 0
            ? round($summaryTotals['net_gain_percent_sum'] / $summaryTotals['net_gain_percent_count'], 2)
            : null;
        unset($summaryTotals['net_gain_percent_sum'], $summaryTotals['net_gain_percent_count']);
        $invoices = $query->paginate(15);
        $expenseAssociations = DB::table('mfw_accounts_invoice_expense_associations')
            ->join('mfw_accounts_invoices', 'mfw_accounts_invoices.id', '=', 'mfw_accounts_invoice_expense_associations.parent_invoice_id')
            ->whereIn('mfw_accounts_invoice_expense_associations.associated_invoice_id', $invoices->pluck('id'))
            ->pluck('mfw_accounts_invoices.document_id', 'mfw_accounts_invoice_expense_associations.associated_invoice_id')
            ->toArray();
        $expenseAssociationParents = DB::table('mfw_accounts_invoice_expense_associations')
            ->whereIn('mfw_accounts_invoice_expense_associations.associated_invoice_id', $invoices->pluck('id'))
            ->pluck('parent_invoice_id', 'associated_invoice_id')
            ->toArray();
        $expenseAssociationProtocols = DB::table('mfw_accounts_invoice_expense_associations')
            ->join('mfw_accounts_invoices', 'mfw_accounts_invoices.id', '=', 'mfw_accounts_invoice_expense_associations.parent_invoice_id')
            ->whereIn('mfw_accounts_invoice_expense_associations.associated_invoice_id', $invoices->pluck('id'))
            ->pluck('mfw_accounts_invoices.expense_protocol_ref', 'mfw_accounts_invoice_expense_associations.associated_invoice_id')
            ->toArray();

        $duplicatas = Invoice::query()
            ->whereIn('document_id', $invoices->pluck('document_id'))
            ->whereIn('account_id', $invoices->pluck('account_id'))
            ->where('duplicata', 1)
            ->get()
            ->groupBy(fn ($d) => $d->document_id . '-' . $d->account_id);

        $invoices->each(function ($invoice) use ($duplicatas) {
            $key = $invoice->document_id . '-' . $invoice->account_id;
            $invoice->setRelation('duplicatas', $duplicatas->get($key, collect()));
        });

        return view('mfw-accounts::invoices.index')->with([
            'appends' => $filters,
            'doc_types' => CashflowDocTypes::simpleList(),
            'invoices' => $invoices,
            'total' => $total,
            'total_amount_eur' => $totalAmountEur,
            'summary_totals' => $summaryTotals,
            'expense_associations' => $expenseAssociations,
            'expense_association_parents' => $expenseAssociationParents,
            'expense_association_protocols' => $expenseAssociationProtocols,
        ]);
    }

    public function edit(Invoice $invoice): View
    {
        $invoice->load(['details', 'client.invoices', 'facturation', 'currencyType', 'expenseAssociatedInvoices']);

        $expenseAccessor = new InvoiceExpenseAccessor($invoice);

        return view('mfw-accounts::invoices.edit')->with([
            'accessor' => new InvoiceAccessor($invoice),
            'expenseAccessor' => $expenseAccessor,
            'data' => $invoice,
            'client' => $invoice->client,
            'bank_accounts' => BankAccounts::accounts(config('app.locale')),
        ]);
    }

    public function mailPreview(string $hash): View
    {
        $invoice = Invoice::where('hash', $hash)->firstOrFail();
        $invoice->load(['client', 'details', 'currencyType']);

        $locale = $invoice->pdf_locale ?? app()->getLocale();
        app()->setLocale($locale);

        return view('mfw-accounts::mails.invoice')->with([
            'invoice' => $invoice,
            'client' => $invoice->client,
            'pdf_url' => url('mfw-accounts/pdf/' . $invoice->hash),
            'locale' => $locale,
            'currencies' => Currency::getCurrencies(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $invoice = $this->createFromRequest($request);

        return redirect()
            ->route('mfw-accounts.invoices.edit', $invoice)
            ->with('session_message', __('mfw-accounts::ui.document_is_saved'));
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $validated = $this->validateInvoice($request);

        DB::transaction(function () use ($invoice, $validated) {
            $this->persistInvoice($invoice, $validated);
        });

        return redirect()
            ->route('mfw-accounts.invoices.edit', $invoice)
            ->with('session_message', __('mfw-accounts::ui.document_is_saved'));
    }

    public function duplicate(Invoice $invoice): RedirectResponse
    {
        $duplicata = $invoice->replicate();
        $duplicata->duplicata = 1;
        $duplicata->doc_type = CashflowDocTypes::where('slug', 'duplicata')->value('id');
        $duplicata->hash = Str::random(40);
        $duplicata->push();

        return redirect()->route('mfw-accounts.invoices.edit', $duplicata);
    }

    public function validateInvoice(): array
    {
        $this->normalizeNumericInputKey('amount');
        $this->normalizeNumericInputKey('vat');
        $this->normalizeNumericInputKey('quantity');

        return request()->validate([
            'account_id' => 'required|integer|exists:users,id',
            'doc_type' => 'required|integer|exists:mfw_accounts_cashflow_doc_types,id',
            'document_id' => 'nullable|integer',
            'invoice_date' => 'required|string',
            'currency' => 'required|integer',
            'sell_channel' => 'nullable',
            'title' => 'nullable|string',
            'notes' => 'nullable|string',
            'pdf_locale' => 'nullable|string',
            'bank_account' => 'nullable|integer',
            'attached_to' => 'nullable|integer',
            'sale_id' => 'nullable|integer',
            'date_paid' => 'nullable|string',
            'date_before' => 'nullable|string|required_if:paid,date_before',
            'pay_mean' => 'nullable|integer',
            'paid' => 'nullable',
            'amount' => 'array',
            'amount.*' => 'numeric',
            'vat' => 'array',
            'vat.*' => 'numeric',
            'vat_id' => 'array',
            'vat_id.*' => 'integer|nullable',
            'quantity' => 'array',
            'quantity.*' => 'numeric|nullable',
            'content' => 'array',
            'content.*' => 'nullable|string',
            'expenses' => 'nullable|numeric',
            'expense_associations' => 'nullable|array',
            'expense_associations.*' => 'integer|exists:mfw_accounts_invoices,id',
        ]);
    }

    public function persistInvoice(Invoice $invoice, array $data): Invoice
    {
        $docType = (int) ($data['doc_type'] ?? Invoice::DEFAULT_DOC_TYPE);
        $currency = (int) ($data['currency'] ?? Invoice::DEFAULT_CURRENCY);
        $amounts = $data['amount'] ?? [];
        $vat = $data['vat'] ?? [];
        $vatTypes = $data['vat_id'] ?? [];
        $quantities = $data['quantity'] ?? [];
        $contents = $data['content'] ?? [];
        $expensesProvided = array_key_exists('expenses', $data);
        $expenses = $expensesProvided ? $data['expenses'] : null;
        $expenseAssociationsProvided = array_key_exists('expense_associations', $data);
        $expenseAssociations = $expenseAssociationsProvided ? ($data['expense_associations'] ?? []) : [];
        $previousAssociatedIds = $invoice->exists
            ? $invoice->expenseAssociatedInvoices()->pluck($invoice->getTable() . '.id')->toArray()
            : [];
        $paidSelection = $data['paid'] ?? null;
        $isPaidDateSelection = $paidSelection === 'date_paid' || ($paidSelection === null && $invoice->paid !== null);

        if ($isPaidDateSelection && empty($data['date_paid'])) {
            $data['date_paid'] = now()->format('d/m/Y');
        }

        if ($invoice->exists) {
            $invoice->details()->delete();
        } else {
            $invoice->hash = Str::random(40);
            $invoice->document_id = Invoice::nextDocumentId($docType);
        }

        $invoice->doc_type = $docType;
        $invoice->account_id = (int) $data['account_id'];
        $invoice->invoice_date = $data['invoice_date'];
        $invoice->sell_channel = $data['sell_channel'] ?? null;
        $invoice->title = $data['title'] ?? null;
        $invoice->notes = $data['notes'] ?? null;
        $invoice->currency = $currency;
        $invoice->user = Auth::id();
        $invoice->pdf_locale = $data['pdf_locale'] ?? config('app.locale');
        $invoice->bank_account = $data['bank_account'] ?? null;
        $invoice->attached_to = $docType === 4 ? ($data['attached_to'] ?? null) : null;
        $invoice->sale_id = $data['sale_id'] ?? null;
        $invoice->date_paid = $data['date_paid'] ?? null;
        $invoice->date_before = $data['date_before'] ?? null;
        $invoice->paid = empty($paidSelection) ? null : 1;
        $invoice->pay_mean = $data['pay_mean'] ?? null;
        $invoice->document_id = $data['document_id'] ?? $invoice->document_id;

        if ($invoice->paid !== null && !$invoice->duplicata && $expensesProvided) {
            $invoice->expenses = $expenses;
        }

        $invoice->save();

        if ($invoice->paid !== null && !$invoice->duplicata && !$invoice->isAssociatedToExpense() && $expenseAssociationsProvided) {
            $invoice->expenseAssociatedInvoices()->sync($expenseAssociations);
        }

        // Net gain is updated only when expenses are saved (see InvoiceActions::updateExpenses).

        $amountCount = is_array($amounts) ? count($amounts) : 0;

        if ($amountCount < 2 && is_array($amounts) && $amountCount >= 1) {
            $invoice->content = $contents[0] ?? null;
            $invoice->amount = (float) ($amounts[0] ?? 0);
            $invoice->vat = (float) ($vat[0] ?? 0);
            $invoice->vat_id = $vatTypes[0] ?? null;
            $invoice->quantity = $quantities[0] ?? null;
            $invoice->save();

            return $invoice;
        }

        if ($amountCount > 1) {
            $amountSum = [];
            $vatSum = [];

            for ($i = 0; $i < $amountCount; $i++) {
                $amountValue = (float) ($amounts[$i] ?? 0);
                $vatValue = (float) ($vat[$i] ?? 0);
                $vatValueCents = (int) round($vatValue * 100);

                $amountSum[] = $amountValue;
                $vatSum[] = $vatValue;

                InvoiceStructure::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $amountValue,
                    'vat' => $vatValueCents,
                    'vat_id' => $vatTypes[$i] ?? null,
                    'content' => $contents[$i] ?? null,
                    'quantity' => $quantities[$i] ?? null,
                ]);
            }

            $invoice->update([
                'amount' => array_sum($amountSum),
                'vat' => array_sum($vatSum),
            ]);
        }

        return $invoice;
    }

    public function createFromRequest(Request $request): Invoice
    {
        $validated = $request->validate([
            'account_id' => 'required|integer|exists:users,id',
            'doc_type' => 'required|integer|exists:mfw_accounts_cashflow_doc_types,id',
        ]);

        $docType = (int) ($validated['doc_type'] ?? Invoice::DEFAULT_DOC_TYPE);
        $currency = Invoice::DEFAULT_CURRENCY;

        $invoice = new Invoice;
        $invoice->account_id = (int) $validated['account_id'];
        $invoice->doc_type = $docType;
        $invoice->currency = $currency;
        $invoice->document_id = Invoice::nextDocumentId($docType);
        $invoice->hash = Str::random(40);
        $invoice->user = Auth::id();
        $invoice->pdf_locale = $request->input('pdf_locale', config('app.locale'));
        $invoice->invoice_date = now()->format('d/m/Y');
        $invoice->save();

        return $invoice;
    }

    private function normalizeNumericInputKey(string $key): void
    {
        $value = request()->input($key);
        request()->merge([$key => $this->normalizeNumericArrayValue($value)]);
    }
}
