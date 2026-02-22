@extends('mfw-accounts::layouts.backend')

@section('header')
    <h4>
        {{ $data->id ? __('mfw-sellable.vat.label'). " " . $data->rate .' %' : __('mfw-sellable.vat.new') }}
    </h4>
    <div class="d-flex align-items-center" id="topbar-actions">
        <x-mfw::btn-index :route="route('mfw.vat.index')" />
        <div class="separator"></div>
    </div>

@endsection

@section('content')

    @php
        $error = $errors->any();
    @endphp

    <div class="shadow p-3 mb-5 bg-body-tertiary rounded">
        <div class="row m-3">
            <div class="col">

                <x-mfw-support::response-messages/>
                <x-mfw-support::validation-errors/>

                @php
                    $error = $errors->any();
                @endphp

                <form method="post" action="{{ $route }}" id="mfw-form">
                    @csrf
                    @if($data->id)
                        @method('put')
                    @endif

                    <div class="row mb-4">
                        <div class="col-12">
                            <h4 class="mt-3">TVA</h4>
                            <div class="row">
                                <div class="col-lg-6">
                                    <x-mfw-inputable::number name="vat[rate]" step="0.01"
                                                         :value="$error ? old('vat.rate') : $data->rate"
                                                         :label="__('mfw-sellable.vat.percent')"/>
                                </div>
                                <div class="col-lg-6">
                                    <x-mfw-inputable::radio :values="[0 => 'Non',1 => 'Oui']"
                                                        :affected="$error ? old('vat.default') : ($data->default ?: 0)"
                                                        name="vat[default]"
                                                        :label="__('mfw-sellable.vat.is_this_default')"
                                                        :nullable="false"/>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="mt-5 main-save">
                        <x-mfw::btn-save/>
                    </div>
                </form>

            </div>
        </div>
    </div>

    @push('js')
        <script src="{{ asset('vendor/mfw/js/published_status.js') }}"></script>
    @endpush

@endsection
