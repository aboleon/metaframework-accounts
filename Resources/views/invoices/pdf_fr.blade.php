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
        $invoiceDateDisplay = $data->invoice_date;
        $datePaidDisplay = $data->date_paid;
        $dateBeforeDisplay = $data->date_before;
    @endphp

    <div style="text-align: center;margin: 25px 0 50px 0">
        <img src="{!! asset('Projects/' . config('app.project') . '/media/logo_pdf.png') !!}"
             alt="{{ config('app.project') }}" style="margin: 0 auto"/>
    </div>
    <table class="nob">
        <tr>
        <tr>
            <th style="text-align: left;vertical-align: top;width: 100px">{!! ucfirst(__('mfw-accounts::ui.BillingTo')) !!}
                :
            </th>
            <td>
                @if ($isCompany && $client->business?->name)
                    {{ $client->business->name }}
                @else
                    {{ $client->first_name . ' ' . $client->last_name }}
                @endif
                <br>
            </td>
            <td style="width: 10%"></td>
            <th style="text-align: left;vertical-align: top;width: 100px">{!! ucfirst(trans_choice('mfw-accounts::ui.provider', 1)) !!}
                :
            </th>
            <td style="vertical-align: top !important;">{!! $company->data->first()->name !!}</td>
        </tr>
        <tr>
            <th style="text-align: left;">{!! __('mfw-accounts::ui.Adress') !!} :</th>
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
                        , {{ \MetaFramework\GooglePlaces\Accessors\Country::getCountryNameByCodeAndLocale($billingAddress->country_code,$data->pdf_locale) }}
                    @endif
                @endif
            </td>
            <td></td>
            <th style="text-align: left;">{!! __('mfw-accounts::ui.Adress') !!} :</th>
            <td>{!! $company->data->first()->adresse !!}</td>
        </tr>
        <tr>
        <tr>

            <th style="text-align: left;">
                @if ($client->business)
                    SIRET :
                @endif
            </th>
            <td>
                {{ $client->business?->reg_number }}
            </td>

            <td></td>
            <th style="text-align: left;">BULSTAT :</th>
            <td>{!! $company->EIN !!}</td>
        </tr>

        <tr>
            <th style="text-align: left;">
                @if ($client->business?->vat_number)
                    TVA Intra :
                @endif
            </th>
            <td>
                {{ $client->business?->vat_number }}
            </td>
            <td></td>
            <th style="text-align: left;">
                TVA Intra :
            </th>
            <td>
                {{ $company->VAT }}
            </td>
        </tr>

    </table>


    <br>
    <br>
    <br>

    <table class="nob">
        <tr>
            <th style="font-size: 18px;padding: 0; text-align: left">
                {!! $docTypeLabel !!}
                N° {{ $data->docType->inventory . sprintf('%09d', $data->document_id) }}
            </th>
            <td style="text-align: right;font-size: 16px;">
                {!! __('mfw-accounts::ui.Date') . ' : ' . ($invoiceDateDisplay ?? '') !!}
            </td>
        </tr>
    </table>

    <br>

    <table class="table-responsive prestations table">
        <thead>
        <tr>
            <th style="background-color: #eeeeee !important;padding:6px">{!! __('mfw-accounts::ui.Prestation') !!}</th>
            <th style="text-align:right;padding:6px;width:80px;">{!! __('mfw-accounts::ui.Price') !!}</th>
            <th style="text-align: center;padding:6px">{!! __('mfw-accounts::ui.ShortQuantity') !!}</th>
            <th style="text-align:right;padding:6px">{!! __('mfw-accounts::ui.VAT') !!}</th>
            <th style="text-align:right;padding:6px">Taux</th>
            <th style="text-align:right;padding:6px">{!! __('mfw-accounts::ui.Amount') !!}</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $cur = $data->currencyType->sign; ?>
        @php
            $isDetailed = !$data->details->isEmpty();
        @endphp
        @if ($isDetailed)

            @foreach ($data->details as $key => $item)
                    <?php

                    $amount[] = $item->amount * $item->quantity;
                    $itemat[] = $item->vat / 100;

                    ?>
                <tr>
                    <td style="padding: 6px 6px 6px 0">{!! nl2br($item->content) !!}</td>
                    <td style="padding:6px;text-align:right;">{!! str_replace('.00', '', number_format($item->amount, 2, '.', ' ')) !!}</td>
                    <td style="padding:6px;text-align: center;">{!! $item->quantity !!}</td>
                    <td style="padding:6px;text-align:right;">{!! str_replace('.00', '', number_format($item->vat / 100, 2, '.', ' ')) !!}</td>
                    <td style="padding:6px;text-align:right;">{!! VatAccessor::rate($item->vat_id) !!}</td>
                    <td style="padding:6px;text-align:right;">{!! str_replace('.00', '', number_format($item->amount * $item->quantity + $item->vat / 100, 2, '.', ' ')) !!}</td>
                </tr>
            @endforeach


                <?php

                $amount = array_sum($amount);
                $itemat = array_sum($itemat);

                ?>
        @else
            <tr>
                <td>{!! nl2br($data->content) !!}</td>
                <td style="padding:6px;text-align:right;">{!! Prices::readableFormat(price: $data->amount + 0, currency:'', stripZeros: true) !!}</td>
                <td style="padding:6px;text-align: center;">{!! $data->quantity !!}</td>
                <td style="padding:6px;text-align:right;">{!! Prices::readableFormat(price: $data->vat + 0, currency:'', stripZeros: true) !!}</td>
                <td style="padding:6px;text-align:right;">{!! VatAccessor::rate($data->vat_id) !!}</td>
                <td style="padding:6px;text-align:right;">{!! Prices::readableFormat(price: $data->amount * $data->quantity + $data->vat, currency:'', stripZeros: true) . ' ' . $cur !!} </td>
            </tr>

                <?php

                $amount = $data->amount * $data->quantity;
                $itemat = $data->vat;

                ?>

        @endif
        @php
            if ($isDetailed) {
                $total = $amount + $itemat;
                $amountDisplay = str_replace('.00', '', number_format($amount, 2, '.', ' '));
                $itematDisplay = str_replace('.00', '', number_format($itemat, 2, '.', ' '));
                $totalDisplay = str_replace('.00', '', number_format($total, 2, '.', ' '));
            } else {
                $total = $amount + $itemat;
                $amountDisplay = Prices::readableFormat(price: $amount, currency:'', stripZeros: true);
                $itematDisplay = Prices::readableFormat(price: $itemat, currency:'', stripZeros: true);
                $totalDisplay = Prices::readableFormat(price: $total, currency:'', stripZeros: true);
            }
        @endphp
        </tbody>
    </table>

    <br><br>

    <table class="nob">
        <tr>
            <td>


                @if (empty($data->paid))
                    Paiement
                    {{ !empty($dateBeforeDisplay) ? ' avant le ' . $dateBeforeDisplay : ' à la réception' }}
                @else
                    Facture payée.<br><br>
                    @if (!is_null($datePaidDisplay))
                        Paiement effectué
                        le {{ $datePaidDisplay . ' ' . __('mfw-accounts::ui.pay_mean_' . $data->pay_mean) }}
                    @endif
                @endif
                <br>
                Facture en {{ $data->currencyType->name }}
                <br><br>

                <p>{!! nl2br($data->notes) !!}</p>
            </td>
            <td>

                <table class="table-condensed prestations table">

                    <tr>
                        <th class="nob" style="text-align: left;padding: 6px"><strong>Montant HT</strong></th>
                        <td class="nob" style="text-align: right">{!! $amountDisplay . ' ' . $cur !!}</td>
                    </tr>
                    <tr>
                        <th style="text-align: left;padding: 6px">TVA</th>
                        <td style="text-align:right;">{!! $itematDisplay . ' ' . $cur !!}</td>
                    </tr>
                    <tr>
                        <th style="border-bottom:none !important;text-align: left;padding: 6px">Total</th>
                        <td class="nob" style="text-align: right"><strong>{!! $totalDisplay . ' ' . $cur !!}</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

@stop


