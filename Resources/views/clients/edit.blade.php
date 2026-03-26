@extends('mfw-accounts::layouts.backend')

@section('header')
    <h4>
        {{ __('mfw-accounts::ui.ClientAccount') }}
    </h4>
    @if ($data && $data->exists)
        <a class="btn btn-secondary ms-2" href="{{ route('mfw-accounts.clients.dashboard', $data) }}"><i
                class="fa fa-solid fa-bars"></i>{{ __('mfw-accounts::ui.AccountActivity') }}</a>
    @endif
@endsection

@section('content')
    <div class="bg-body-tertiary rounded p-4 shadow">
        <x-mfw-support::response-messages />


        <div class="row">
            <div class="col-md-7">
                <form class="form" method="post" action="{{ route('mfw-accounts.ajax') }}"
                    data-ajax="{{ route('mfw-accounts.ajax') }}" autocomplete='off' id="account-client-form"
                    data-edit-url="{{ route('mfw-accounts.clients.edit', ['client' => 'CLIENT_ID']) }}">
                    @csrf
                    <input type="hidden" name="object" value="Account">
                    <input type="hidden" name="object_id" value="{{ $data->id ?? '' }}">
                    <input type="hidden" name="action" value="update_client">

                    <div id="account_identity">

                        <div class="row mb-3">
                            <div class="col-md-6">

                                <x-mfw-inputable::radio name="civ" class="m-0" :label="__('mfw-accounts::ui.CIV')" :values="[
                                    'C' => __('mfw-accounts::ui.civ.Mlle'),
                                    'B' => __('mfw-accounts::ui.civ.Mme'),
                                    'A' => __('mfw-accounts::ui.civ.M'),
                                ]"
                                    :affected="old('civ', $data->civ ?: config('mfw-accounts.client.default_civility', 'A'))" />
                            </div>
                            <div class="col-md-6">
                                <x-mfw-inputable::select :label="__('ui.lg')" name="locale" :affected="$data->locale ?: app()->getLocale()"
                                    :values="MetaFramework\Accessors\Locale::localesAsSelectable()" />
                            </div>
                        </div>

                        @if ($data && $data->exists)
                            <x-mfw::translatable-tabs :model="$data" :pluck="['first_name', 'last_name']" />
                        @else
                            <div class="row">
                                <div class="col-sm-6">
                                    <x-mfw-inputable::input name="first_name" :label="__('mfw-accounts::ui.FirstName')" :value="old('first_name', $data->first_name ?? null)" />
                                </div>

                                <div class="col-sm-6">
                                    <x-mfw-inputable::input name="last_name" :label="__('mfw-accounts::ui.LastName')" :value="old('last_name', $data->last_name ?? null)" />
                                </div>
                            </div>
                        @endif

                        <div class="row mt-3">
                            <div class="col-sm-6">
                                <x-mfw-inputable::input name="phone" :label="__('mfw-accounts::ui.phone')" :value="old('phone', $data->phone ?? null)" />
                            </div>
                            <div class="col-sm-6">
                                <x-mfw-inputable::input name="email" type="email" label="e-mail" :value="old('email', $data->email ?? null)" />
                            </div>
                        </div>

                        @php
                            $hasCompany = (bool) old('is_company', !empty($data->business));
                        @endphp
                        <div class="row mt-3" id="account-company-toggle">
                            <div class="col-12">
                                <x-mfw-inputable::checkbox name="is_company" :label="__('mfw-accounts::ui.is_company')" :affected="$hasCompany ? 1 : 0"
                                    :switch="true" :randomize="false" />
                            </div>
                        </div>
                        <div class="row mt-3" id="account-company-fields" style="{{ $hasCompany ? '' : 'display:none' }}">
                            <div class="col-12">
                                @if ($data && $data->exists)
                                    <x-mfw::translatable-tabs datakey="business" :model="$data->business ?: new \MetaFramework\Accounts\Models\AccountBusiness()" />
                                @else
                                    <x-mfw-inputable::input name="business[name]" :label="__('mfw-accounts::ui.CompanyName')" :value="old('business.name', $data->business->name ?? null)" />
                                @endif
                            </div>
                            <div class="col-sm-6 mt-3">
                                <x-mfw-inputable::input name="business[vat_number]" :label="'N° ' . __('mfw-accounts::ui.VAT')" :value="old('business.vat_number', $data->business->vat_number ?? null)" />
                            </div>
                            <div class="col-sm-6 mt-3">
                                <x-mfw-inputable::input name="business[reg_number]" :label="__('mfw-accounts::ui.CompanyRegNumber')" :value="old('business.reg_number', $data->business->reg_number ?? null)" />
                            </div>
                        </div>

                    </div>
                    <div class="my-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <legend class="mb-0">{!! __('mfw-accounts::ui.Adress') !!}</legend>
                            <button class="btn" type="button" data-section-toggle="#account-address-collapse" aria-expanded="true"
                                aria-controls="account-address-collapse">
                                <i class="bi bi-chevron-down" data-collapse-icon></i>
                            </button>
                        </div>
                        <div id="account-address-collapse">
                            <x-mfw-google-places::form :model="$address"
                                label="{{ __('mfw-accounts::ui.Adress') }} (taper pour obtenir des resultats)" />

                            <div class="row mt-3">
                                <div class="col-sm-12">
                                    <x-mfw-inputable::textarea label="Complement d'adresse" height="80"
                                        name="mfw_google_places[complementary]" :value="old('mfw_google_places.complementary', $address->complementary ?? null)" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <x-mfw-inputable::checkbox name="manual_fix_address" :label="__('mfw-accounts::ui.manual_address_fix')" :randomize="false" />
                    <button type="submit" class="btn btn-info ajaxable mt-4">{!! $data && $data->exists ? __('ui.save') : __('ui.add') !!}</button>

                </form>

                @if ($data && $data->exists)
                    @include('mfw-accounts::clients.partials.agents', ['data' => $data])
                @endif
            </div>

            <div class="col-md-5">
                @if ($data && $data->exists)
                    <div id="account-address-corrections-collapse">
                        <x-translatable-google-address-corrections :model="$address" />
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('css')
    <style>
        #account-address-collapse {
            border: 2px dashed rgb(204, 204, 204);
            border-radius: 17px;
            padding: 26px;
            background: white;
        }

        button[data-section-toggle="#account-address-collapse"],
        button[data-section-toggle="#account-agents-collapse"] {
            font-size: 34px;
            margin-top: 28px;
            margin-bottom: -14px;
            margin-left: 16px;
            border: 0;
            box-shadow: none;
        }

        button[data-section-toggle="#account-address-collapse"]:active,
        button[data-section-toggle="#account-address-collapse"]:focus,
        button[data-section-toggle="#account-agents-collapse"]:active,
        button[data-section-toggle="#account-agents-collapse"]:focus {
            border: 0;
            box-shadow: none;
        }
    </style>
@endpush

@push('js')
    <script src="{{ asset('vendor/mfw-accounts/js/clients/edit.js') }}"></script>
@endpush


