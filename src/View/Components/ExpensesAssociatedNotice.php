<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use MetaFramework\Accounts\Models\Invoice;

class ExpensesAssociatedNotice extends Component
{
    public ?Invoice $parentInvoice;

    public function __construct(
        public Invoice $invoice,
    ) {
        $this->parentInvoice = $this->invoice->expenseParentInvoice();
    }

    public function shouldRender(): bool
    {
        return $this->parentInvoice !== null;
    }

    public function render(): View|string
    {
        return view('mfw-accounts::components.expenses-associated-notice');
    }
}
