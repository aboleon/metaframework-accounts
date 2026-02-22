@php use MetaFramework\Accessors\Prices;use MetaFramework\Accounts\Accessors\VatAccessor; @endphp
@extends('mfw-accounts::pdf.default')
@section('content')
    @php
        $company = $company->get('company');
        $client = $data->client;
        $billingAddress = $client->address->sortByDesc('billing')->first();
        $isCompany = $client->isCompany();
        $docTypeLabel =
            $data->docType?->translation('name', $data->pdf_locale ?? app()->getLocale()) ??
            ($data->docType?->name ?? __('mfw-accounts::ui.docType' . $data->doc_type));
    @endphp

    <div class="text-center" style="margin: 25px 0 50px 0">
        <img src="{!! asset('Projects/' . config('app.project') . '/media/logo_pdf.png') !!}" alt="{{ config('app.project') }}" style="margin: 0 auto" />
    </div>
    <table>
        <tr>
            <td width="50%">
                <table class="nob">
                    <tr>
                        <th>
                            {!! __('mfw-accounts::ui.BillingTo') !!} :
                        </th>
                        <td>
                            @if ($isCompany)
                                {{ $client->business->name }}
                            @else
                                {{ $client->first_name . ' ' . $client->last_name }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>
                            {!! __('mfw-accounts::ui.Adress') !!} :
                        </th>
                        <td>
                            @if ($billingAddress)
                                @if ($billingAddress->route)
                                    {{ $billingAddress->street_number }} {{ $billingAddress->route }}
                                @endif
                                @if ($billingAddress->complementary)
                                    <br>{{ $billingAddress->complementary }}
                                @endif
                                <br>
                                {{ $billingAddress->postal_code }} {{ $billingAddress->locality }}
                                @if ($billingAddress->country_code)
                                    ,
                                        {{ \MetaFramework\GooglePlaces\Accessors\Country::getCountryNameByCodeAndLocale($billingAddress->country_code,$data->pdf_locale) }}
                                @endif
                            @endif
                        </td>
                    </tr>
                    @if ($isCompany)
                        <tr>
                            <th class="up">М.О.Л. :</th>
                            <td>{{ $client->first_name . ' ' . $client->last_name }}</td>
                        </tr>
                        <tr>
                            <th class="up">ЕИК/Булстат :</th>
                            <td>{{ $client->business->reg_number }}</td>
                        </tr>
                        <tr>
                            <th class="up">ДДС N° :</th>
                            <td>{{ $client->business->vat_number }}</td>
                        </tr>
                    @endif
                </table>
            </td>
            <td>
                <table class="nob">
                    <tr>
                        <th>{!! trans_choice('mfw-accounts::ui.provider', 1) !!} :</th>
                        <td>{!! $company->data->first()->name !!}</td>
                    </tr>
                    <tr>
                        <th>{!! __('mfw-accounts::ui.Adress') !!} :</th>
                        <td>{!! $company->data->first()->siege !!}</td>
                    </tr>
                    <tr>
                        <th class="up">М.О.Л. :</th>
                        <td>{!! $company->data->first()->owner !!}</td>
                    </tr>
                    <tr>
                        <th class="up">Булстат :</th>
                        <td>{!! $company->EIN !!}</td>
                    </tr>
                    <tr>
                        <th class="up">ДДС N° :</th>
                        <td>{!! $company->VAT !!}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <h1>{!! $docTypeLabel !!}
        N° {{ $data->docType->inventory . sprintf('%09d', $data->document_id) }}</h1>

    @if (!empty($data->attached_to))
        <div class="sub">
            {{ __('mfw-accounts::ui.attachedTo') . ' ' . __('mfw-accounts::ui.docType1') . ' N° 0' . sprintf('%09d', $data->attachedTo->document_id) . ' ' . __('mfw-accounts::ui.from') . ' ' . \App\Helpers\Helpers::kvasir_dateFormat($data->attachedTo->invoice_date, 'Y-m-d', 'd.m.Y') }}
        </div>
    @endif

    @php
        $invoice_date = $data->invoice_date;
    @endphp

    <div class="row">
        <div class="col-6">
            <strong>Дата на издаване : </strong>{!! $invoice_date !!}
        </div>
        <div class="col-6 text-right">
            <strong>Дата на данъчно събитие : </strong>{!! $invoice_date !!}
        </div>
    </div>

    <br>
    <table class="table-responsive table">
        <thead>
            <tr>
                <th>{!! __('mfw-accounts::ui.Prestation') !!}</th>
                <th class="text-center">{!! __('mfw-accounts::ui.ShortQuantity') !!}</th>
                <th class="text-right">{!! __('mfw-accounts::ui.Price') !!}</th>
                <th class="up text-right">{!! __('mfw-accounts::ui.VAT') !!}</th>
                <th class="text-right">{!! __('mfw-accounts::ui.VATRate') !!}</th>
                <th class="text-right">{!! __('mfw-accounts::ui.Amount') !!}</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $cur = $data->currencyType->sign;
            ?>
            @php
                $isDetailed = !$data->details->isEmpty();
            @endphp
            @if ($isDetailed)

                @foreach ($data->details as $key => $item)
                    <?php
                    $amount[] = $item->amount * $item->quantity;
                    $itemat[] = $item->vat / 100;
                    $subtotal = $item->amount * $item->quantity;
                    ?>
                    <tr>
                        <td>{!! nl2br($item->content) !!}</td>
                        <td class="text-center">{!! $item->quantity !!}</td>
                        <td class="text-right">{!! str_replace('.00', '', number_format($item->amount, 2, '.', ' ')) . ' ' . $cur !!}</td>
                        <td class="text-right">{!! str_replace('.00', '', number_format($item->vat / 100, 2, '.', ' ')) . ' ' . $cur !!}</td>
                        <td class="text-right">{!! VatAccessor::rate($item->vat_id) !!}</td>
                        <td class="text-right">{!! str_replace('.00', '', number_format($item->amount * $item->quantity + $item->vat / 100, 2, '.', ' ')) .
                            ' ' .
                            $cur !!}</td>
                    </tr>
                @endforeach

                <?php
                $itemat = array_sum($itemat);
                $amount = array_sum($amount);
                ?>
            @else
                <tr>
                    <td>{!! $data->content !!}</td>
                    <td class="text-center">{!! $data->quantity !!}</td>
                    <td class="text-right">{!! Prices::readableFormat(price: $data->amount + 0, currency:'', stripZeros: true) . ' ' . $cur !!}</td>
                    <td class="text-right">{!! Prices::readableFormat(price: $data->vat + 0, currency:'', stripZeros: true) . ' ' . $cur !!}</td>
                    <td class="text-right">{!! VatAccessor::rate($data->vat_id) !!}</td>
                    <td class="text-right">{!! Prices::readableFormat(price: $data->amount + $data->vat, currency:'', stripZeros: true) . ' ' . $cur !!}</td>
                </tr>

                <?php
                $amount = $data->amount * $data->quantity;
                $itemat = $data->vat;
                ?>
            @endif
            @php
                if ($isDetailed) {
                    $total = str_replace('.00', '', number_format($amount + $itemat, 2, '.', ' '));
                    $amountDisplay = str_replace('.00', '', number_format($amount, 2, '.', ' '));
                    $itematDisplay = str_replace('.00', '', number_format($itemat, 2, '.', ' '));
                    $wordsAmount = number_format($amount + $itemat, 2, '.', '');
                } else {
                    $total = Prices::readableFormat(price: $amount + $itemat, currency:'', stripZeros: true);
                    $amountDisplay = Prices::readableFormat(price: $amount, currency:'', stripZeros: true);
                    $itematDisplay = Prices::readableFormat(price: $itemat, currency:'', stripZeros: true);
                    $wordsAmount = number_format($amount + $itemat, 2, '.', '');
                }
            @endphp
        </tbody>
    </table>

    <br>

    <div class="row">
        <div class="col-xs-7">
            <table class="nob">
                <tr>
                    <th class="nob" style="border-top:none !important;width:100px !important;">Словом :</th>

                    <?php

                    $w = explode('.', $wordsAmount);
                    $sw = null;
                    $n = intval($w[0]);

                    $j = new \App\Helpers\Numbers\Words\Locale\bg();

                    $sw .= $j->toWords($n, 'bg') . ' ' . (abs($n) > 1 ? 'лева' : (abs($n) == 0 ? 'лева' : 'лев'));

                    if (!empty($w[1])) {
                        $n = intval($w[1]);
                        if (!empty($n)) {
                            $sw .= ' и ' . $j->toWords($n, 'bg', ['cents' => true]) . ' ' . trans_choice('mfw-accounts::ui.cents', $n > 1 ? 2 : 1);
                        }
                    }

                    $b_id = empty($data->bank_account) ? 1 : $data->bank_account;
                    $b = $bank_accounts->where('account_id', $b_id)->first();
                    ?>
                    <td class="nob" style="border-top:none !important;">{{ $sw }}<br><br></td>
                </tr>
                <tr>
                    <th colspan="2">Основание за неначисляване на <span class="up">ДДС</span> :</th>
                </tr>
                <tr>
                    <td colspan="2">
                        @if ($data->doc_type > 4)
                            Член.86 ал.1 от ЗДДС
                        @else
                            ЗДДС, чл.86, ал.3 и във връзка с чл.21, ал.2. Обратно начисляване
                        @endif
                        <br><br>
                    </td>
                </tr>
                <tr>
                    <th>Метод за плащане :</th>
                    <td>{{ $data->payMean?->name }}</td>
                </tr>
                <tr>
                    <th>Банка :</th>
                    <td>{{ $b->bank }}</td>
                </tr>
                <tr>
                    <th class="up">BIC :</th>
                    <td>{{ $b->account->BIC }}</td>
                </tr>
                <tr>
                    <th class="up" style="border-bottom:none !important;">IBAN :</th>
                    <td class="nob">{{ $b->account->IBAN }}</td>
                </tr>
            </table>
        </div>

        <div class="col-xs-4 col-xs-offset-1">
            <table class="table-condensed table">
                <tr>
                    <th class="nob" style="border-top:none !important;">Сума по фактура :</th>
                    <td class="nob">{!! $amountDisplay . ' ' . $cur !!}</td>
                </tr>
                <tr>
                    <th>Данъчна основа :</th>
                    <td>{!! $amountDisplay . ' ' . $cur !!}</td>
                </tr>
                <tr>
                    <th>Начислен <span class="up">ДДС</span></th>
                    <td>{!! $itematDisplay . ' ' . $cur !!}</td>
                </tr>
                <tr>
                    <th style="border-bottom:none !important;">Всичко :</th>
                    <td class="nob">{!! $total . ' ' . $cur !!}</td>
                </tr>
            </table>
        </div>
    </div>
    <br><br><br>
    <div class="row">
        <div class="col-xs-5 col-xs-offset-7">
            Съставил : {!! $company->data->first()->owner !!}<br><br>
            Подпис : .......................................................
        </div>
    </div>
@stop
