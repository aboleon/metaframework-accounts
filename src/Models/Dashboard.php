<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use MetaFramework\GooglePlaces\Accessors\Country;

class Dashboard extends Model
{

    protected $table = 'mfw_accounts_clients';

    private function reportingSumExpression(string $column): string
    {
        return Invoice::reportingSqlExpression($column);
    }

    private function sumInReportingCurrency(Builder $query): object
    {
        $amountExpression = $this->reportingSumExpression('amount');
        $vatExpression = $this->reportingSumExpression('vat');

        return (clone $query)
            ->toBase()
            ->selectRaw("{$amountExpression} as amount, {$vatExpression} as vat")
            ->first();
    }

    public function statClients(array $filters, string $lang): array
    {
        $invoiced_clients = Invoice::select('account_id')->filters($filters)->distinct()->pluck('account_id');

        $clients = Account::whereIn('id', $invoiced_clients)
            ->with(['address' => function ($query) {
                $query->orderByDesc('billing');
            }])
            ->get();

        $clients = collect($clients);
        $grouped = $clients->groupBy(function (Account $client) {
            $billing = $client->address->sortByDesc('billing')->first();

            return $billing?->country_code ?? 'unknown';
        });
        $groupCount = $grouped->map(function ($item) {
            return collect($item)->count();
        });
        $groupCount = $groupCount->sort()->reverse()->toArray();
        $named_countries = collect(array_keys($groupCount))
            ->mapWithKeys(function ($code) {
                return [$code => $code === 'unknown' ? __('mfw-accounts::ui.country_unknown') : Country::getCountryNameByCode($code)];
            })
            ->toArray();

        $turnover = [];
        foreach ($grouped as $key => $virgo) {
            $query = Invoice::whereIn('account_id', $virgo->pluck('id'))->filters($filters)->whereNull('duplicata');
            $totals = $this->sumInReportingCurrency($query);
            $turnover[$key] = [
                'amount' => (float) ($totals->amount ?? 0),
                'vat' => (float) ($totals->vat ?? 0),
            ];
        }

        $data = [];
        $data['total'] = $clients->count();
        $data['bygroup'] = $groupCount;
        $data['turnoverByGroup'] = collect($turnover);
        $data['named_countries'] = $named_countries;

        return $data;
    }

    public function turnover(bool $export = false, array $filters = []): SupportCollection
    {
        $amountExpression = $this->reportingSumExpression('amount');
        $vatExpression = $this->reportingSumExpression('vat');

        $query = Invoice::query()
            ->whereNull('duplicata')
            ->filters($filters)
            ->export($export);

        return $query
            ->toBase()
            ->selectRaw("date_format(invoice_date, '%Y') as year, {$amountExpression} as amount, {$vatExpression} as vat")
            ->groupBy('year')
            ->orderByDesc('year')
            ->get();
    }

    public function years(): Collection
    {
        return Invoice::selectRaw("date_format(invoice_date, '%Y') as year")->orderBy('year')->distinct()->get();
    }

    public function operativeTurnover(Collection $years): array
    {
        $operative_turnover = [];

        foreach ($years as $item) {
            $amountExpression = $this->reportingSumExpression('amount');
            $vatExpression = $this->reportingSumExpression('vat');

            $query = Invoice::query()
                ->whereNull('duplicata')
                ->whereBetween('invoice_date', [($item->year - 1) . '-11-01', $item->year . '-10-31']);

            $row = $query
                ->toBase()
                ->selectRaw("{$amountExpression} as amount, {$vatExpression} as vat")
                ->first();
            $operative_turnover[$item->year] = [
                'amount' => (float) ($row->amount ?? 0),
                'vat' => (float) ($row->vat ?? 0),
            ];
        }

        krsort($operative_turnover);

        return $operative_turnover;
    }
}
