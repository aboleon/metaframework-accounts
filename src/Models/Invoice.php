<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;


use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use MetaFramework\Casts\Datepicker;
use MetaFramework\Casts\NullablePriceInteger;
use MetaFramework\Casts\PriceInteger;
use MetaFramework\Traits\Locale;

/**
 * @property int $doc_type
 */
class Invoice extends Model
{

    use Locale;
    use SoftDeletes;

    public const DEFAULT_DOC_TYPE = 5;

    protected $table = 'mfw_accounts_invoices';

    public $timestamps = false;

    protected $fillable
        = [
            'document_id',
            'account_id',
            'sale_id',
            'title',
            'subtitle',
            'content',
            'notes',
            'amount',
            'amount_text',
            'quantity',
            'vat',
            'expenses',
            'no_expenses',
            'expense_protocol_ref',
            'net_gain',
            'net_gain_percent',
            'vat_id',
            'currency',
            'sell_channel',
            'paid',
            'pay_mean',
            'sent_at',
            'doc_type',
            'hash',
            'pdf_locale',
            'invoice_date',
            'transaction_date',
            'date_paid',
            'date_before',
            'user',
            'duplicata',
            'bank_account',
            'attached_to',
        ];

    protected $casts
        = [
            'invoice_date'     => Datepicker::class,
            'date_paid'        => Datepicker::class,
            'date_before'      => Datepicker::class,
            'sent_at'          => 'datetime',
            'amount'           => PriceInteger::class,
            'vat'              => PriceInteger::class,
            'expenses'         => NullablePriceInteger::class,
            'no_expenses'      => 'bool',
            'net_gain'         => NullablePriceInteger::class,
            'net_gain_percent' => NullablePriceInteger::class,
        ];

    protected $attributes
        = [
            'doc_type' => self::DEFAULT_DOC_TYPE,
            'no_expenses' => false,
        ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (! array_key_exists('currency', $attributes) && ! array_key_exists('currency', $this->attributes)) {
            $this->attributes['currency'] = self::defaultCurrencyId();
        }
    }

    public static function defaultCurrencyId(): int
    {
        return (int) config('mfw-accounts.invoice.default_currency_id', 1);
    }

    public static function reportingCurrencyTargetId(): ?int
    {
        $value = config('mfw-accounts.reporting_currency.target_currency_id');

        return is_numeric($value) ? (int) $value : null;
    }

    public static function reportingCurrencyLabel(): string
    {
        return trim((string) config('mfw-accounts.reporting_currency.label', 'EUR'));
    }

    public static function reportingCurrencySourceId(): ?int
    {
        $value = config('mfw-accounts.reporting_currency.conversion.source_currency_id');

        return is_numeric($value) ? (int) $value : null;
    }

    public static function reportingCurrencyRate(): ?float
    {
        $value = config('mfw-accounts.reporting_currency.conversion.rate');

        if ($value === null || $value === '') {
            return null;
        }

        $rate = (float) $value;

        return $rate > 0 ? $rate : null;
    }

    public static function usesReportingConversionForCurrency(?int $currencyId): bool
    {
        $sourceId = self::reportingCurrencySourceId();
        $rate = self::reportingCurrencyRate();

        return $currencyId !== null && $sourceId !== null && $rate !== null && $currencyId === $sourceId;
    }

    public static function convertAmountToReporting(float|int $amount, ?int $currencyId): float
    {
        if (! self::usesReportingConversionForCurrency($currencyId)) {
            return (float) $amount;
        }

        $rate = self::reportingCurrencyRate();

        return $rate ? ((float) $amount / $rate) : (float) $amount;
    }

    public static function reportingSqlExpression(string $column): string
    {
        $sourceId = self::reportingCurrencySourceId();
        $rate = self::reportingCurrencyRate();
        $columnExpression = "({$column} / 100.0)";

        if ($sourceId === null || $rate === null) {
            return "sum({$columnExpression})";
        }

        return "sum(case when currency = {$sourceId} then {$columnExpression} / {$rate} else {$columnExpression} end)";
    }

