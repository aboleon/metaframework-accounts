@extends('mfw-accounts::layouts.backend')

@section('header')
    <h4>
        {{ trans_choice('mfw-accounts::ui.Client', $total ) }}
    </h4>
    <x-mfw::btn-add :route="route('mfw-accounts.clients.create')"/>

@endsection


@section('content')
    @include('mfw-accounts::clients.filter')


    <table class="table table-striped table-bordered nowrap table-hover" cellspacing="0" width="100%">
        <thead>
        <tr>
            <td colspan="8"
                class="caption">{{ number_format($total, 0, ' ',' ')  . ' ' .trans_choice('mfw-accounts::ui.Client', $total) }}</td>
        </tr>
        @php
            $sortBy = request('sort_by', 'date_created');
            $sortOrder = request('sort_order', 'desc');
            $sortIcon = $sortOrder === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down';
        @endphp
        <tr>
            <th>#</th>
            <th width="300">
                <span class="d-flex justify-content-between align-items-center">
                    <span>{!! __('mfw-accounts::ui.Name') !!}</span>
                    @if($sortBy === 'name')
                        <i class="bi {{ $sortIcon }}"></i>
                    @endif
                </span>
            </th>
            <th>{!! trans_choice('mfw-accounts::ui.Location',1) !!}</th>
            <th width="100">{!! trans_choice('ui.geo.country',1) !!}</th>
            <th>e-mail</th>
            <th>
                <span class="d-flex justify-content-between align-items-center">
                    <span>{!! __('mfw-accounts::ui.client.created') !!}</span>
                    @if($sortBy === 'date_created')
                        <i class="bi {{ $sortIcon }}"></i>
                    @endif
                </span>
            </th>
            <th class="text-center">
                <span class="d-flex justify-content-between align-items-center">
                    <span>{{ Str::ucfirst(trans_choice('mfw-accounts::ui.invoice', 2)) }}</span>
                    @if($sortBy === 'invoice_count')
                        <i class="bi {{ $sortIcon }}"></i>
                    @endif
                </span>
            </th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        @foreach($clients as $item)
            @php
                $billingAddress = $item->address->first();
                $business = $item->business;
                $isCompany = $business !== null;
                $accountLocale = $item->locale ?: config('app.fallback_locale');
                $accountName = $isCompany
                    ? $business?->translation('name', $accountLocale)
                    : trim($item->translation('last_name', $accountLocale) . ' ' . $item->translation('first_name', $accountLocale));
                $nomail = str_contains($item->email,'random_');
                $canDelete = (int) ($item->invoices_count ?? 0) === 0 && (int) ($item->offers_count ?? 0) === 0;
            @endphp

            <tr>
                <td>{!! $item->id . ' - '. $item->account_id !!}</td>
                <td>
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('mfw-accounts.clients.dashboard', $item) }}">
                            @if($isCompany)
                                {{ $accountName }}
                            @else
                                <span
                                    class="uppercase">{!! $item->translation('last_name', $accountLocale)  !!}</span>
                                {!! $item->translation('first_name', $accountLocale) !!}
                            @endif
                        </a>
                        @if($isCompany)
                            <span class="label-company">{{ __('mfw-accounts::ui.Company') }}</span>
                        @endif
                    </div>
                </td>
                <td>
                    {{ $billingAddress?->locality }}
                </td>
                <td>
                    {{ \MetaFramework\GooglePlaces\Accessors\Country::getCountryNameByCode($billingAddress?->country_code) }}
                </td>
                <td class="{{ $nomail ? 'text-danger' : '' }}">
                    {{ $item->email }}
                </td>
                <td>
                    {{ $item->created_at?->format('d/m/Y') }}
                </td>
                <td class="text-center">
                    <a href="{{ route('mfw-accounts.clients.dashboard', $item) }}">
                        <span class="intbox{{ ($item->invoices_count ?? 0) ? '' : ' inactive' }}">
                            {{ $item->invoices_count ?? 0 }}
                        </span>
                    </a>
                </td>
                <td>
                    <ul class="mfw-actions">
                        <x-mfw::edit-link :route="route('mfw-accounts.clients.edit', $item->id)"/>
                        <li>
                            @include('mfw-accounts::clients.send_welcome_mail_action', [
                                'account' => $item,
                            ])
                        </li>
                        @if($canDelete)
                            <x-mfw::delete-modal-link reference="client_{{ $item->id }}"/>
                        @endif
                    </ul>
                    @if($canDelete)
                        <x-mfw::modal :route="route('mfw-accounts.clients.destroy', $item->id)"
                                      title="{{ __('mfw::mfw.delete') }}"
                                      question="{!! __('mfw::mfw.should_i_delete_record') !!} - <b>{{ $item->id . ' '. $accountName }}</b>"
                                      reference="destroy_client_{{ $item->id }}"/>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="text-center">
        {{ $clients->appends(request()->input())->links() }}
    </div>

@endsection

@push('js')
    <script src="{{ asset('vendor/mfw-accounts/js/account_welcome_from_modal.js') }}"></script>
@endpush


