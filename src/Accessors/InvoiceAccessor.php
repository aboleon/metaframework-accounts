<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Accessors;

use MetaFramework\Accounts\Models\Invoice;

class InvoiceAccessor
{
    private ?InvoiceExpenseAccessor $expenseAccessor = null;

    public function __construct(private readonly Invoice $invoice) {}

    public function isBGN(): bool
    {
        return $this->invoice->currency == Invoice::BGN_CURRENCY_ID;
    }

    public function bgnToEur(): float
    {
        return ($this->invoice->amount + $this->invoice->vat)  / Invoice::BGN_TO_EUR_RATE;
    }

    public function expenses(): float
    {
        return $this->getExpenseAccessor()->getExpenses();
    }

    public function expensesInEur(): float
    {
        return $this->getExpenseAccessor()->getExpensesInEur();
    }

    public function payableVatOnProfit(): float
    {
        return $this->getExpenseAccessor()->calculatePayableVat();
    }

    public function payableVatOnProfitInEur(): float
    {
        return $this->getExpenseAccessor()->calculatePayableVatInEur();
    }

    public function netGain(): float
    {
        return $this->getExpenseAccessor()->calculateNetProfit();
    }

    public function netGainInEur(): float
    {
        return $this->getExpenseAccessor()->calculateNetProfitInEur();
    }

    private function getExpenseAccessor(): InvoiceExpenseAccessor
    {
        if (!$this->expenseAccessor) {
            $this->expenseAccessor = new InvoiceExpenseAccessor($this->invoice);
        }

        return $this->expenseAccessor;
    }
}
