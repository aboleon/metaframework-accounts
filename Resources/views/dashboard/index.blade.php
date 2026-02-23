@php use MetaFramework\Accessors\Prices; @endphp
@extends('layouts.panel')
@section('header')
    <h4>
        {{ __('mfw-accounts::ui.dashboard') }}
    </h4>

@endsection

@section('content')

@php
$totals = array_sum($clients['bygroup']);
$backgroundColor = [];
$hoverBackgroundColor = [];
$countryCount = count($clients['bygroup']);
if ($countryCount > 0) {
    for ($c = 0; $c < $countryCount; ++$c) {
        $hue = ($c * 137) % 360; // golden angle to spread hues
        $backgroundColor[] = "hsl($hue, 65%, 55%)";
        $hoverBackgroundColor[] = "hsl($hue, 65%, 45%)";
    }
}
$i=0; $ccolors = count($backgroundColor);
$between_dates = (!request()->has('date_operator') or (request()->has('date_operator') && request()->date_operator == 'between'));
@endphp

{{-- $data ? \MetaFramework\Accounts\Support\DateFormat::convert((string) $data->invoice_date, 'Y-m-d', 'd/m/Y') : null --}}
<div class="row">
    <div class="col-md-6 col-sm-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{  __('mfw-accounts::ui.turnover') }} : Оперативна година 01 ноември - 31 октомври</h6>
                <a class="btn btn-sm btn-link p-0" data-bs-toggle="collapse" href="#collapse-operative" role="button">
                    <i class="fa fa-chevron-up"></i>
                </a>
            </div>
            <div class="collapse show" id="collapse-operative">
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('mfw-accounts::ui.year') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.turnoverHT') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.VAT') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.common') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.previous') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.difference') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.evolution') }}</th>
                            </tr>
                        </thead>
                        @php
                        $totalAmount = $operative_turnover->sum('amount');
                        $totalHT = $operative_turnover->sum('vat');
                        @endphp
                        <tbody>
                            @foreach($operative_turnover as $key => $value)

                            <?php
                            $previous_year_turnover = $operative_turnover[$key-1]['amount'] ?? 0;
                            $variation = $previous_year_turnover ? ($loop->last ? 0 : (($value['amount'] - $previous_year_turnover)/$previous_year_turnover)*100) : 0; ?>
                            <tr>
                                <td>{{ $key }}</td>
                                <td class="text-end">{!! Prices::readableFormat(price: $value['amount'], currency:'', stripZeros: true) !!}</td>
                                <td class="text-end">{!! Prices::readableFormat(price: $value['vat'], currency:'', stripZeros: true) !!}</td>
                                <td class="text-end">{!! Prices::readableFormat(price: $value['amount']+$value['vat'], currency:'', stripZeros: true) !!}</td>
                                <td class="text-end">{!! $loop->last ? 0 : Prices::readableFormat(price: $previous_year_turnover, currency:'', stripZeros: true) !!}</td>
                                <td class="text-end">{!! $loop->last ? 0 : Prices::readableFormat(price: $value['amount'] - $previous_year_turnover, currency:'', stripZeros: true) !!}</td>
                                <td class="text-end {!! $variation == 0 ? '' : ( $variation < 1 ? 'text-danger':'text-success') !!}">
                                    {{ number_format($variation) }}%
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th class="text-end">{{ Prices::readableFormat(price: $totalAmount, currency:'', stripZeros: true) }}</th>
                                <th class="text-end">{{ Prices::readableFormat(price: $totalHT, currency:'', stripZeros: true) }}</th>
                                <th class="text-end">{{ Prices::readableFormat(price: $totalAmount+$totalHT, currency:'', stripZeros: true) }}</th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-sm-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{  __('mfw-accounts::ui.turnover') }} : Счетоводна година 01 януари - 31 декември</h6>
                <a class="btn btn-sm btn-link p-0" data-bs-toggle="collapse" href="#collapse-accounting" role="button">
                    <i class="fa fa-chevron-up"></i>
                </a>
            </div>
            <div class="collapse show" id="collapse-accounting">
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('mfw-accounts::ui.year') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.turnoverHT') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.VAT') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.common') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.previous') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.difference') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.evolution') }}</th>
                            </tr>
                        </thead>
                        @php
                        $totalAmount = $turnover->sum('amount');
                        $totalHT = $turnover->sum('vat');
                        @endphp

                        <tbody>
                            @foreach($turnover as $key => $value)
                            <?php $variation = $loop->last ? 0 : (($value->amount - $turnover[$key+1]->amount)/$turnover[$key+1]->amount)*100; ?>
                            <tr>
                                <td>{{ $value->year }}</td>
                                <td class="text-end">{!! Prices::readableFormat(price: $value->amount, currency:'', stripZeros: true) !!}</td>
                                <td class="text-end">{!! Prices::readableFormat(price: $value->vat, currency:'', stripZeros: true) !!}</td>
                                <td class="text-end">{!! Prices::readableFormat(price: $value->amount+$value->vat, currency:'', stripZeros: true) !!}</td>
                                <td class="text-end">{!! $loop->last ? 0 : Prices::readableFormat(price: $turnover[$key+1]->amount, currency:'', stripZeros: true) !!}</td>
                                <td class="text-end">{!! $loop->last ? 0 : Prices::readableFormat(price: $value->amount - $turnover[$key+1]->amount, currency:'', stripZeros: true) !!}</td>
                                <td class="text-end {!! $variation == 0 ? '' : ( $variation < 1 ? 'text-danger':'text-success') !!}">
                                    {{ number_format($variation) }}%
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th class="text-end">{{ Prices::readableFormat(price: $totalAmount, currency:'', stripZeros: true) }}</th>
                                <th class="text-end">{{ Prices::readableFormat(price: $totalHT, currency:'', stripZeros: true) }}</th>
                                <th class="text-end">{{ Prices::readableFormat(price: $totalAmount+$totalHT, currency:'', stripZeros: true) }}</th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-6 col-sm-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{  __('mfw-accounts::ui.period') }} :
                        @if (request()->filled('date_operator'))
                        {{ (request()->date_operator == 'greater' && !$between_dates ? ' > ' : null) }}
                        {{ (request()->date_operator == 'less' && !$between_dates ? ' > ' : null) }}
                        {!! request()->filled('date') ? \MetaFramework\Accounts\Support\DateFormat::convert((string) request()->date, 'd/m/Y', 'd/m/Y') : null !!}
                        {!! ($between_dates && request()->filled('date2')) ? ' - '. \MetaFramework\Accounts\Support\DateFormat::convert((string) request()->date2, 'd/m/Y', 'd/m/Y') : null !!}
                        @else
                        01/11/{{ (date('Y')-1) }} - 31/10/{{ date('Y') }}
                        @endif
                    </h6>
                    <a class="btn btn-sm btn-link p-0" data-bs-toggle="collapse" href="#collapse-period" role="button">
                        <i class="fa fa-chevron-up"></i>
                    </a>
                </div>
            </div>
            <div class="collapse show" id="collapse-period">
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{$totals .' '. trans_choice('mfw-accounts::ui.Client',2) }}</th>
                                <th>{{ __('mfw-accounts::ui.Country') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.percentage') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.turnoverHT') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.VAT') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.common') }}</th>
                            </tr>
                        </thead>
                        @php
                        $totalAmount = $clients['turnoverByGroup']->sum('amount');
                        $totalHT = $clients['turnoverByGroup']->sum('vat');
                        @endphp
                        <tbody>
                            @foreach($clients['bygroup'] as $key => $value)

                            <tr>
                                @if($loop->first)
                                <td rowspan="{{ count($clients['bygroup']) }}">
                                    <canvas class="canvasDoughnut" height="140" width="140" style="margin: 15px 10px 10px 0"></canvas>
                                </td>
                                @endif
                                <td>
                                    <p class="mb-0">
                                        <i class="fa fa-square" style="color:{{ $backgroundColor[$i] }};"></i>
                                        {!! array_key_exists($key, $clients['named_countries']) ? $clients['named_countries'][$key] : __('mfw-accounts::ui.country_unknown') !!}
                                    </p>
                                </td>
                                <td class="text-end">{!! number_format(($value*100)/$totals, 2) !!}%</td>
                                <td class="text-end">{!! Prices::readableFormat(price: $clients['turnoverByGroup'][$key]['amount'], currency:'', stripZeros: true) !!}</td>
                                <td class="text-end">{!! Prices::readableFormat(price: $clients['turnoverByGroup'][$key]['vat'], currency:'', stripZeros: true) !!}</td>
                                <td class="text-end">{!! Prices::readableFormat(price: $clients['turnoverByGroup'][$key]['amount']+$clients['turnoverByGroup'][$key]['vat'], currency:'', stripZeros: true) !!}</td>
                                <?php ++$i; if ($i==$ccolors) { $i=0; }?>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3"></td>
                                <th class="text-end">100%</th>
                                <th class="text-end">{{ Prices::readableFormat(price: $totalAmount, currency:'', stripZeros: true) }}</th>
                                <th class="text-end">{{ Prices::readableFormat(price: $totalHT, currency:'', stripZeros: true) }}</th>
                                <th class="text-end">{{ Prices::readableFormat(price: $totalAmount+$totalHT, currency:'', stripZeros: true) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-sm-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{  __('mfw-accounts::ui.turnoverExport') }}</h6>
                <a class="btn btn-sm btn-link p-0" data-bs-toggle="collapse" href="#collapse-export" role="button">
                    <i class="fa fa-chevron-up"></i>
                </a>
            </div>
            <div class="collapse show" id="collapse-export">
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>{{ __('mfw-accounts::ui.year') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.turnoverHT') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.VAT') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.common') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.previous') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.difference') }}</th>
                                <th class="text-end">{{ __('mfw-accounts::ui.evolution') }}</th>
                            </tr>
                        </thead>
                        @php
                        $totalAmount = $turnoverExport->sum('amount');
                        $totalHT = $turnoverExport->sum('vat');
                        @endphp

                        <tbody>
                            @foreach($turnoverExport as $key => $value)
                            <?php $variation = $loop->last ? 0 : (($value->amount - $turnoverExport[$key+1]->amount)/$turnoverExport[$key+1]->amount)*100; ?>
                            <tr>
                                <td>{{ $value->year }}</td>
                                <td class="text-end">{!! Prices::readableFormat(price: $value->amount, currency:'', stripZeros: true) !!}</td>
                                <td class="text-end">{!! Prices::readableFormat(price: $value->vat, currency:'', stripZeros: true) !!}</td>
                                <td class="text-end">{!! Prices::readableFormat(price: $value->amount+$value->vat, currency:'', stripZeros: true) !!}</td>
                                <td class="text-end">{!! $loop->last ? 0 : Prices::readableFormat(price: $turnoverExport[$key+1]->amount, currency:'', stripZeros: true) !!}</td>
                                <td class="text-end">{!! $loop->last ? 0 : Prices::readableFormat(price: $value->amount - $turnoverExport[$key+1]->amount, currency:'', stripZeros: true) !!}</td>
                                <td class="text-end {!! $variation == 0 ? '' : ( $variation < 1 ? 'text-danger':'text-success') !!}">
                                    {{ number_format($variation) }}%
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th></th>
                                <th class="text-end">{{ Prices::readableFormat(price: $totalAmount, currency:'', stripZeros: true) }}</th>
                                <th class="text-end">{{ Prices::readableFormat(price: $totalHT, currency:'', stripZeros: true) }}</th>
                                <th class="text-end">{{ Prices::readableFormat(price: $totalAmount+$totalHT, currency:'', stripZeros: true) }}</th>
                                <th colspan="3"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
@push('js')

<script src="{!! asset('vendors/Chart.js/dist/Chart.min.js') !!}"></script>
@include('mfw-accounts::clients.charts')
<script src="{!! asset('vendor/mfw-accounts/js/filters.js') !!}"></script>
@endpush


