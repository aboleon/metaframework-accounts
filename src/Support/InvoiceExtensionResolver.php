<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Support;

use MetaFramework\Accounts\Contracts\InvoiceExtension;

class InvoiceExtensionResolver
{
    public function resolve(): ?InvoiceExtension
    {
        $extensionClass = config('mfw-accounts.extensions.invoice');

        if (!is_string($extensionClass) || $extensionClass === '') {
            return null;
        }

        $extension = app($extensionClass);

        return $extension instanceof InvoiceExtension ? $extension : null;
    }
}
