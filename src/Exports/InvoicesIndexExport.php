<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoicesIndexExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $summary
     */
    public function __construct(
        private readonly array $rows,
        private readonly array $summary
    ) {}

    public function title(): string
    {
        return 'Invoices';
    }

    public function headings(): array
    {
        $eurLabel = ' (EUR)';
        $netGainLabel = __('mfw-accounts::ui.expenses.net_profit');

        return [
            trans_choice('mfw-accounts::ui.type', 1),
            __('mfw-accounts::ui.Date'),
            __('mfw-accounts::ui.billing_id'),
            __('mfw-accounts::ui.Amount') . $eurLabel,
            __('mfw-accounts::ui.expenses.payable_vat') . $eurLabel,
            __('mfw-accounts::ui.expenses.amount') . $eurLabel,
            $netGainLabel . $eurLabel,
            $netGainLabel . ' %',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $summaryRowIndex = count($this->rows) + 2;

        return [
            $summaryRowIndex => ['font' => ['bold' => true]],
        ];
    }

    public function array(): array
    {
        $rows = array_map(
            fn (array $row) => [
                $row['doc_type'] ?? null,
                $row['date'] ?? null,
                $row['invoice_id'] ?? null,
                $row['amount_eur'] ?? null,
                $row['payable_vat_eur'] ?? null,
                $row['expenses_eur'] ?? null,
                $row['net_gain_eur'] ?? null,
                $row['net_gain_percent'] ?? null,
            ],
            $this->rows
        );

        $rows[] = [
            $this->summary['label'] ?? __('mfw-accounts::ui.expenses.summary'),
            null,
            null,
            $this->summary['amount_eur'] ?? null,
            $this->summary['payable_vat_eur'] ?? null,
            $this->summary['expenses_eur'] ?? null,
            $this->summary['net_gain_eur'] ?? null,
            $this->summary['net_gain_percent'] ?? null,
        ];

        return $rows;
    }
}