    public static function nextDocumentId(int $docType): int
    {
        $docType = self::where('doc_type', $docType)->first();

        return (int) match ($docType->numerotation) {
            'own' => self::where('doc_type', $docType)->max('document_id') + 1,
            default => self::max('document_id') + 1
        };
    }

    public function scopeFilters(Builder $query, array $filters = []): Builder
    {
        return $query
            ->when($filters['doc_type'] ?? null, function (Builder $builder, $docType) {
                if (is_array($docType)) {
                    $docTypes = array_values(array_filter($docType, fn ($value) => (string) $value !== ''));
                    $docTypes = array_map('intval', $docTypes);

                    return $docTypes ? $builder->whereIn('doc_type', $docTypes) : $builder;
                }

                return $builder->where('doc_type', $docType);
            })
            ->when(
                ($filters['date_operator'] ?? null) !== null || ($filters['dashboard'] ?? null) !== null,
                function (Builder $builder) use ($filters) {
                    return $this->scopeDateRange($builder, $filters);
                },
            )
            ->when(
                $filters['amount_operator'] ?? null,
                function (Builder $builder) use ($filters) {
                    return $this->scopeAmounts($builder, $filters);
                },
            )
            ->when($filters['billing_id'] ?? null, fn (Builder $builder, $billingId) => $builder->where('document_id', $billingId))
            ->when(
                $filters['paid'] ?? null,
                function (Builder $builder) use ($filters) {
                    return $this->scopeIsPaid($builder, $filters);
                },
            )
            ->when(
                $filters['expense_protocol_ref'] ?? null,
                function (Builder $builder, $protocolRef) {
                    return $builder->where('expense_protocol_ref', 'like', '%' . $protocolRef . '%');
                },
            )
            ->when(
                $filters['account_id'] ?? null,
                function (Builder $builder, $clientId) use ($filters) {
                    if (!empty($filters['exclude'])) {
                        return $builder->where('account_id', '!=', $clientId);
                    }

                    return $builder->where('account_id', $clientId);
                },
            )
            ->when($filters['sale_id'] ?? null, fn (Builder $builder, $saleId) => $builder->where('sale_id', $saleId));
    }

    public function scopeAmounts(Builder $query, array $filters = []): Builder
    {
        $operator   = $filters['amount_operator'] ?? null;
        $amountRaw1 = array_key_exists('amount', $filters) ? $filters['amount'] : null;
        $amountRaw2 = array_key_exists('amount2', $filters) ? $filters['amount2'] : null;
        $amount1    = $amountRaw1 !== null && trim((string) $amountRaw1) !== '' ? (float) $amountRaw1 : null;
        $amount2    = $amountRaw2 !== null && trim((string) $amountRaw2) !== '' ? (float) $amountRaw2 : null;

        if ($operator === 'between' && $amount1 !== null && $amount2 !== null) {
            return $query->whereBetween('amount', [$amount1, $amount2]);
        }

        if ($amount1 === null || empty($operator)) {
            return $query;
        }

        return match ($operator) {
            'equal' => $query->where('amount', $amount1),
            'greater' => $query->where('amount', '>', $amount1),
            'less' => $query->where('amount', '<', $amount1),
            default => $query,
        };
    }

    public function scopeDateRange(Builder $query, array $filters = []): Builder
    {
        $operator = $filters['date_operator'] ?? null;
        $date1    = array_key_exists('date', $filters) ? $this->normalizeFilterDate($filters['date']) : null;
        $date2    = array_key_exists('date2', $filters) ? $this->normalizeFilterDate($filters['date2']) : null;

        if (($filters['dashboard'] ?? false) !== false) {
            return $query->whereBetween('invoice_date', [(date('Y') - 1) . '-11-01', date('Y') . '-10-31']);
        }

        if (empty($operator) || $date1 === null) {
            return $query;
        }

        if ($operator === 'between' && $date2 !== null) {
            return $query->whereBetween('invoice_date', [$date1, $date2]);
        }

        return match ($operator) {
            'equal' => $query->where('invoice_date', $date1),
            'greater' => $query->where('invoice_date', '>', $date1),
            'less' => $query->where('invoice_date', '<', $date1),
            default => $query,
        };
    }

