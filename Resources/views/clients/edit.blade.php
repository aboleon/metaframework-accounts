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
    @php
        $hasCompany = (bool) old('is_company', !empty($data->business));
        $showCompanyAgentsTab = $hasCompany && $data && $data->exists;
        $isSeller = (bool) old('business.is_seller', $data->business->is_seller ?? false);
    @endphp

    <div class="bg-body-tertiary rounded p-4 shadow">
        <x-mfw-support::response-messages />

        <form class="form" method="post" action="{{ route('mfw-accounts.ajax') }}"
            data-ajax="{{ route('mfw-accounts.ajax') }}" autocomplete='off' id="account-client-form"
            data-edit-url="{{ route('mfw-accounts.clients.edit', ['client' => 'CLIENT_ID']) }}">
            @csrf
            <input type="hidden" name="object" value="Account">
            <input type="hidden" name="object_id" value="{{ $data->id ?? '' }}">
            <input type="hidden" name="action" value="update_client">

            <div class="stylish-tabs">
                <nav class="mb-4">
                    <div class="nav nav-tabs" id="account-client-tabs" role="tablist">
                        <button class="nav-link active" id="account-tab-info" data-bs-toggle="tab" data-bs-target="#account-pane-info"
                            type="button" role="tab" aria-controls="account-pane-info" aria-selected="true">
                            {{ __('mfw::mfw.infos') }}
                        </button>
                        <button class="nav-link" id="account-tab-address" data-bs-toggle="tab" data-bs-target="#account-pane-address"
                            type="button" role="tab" aria-controls="account-pane-address" aria-selected="false">
                            {{ __('mfw-accounts::ui.Adress') }}
                        </button>
                        <button class="nav-link{{ $hasCompany ? '' : ' d-none' }}" id="account-tab-company-info"
                            data-bs-toggle="tab" data-bs-target="#account-pane-company-info" type="button" role="tab"
                            aria-controls="account-pane-company-info" aria-selected="false" data-company-tab="info">
                            {{ __('mfw-accounts::ui.CompanyInfo') }}
                        </button>
                        <button class="nav-link{{ $showCompanyAgentsTab ? '' : ' d-none' }}" id="account-tab-company-agents"
                            data-bs-toggle="tab" data-bs-target="#account-pane-company-agents" type="button" role="tab"
                            aria-controls="account-pane-company-agents" aria-selected="false" data-company-tab="agents">
                            {{ __('mfw-accounts::ui.Agents') }}
                        </button>
                    </div>
                </nav>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="account-pane-info" role="tabpanel"
                        aria-labelledby="account-tab-info">
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

                            <div class="row mt-3" id="account-company-toggle">
                                <div class="col-12">
                                    <x-mfw-inputable::checkbox name="is_company" :label="__('mfw-accounts::ui.is_company')" :affected="$hasCompany ? 1 : 0"
                                        :switch="true" :randomize="false" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="account-pane-address" role="tabpanel"
                        aria-labelledby="account-tab-address">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="border rounded-4 bg-white p-4">
                                    <x-mfw-google-places::form :model="$address"
                                        label="{{ __('mfw-accounts::ui.Adress') }} (taper pour obtenir des resultats)" />

                                    <div class="row mt-3">
                                        <div class="col-sm-12">
                                            <x-mfw-inputable::textarea label="Complement d'adresse" height="80"
                                                name="mfw_google_places[complementary]" :value="old('mfw_google_places.complementary', $address->complementary ?? null)" />
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <x-mfw-inputable::checkbox name="manual_fix_address" :label="__('mfw-accounts::ui.manual_address_fix')" :randomize="false" />
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="tab-pane fade{{ $hasCompany ? '' : ' d-none' }}" id="account-pane-company-info"
                        role="tabpanel" aria-labelledby="account-tab-company-info" data-company-pane="info">
                        <div id="account-company-fields" class="{{ $hasCompany ? '' : ' d-none' }}">
                            <div class="row">
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
                                <div class="col-12 mt-3" id="account-seller-toggle-wrap">
                                    <x-mfw-inputable::checkbox name="business[is_seller]" :label="__('mfw-accounts::ui.is_seller')" :affected="$isSeller ? 1 : 0"
                                        :switch="true" :randomize="false" />
                                </div>
                                <div class="col-sm-6 mt-3{{ $hasCompany && $isSeller ? '' : ' d-none' }}" id="account-seller-slug-wrap">
                                    <x-mfw-inputable::input name="business[seller_slug]" :label="__('mfw-accounts::ui.SellerSlug')" :value="old('business.seller_slug', $data->business->seller_slug ?? null)" />
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($data && $data->exists)
                        <div class="tab-pane fade{{ $showCompanyAgentsTab ? '' : ' d-none' }}" id="account-pane-company-agents"
                            role="tabpanel" aria-labelledby="account-tab-company-agents" data-company-pane="agents">
                            @include('mfw-accounts::clients.partials.agents', ['data' => $data])
                        </div>
                    @endif
                </div>
            </div>

            <button type="submit" class="btn btn-info ajaxable mt-4">{!! $data && $data->exists ? __('ui.save') : __('ui.add') !!}</button>
        </form>
    </div>
@endsection

@push('js')
    <script src="{{ asset('vendor/mfw-accounts/js/clients/edit.js') }}"></script>
@endpush
