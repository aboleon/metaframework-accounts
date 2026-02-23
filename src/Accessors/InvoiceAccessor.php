<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Accessors;

use MetaFramework\Accounts\Models\Invoice;

class InvoiceAccessor
{
    private ?InvoiceExpenseAccessor $expenseAccessor = null;

    public function __construct(private readonly Invoice $invoice) {}

    public function usesReportingConversion(): bool
    {
        return Invoice::usesReportingConversionForCurrency((int) $this->invoice->currency);
    }

    public function totalInReportingCurrency(): float
    {
        return Invoice::convertAmountToReporting(
            (float) $this->invoice->amount + (float) $this->invoice->vat,
            (int) $this->invoice->currency,
        );
    }

    public function reportingCurrencyLabel(): string
    {
        return Invoice::reportingCurrencyLabel();
    }

    public function expenses(): float
    {
        return $this->getExpenseAccessor()->getExpenses();
    }

    public function expensesInReportingCurrency(): float
    {
        return $this->getExpenseAccessor()->getExpensesInReportingCurrency();
    }

    public function payableVatOnProfit(): float
    {
        return $this->getExpenseAccessor()->calculatePayableVat();
    }

    public function payableVatOnProfitInReportingCurrency(): float
    {
        return $this->getExpenseAccessor()->calculatePayableVatInReportingCurrency();
    }

    public function netGain(): float
    {
        return $this->getExpenseAccessor()->calculateNetProfit();
    }

    public function netGainInReportingCurrency(): float
    {
        return $this->getExpenseAccessor()->calculateNetProfitInReportingCurrency();
    }

    private function getExpenseAccessor(): InvoiceExpenseAccessor
    {
        if (!$this->expenseAccessor) {
            $this->expenseAccessor = new InvoiceExpenseAccessor($this->invoice);
        }

        return $this->expenseAccessor;
    }
}
