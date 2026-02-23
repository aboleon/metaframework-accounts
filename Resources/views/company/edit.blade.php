@extends('mfw-accounts::layouts.backend')

@section('header')
    <h4>
        {{ __('mfw-accounts::ui.CompanyInfo') }}
    </h4>
@endsection

@section('content')

    <div class="shadow p-4 bg-body-tertiary rounded">
        <x-mfw-support::response-messages/>

        <form class="form" method="post" action="{{ route('mfw-accounts.company.update') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="id" value="{{ $data->id ?? 1 }}"/>

            <x-mfw-translatables :model="$data" datakey="locales" :pluck="['name', 'owner', 'adresse', 'licence']"/>

            <div class="row">
                <div class="col-sm-4">
                    <x-mfw-inputable::input
                        name="phone"
                        :label="__('mfw-accounts::ui.phone')"
                        :value="$data->phone"
                    />
                </div>

                <div class="col-sm-4">
                    <x-mfw-inputable::input
                        name="email"
                        type="email"
                        label="e-mail"
                        :value="$data->email"
                    />
                </div>

                <div class="col-sm-4">
                    <x-mfw-inputable::input
                        name="website"
                        :label="__('mfw-accounts::ui.CompanyWebsite')"
                        :value="$data->website"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-4">
                    <x-mfw-inputable::input
                        name="EIN"
                        :label="__('mfw-accounts::ui.EIN')"
                        :value="$data->EIN"
                    />
                </div>
                <div class="col-sm-4">
                    <x-mfw-inputable::input
                        name="VAT"
                        :label="'N° ' . __('mfw-accounts::ui.VAT')"
                        :value="$data->VAT"
                    />
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <label>{!! __('mfw-accounts::ui.CompanyBilan', ['format' => __('mfw-accounts::ui.ddmm')]) !!}</label>
                    <div class="row">
                        <div class="col-sm-6">
                            <x-mfw-inputable::input
                                name="bilan_start"
                                :label="__('mfw-accounts::ui.CompanyBilanStart')"
                                :value="$data->bilan_start"
                                :params="['placeholder' => __('mfw-accounts::ui.ddmm')]"
                            />
                        </div>
                        <div class="col-sm-6">
                            <x-mfw-inputable::input
                                name="bilan_end"
                                :label="__('mfw-accounts::ui.CompanyBilanEnd')"
                                :value="$data->bilan_end"
                                :params="['placeholder' => __('mfw-accounts::ui.ddmm')]"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">{!! __('mfw-accounts::ui.save') !!}</button>
        </form>
    </div>

@endsection


