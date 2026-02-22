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

    public function isBGN(): bool
    {
        return $this->invoice->currency == Invoice::BGN_CURRENCY_ID;
    }

    public function convertToEur(float $amount): float
    {
        return $amount / Invoice::BGN_TO_EUR_RATE;
    }

    public function getExpensesInEur(): float
    {
        return $this->isBGN() ? $this->convertToEur($this->getExpenses()) : $this->getExpenses();
    }

    public function calculateIncomeInEur(): float
    {
        return $this->isBGN() ? $this->convertToEur($this->calculateIncome()) : $this->calculateIncome();
    }

    public function calculateBruteProfitInEur(): float
    {
        return $this->isBGN() ? $this->convertToEur($this->calculateBruteProfit()) : $this->calculateBruteProfit();
    }

    public function calculateTaxBaseInEur(): float
    {
        return $this->isBGN() ? $this->convertToEur($this->calculateTaxBase()) : $this->calculateTaxBase();
    }

    public function calculatePayableVatInEur(): float
    {
        return $this->isBGN() ? $this->convertToEur($this->calculatePayableVat()) : $this->calculatePayableVat();
    }

    public function calculateNetProfitInEur(): float
    {
        return $this->isBGN() ? $this->convertToEur($this->calculateNetProfit()) : $this->calculateNetProfit();
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
        return $this->invoice->expenses > 0 || $this->invoice->net_gain > 0;
    }

    private function getCurrencySign(): string
    {
        return $this->invoice->currencyType?->name ?? '';
    }
}
