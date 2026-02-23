<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Accessors;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use MetaFramework\Accessors\Prices;
use MetaFramework\Accounts\Models\Invoice;

class InvoiceExpenseAccessor
{
    public function __construct(private readonly Invoice $invoice) {}

    public function getExpenses(): float
    {
        return (float) $this->invoice->expenses;
    }

    public function getExpensesFormatted(): string
    {
        return Prices::readableFormat(price: $this->getExpenses(), currency: $this->getCurrencySign());
    }

    public function calculateIncome(): float
    {
        $mainInvoiceTotal = (float) $this->invoice->total_amount;

        $associatedTotal = $this->invoice->expenseAssociatedInvoices->sum(function (Invoice $invoice) {
            return (float) $invoice->total_amount;
        });

        return $mainInvoiceTotal + $associatedTotal;
    }

    public function calculateIncomeFormatted(): string
    {
        return Prices::readableFormat(price: $this->calculateIncome(), currency: $this->getCurrencySign());
    }

    public function calculateBruteProfit(): float
    {
        return $this->calculateIncome() - $this->getExpenses();
    }

    public function calculateBruteProfitFormatted(): string
    {
        return Prices::readableFormat(price: $this->calculateBruteProfit(), currency: $this->getCurrencySign());
    }

    public function calculateTaxBase(): float
    {
        $bruteProfit = $this->calculateBruteProfit();

        return round($bruteProfit / 1.2, 2);
    }

    public function calculateTaxBaseFormatted(): string
    {
        return Prices::readableFormat(price: $this->calculateTaxBase(), currency: $this->getCurrencySign());
    }

    public function calculatePayableVat(): float
    {
        return round($this->calculateBruteProfit() - $this->calculateTaxBase(), 2);
    }

    public function calculatePayableVatFormatted(): string
    {
        return Prices::readableFormat(price: $this->calculatePayableVat(), currency: $this->getCurrencySign());
    }

    public function calculateNetProfit(): float
    {
        return $this->calculateTaxBase();
    }

    public function calculateNetProfitFormatted(): string
    {
        return Prices::readableFormat(price: $this->calculateNetProfit(), currency: $this->getCurrencySign());
    }

    public function usesReportingConversion(): bool
    {
        return Invoice::usesReportingConversionForCurrency((int) $this->invoice->currency);
    }

    public function convertToReportingCurrency(float $amount): float
    {
        return Invoice::convertAmountToReporting($amount, (int) $this->invoice->currency);
    }

    public function getExpensesInReportingCurrency(): float
    {
        return $this->usesReportingConversion() ? $this->convertToReportingCurrency($this->getExpenses()) : $this->getExpenses();
    }

    public function calculateIncomeInReportingCurrency(): float
    {
        return $this->usesReportingConversion() ? $this->convertToReportingCurrency($this->calculateIncome()) : $this->calculateIncome();
    }

    public function calculateBruteProfitInReportingCurrency(): float
    {
        return $this->usesReportingConversion() ? $this->convertToReportingCurrency($this->calculateBruteProfit()) : $this->calculateBruteProfit();
    }

    public function calculateTaxBaseInReportingCurrency(): float
    {
        return $this->usesReportingConversion() ? $this->convertToReportingCurrency($this->calculateTaxBase()) : $this->calculateTaxBase();
    }

    public function calculatePayableVatInReportingCurrency(): float
    {
        return $this->usesReportingConversion() ? $this->convertToReportingCurrency($this->calculatePayableVat()) : $this->calculatePayableVat();
    }

    public function calculateNetProfitInReportingCurrency(): float
    {
        return $this->usesReportingConversion() ? $this->convertToReportingCurrency($this->calculateNetProfit()) : $this->calculateNetProfit();
    }

    public function reportingCurrencyLabel(): string
    {
        return Invoice::reportingCurrencyLabel();
    }

    public function getAvailableInvoicesForAssociation(): Collection
    {
        $alreadyAssociatedIds = $this->getAlreadyAssociatedInvoiceIds();

        return Invoice::query()
            ->whereNotNull('paid')
            ->whereNull('duplicata')
            ->where('id', '!=', $this->invoice->id)
            ->whereNotIn('id', $alreadyAssociatedIds)
            ->with(['client', 'currencyType'])
            ->orderByDesc('invoice_date')
            ->get();
    }

    public function getDisabledInvoiceIds(): array
    {
        return DB::table('mfw_accounts_invoice_expense_associations')
            ->where('parent_invoice_id', '!=', $this->invoice->id)
            ->pluck('associated_invoice_id')
            ->toArray();
    }

    private function getAlreadyAssociatedInvoiceIds(): array
    {
        return DB::table('mfw_accounts_invoice_expense_associations')
            ->pluck('associated_invoice_id')
            ->toArray();
    }

    public function getCurrentlyAssociatedIds(): array
    {
        return $this->invoice->expenseAssociatedInvoices->pluck('id')->toArray();
    }

    public function hasExpenseData(): bool
    {
        return (bool) $this->invoice->no_expenses || $this->invoice->expenses > 0 || $this->invoice->net_gain > 0;
    }

    private function getCurrencySign(): string
    {
        return $this->invoice->currencyType?->name ?? '';
    }
}
