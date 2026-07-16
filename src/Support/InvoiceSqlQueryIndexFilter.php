<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use MetaFramework\Accounts\Models\Invoice;
use MetaFramework\Contracts\SqlQueryIndexFilter;
use MetaFramework\Data\SqlQueryResult;

class InvoiceSqlQueryIndexFilter implements SqlQueryIndexFilter
{
    public function apply(
        EloquentBuilder|QueryBuilder $query,
        SqlQueryResult $result,
    ): EloquentBuilder|QueryBuilder {
        $invoiceIds = collect($result->column('id'))
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        if ($invoiceIds === []) {
            return $query->whereRaw('1 = 0');
        }

        $keyColumn = $query instanceof EloquentBuilder
            ? $query->getModel()->getQualifiedKeyName()
            : (new Invoice)->getQualifiedKeyName();

        $orderBindings = [];
        $orderCases = collect($invoiceIds)
            ->map(function (int $invoiceId, int $position) use (&$orderBindings): string {
                $orderBindings[] = $invoiceId;
                $orderBindings[] = $position;

                return 'WHEN ? THEN ?';
            })
            ->implode(' ');

        return $query
            ->whereIn($keyColumn, $invoiceIds)
            ->orderByRaw("CASE {$keyColumn} {$orderCases} END", $orderBindings);
    }
}