    private function normalizeFilterDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        $normalized = \App\Helpers\Helpers::kvasir_dateFormat($value, 'd/m/Y', 'Y-m-d');

        return $normalized ?: null;
    }

    public function scopeIsPaid(Builder $query, array $filters = []): Builder
    {
        if (($filters['paid'] ?? null) === 'no') {
            return $query->whereNull('paid');
        }

        if (($filters['paid'] ?? null) === 'yes') {
            return $query->whereNotNull('paid');
        }

        return $query;
    }

    public function scopeExport(Builder $query, bool $export = false): Builder
    {
        if ($export === false) {
            return $query;
        }

        $excludedCountryCodes = ['BG']; // adjust as needed if more exclusions are required
        $excludedClients      = Account::whereHas('address', function ($query) use ($excludedCountryCodes) {
            $query->whereIn('country_code', $excludedCountryCodes);
        })->pluck('id');

        return $query->whereNotIn('account_id', $excludedClients);
    }

    public function scopeSale(Builder $query, ?int $saleId = null): Builder
    {
        $id = $saleId ?? request()->sale_id ?? null;

        return $id ? $query->where('sale_id', $id) : $query;
    }

    public function attachedTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'attached_to', 'document_id')->where('doc_type', 1);
    }

    public function cashflow(): ?object
    {
        return $this->hasOne(CashflowStructure::class, 'invoice_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id')->with(['address', 'business']);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user');
    }

    public function currencyType(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency');
    }

    public function details(): HasMany
    {
        return $this->hasMany(InvoiceStructure::class, 'invoice_id');
    }

    public function docType(): BelongsTo
    {
        return $this->belongsTo(CashflowDocTypes::class, 'doc_type');
    }

    public function duplicatas(): HasMany
    {
        return $this
            ->hasMany(self::class, 'document_id', 'document_id')
            ->where('duplicata', 1)
            ->where('account_id', $this->account_id);
    }

    public function facturation(): HasMany
    {
        return $this->hasMany(self::class, 'account_id', 'account_id')->where('doc_type', 1);
    }

    public function getOperatorAttribute(): string
    {
        return $this->createdBy?->names() ?? '';
    }

    public function payMean(): BelongsTo
    {
        return $this->belongsTo(PayMeans::class, 'pay_mean');
    }

    public function vat(): BelongsTo
    {
        return $this->belongsTo(Vat::class, 'vat_id');
    }

    public function expenseAssociatedInvoices(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'mfw_accounts_invoice_expense_associations',
            'parent_invoice_id',
            'associated_invoice_id',
        );
    }

    public function expenseParentInvoice(): ?self
    {
        return self::query()
            ->join('mfw_accounts_invoice_expense_associations', 'mfw_accounts_invoices.id', '=', 'mfw_accounts_invoice_expense_associations.parent_invoice_id')
            ->where('mfw_accounts_invoice_expense_associations.associated_invoice_id', $this->id)
            ->select('mfw_accounts_invoices.*')
            ->first();
    }

    public function isAssociatedToExpense(): bool
    {
        return DB::table('mfw_accounts_invoice_expense_associations')
            ->where('associated_invoice_id', $this->id)
            ->exists();
    }

    public function canEditExpenses(): bool
    {
        return ($this->paid || $this->date_paid) && !$this->duplicata && !$this->isAssociatedToExpense();
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) ($this->amount ?? 0) + (float) ($this->vat ?? 0);
    }
}
