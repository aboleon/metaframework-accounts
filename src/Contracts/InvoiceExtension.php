<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Contracts;

use MetaFramework\Accounts\Models\Invoice;

interface InvoiceExtension
{
    public function rules(): array;

    public function persist(Invoice $invoice, array $data): void;
}
