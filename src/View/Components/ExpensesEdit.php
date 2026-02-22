<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use MetaFramework\Accounts\Accessors\InvoiceExpenseAccessor;
use MetaFramework\Accounts\Models\Invoice;

class ExpensesEdit extends Component
{
    public function __construct(
        public Invoice $invoice,
        public InvoiceExpenseAccessor $expenseAccessor,
    ) {}

    public function render(): View|string
    {
        return view('mfw-accounts::components.expenses-edit');
    }
}
