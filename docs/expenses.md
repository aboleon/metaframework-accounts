# Expenses

## Overview

The expense system links cost invoices to revenue invoices, allowing the package to compute net gain and profitability per invoice.

## Expense Associations

Expense invoices are linked to a parent (revenue) invoice through a pivot table.

**Table:** `mfw_accounts_invoice_expense_associations`

| Column | Type | Description |
|--------|------|-------------|
| `parent_invoice_id` | unsignedInteger | FK → mfw_accounts_invoices.id (the revenue invoice) |
| `associated_invoice_id` | unsignedInteger | FK → mfw_accounts_invoices.id (the expense invoice) |

- Each expense invoice can only be associated with one parent (`associated_invoice_id` is unique).
- One parent invoice can have many associated expense invoices.

The `Invoice` model exposes this through the `expenseAssociatedInvoices()` BelongsToMany relation.

## Editability Rule

Expenses can only be edited **after the parent invoice is paid**.

```php
$invoice->canEditExpenses(); // Returns bool
```

Attempting to edit expenses on an unpaid invoice is blocked at the controller level.

## InvoiceExpenseAccessor

The `InvoiceExpenseAccessor` class computes expense-related metrics for a given invoice:

| Computed Value | Field Stored | Description |
|----------------|-------------|-------------|
| Total expenses | `expenses` | Sum of all associated expense invoice amounts |
| Net gain | `net_gain` | `amount - expenses` |
| Net gain % | `net_gain_percent` | `(net_gain / amount) * 100` |
| VAT on profit | — | VAT applicable to the net gain |

These computed values are written back to the parent invoice's `expenses`, `net_gain`, and `net_gain_percent` columns. Do not edit these columns manually.

## Checking Association Status

```php
// Check if an invoice is used as an expense on another invoice
$invoice->isAssociatedToExpense(); // Returns bool

// Get the parent invoice if this invoice is an expense
$invoice->expenseParentInvoice(); // Returns ?Invoice
```

## Blade Components

Three Blade components are provided for expense management in your views.

### Expense Edit Form

```blade
<x-mfw-accounts::expenses-edit
    :invoice="$invoice"
    :expenseAccessor="$expenseAccessor"
/>
```

Renders the form for searching and associating expense invoices to a parent invoice. Only shown when `$invoice->canEditExpenses()` is `true`.

### Expense Summary

```blade
<x-mfw-accounts::expenses-summary
    :invoice="$invoice"
    :expenseAccessor="$expenseAccessor"
/>
```

Displays a summary table showing total expenses, net gain, net gain percentage, and VAT on profit for the invoice.

### Associated Notice

```blade
<x-mfw-accounts::expenses-associated-notice :invoice="$invoice" />
```

Displays a notice on an expense invoice indicating which parent invoice it is associated to. Renders nothing if the invoice is not associated to any parent.
