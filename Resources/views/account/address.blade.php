@extends('mfw-accounts::layouts.backend')

@section('header')
    <h4>
        Adresse<span class="text-secondary"> | {{ $account->names() }}</span>
    </h4>
    @php
        $url = $redirect_to ?? route('panel.accounts.edit', $account);
    @endphp
    <a class="btn btn-secondary ms-2" href="{{ $url }}#address-tabpane"><i class="fa fa-solid fa-bars"></i>Adresses &
        Contacts</a>
@endsection

@section('content')
    @php
        $error = $errors->any();
    @endphp

    <div class="bg-body-tertiary rounded p-4 shadow">
        <x-mfw-support::response-messages />

        @if ($errors->any())
            @foreach ($errors->all() as $_error)
                <div class="alert alert-danger">{{ $_error }}</div>
            @endforeach
        @endif

        <div class="row m-3">
            <div class="col">
                <form method="post" action="{{ $route }}" novalidate>
                    @if (isset($method))
                        @method($method)
                    @endif
                    @csrf

                    @if ($redirect_to)
                        <input type="hidden" name="custom_redirect" value="{{ $redirect_to }}">
                    @endif

                    <fieldset class="position-relative">
                        <legend class="d-flex justify-content-between align-items-end">
                            <span>
                                Adresse<span class="text-secondary"> | {{ $account->names() }}</span>
                            </span>

                            @php
                                $url = $redirect_to ?? route('panel.accounts.edit', $account);
                            @endphp
                            <x-mfw::notice
                                message="<a href='{{ $url }}#address-tabpane'>Adresses & Contacts</a>" />
                        </legend>

                        <x-mfw::input name="mfw_google_places[name]" :value="$error ? old('mfw_google_places.name') : $data->name" :label="__('mfw.title')" />

                        <div class="mfw-line-separator mb-5 mt-3 pb-5">
                            <x-mfw::input label="Raison sociale" name="mfw_google_places[company]" :value="$error ? old('mfw_google_places.company') : $data->company" />
                        </div>


                        <x-mfw-google-places::form :model="$data"
                            label="Adresse géolocalisée (taper pour obtenir des résultats) *" />

                        <div class="row">
                            <div class="col-sm-3">
                                <x-mfw::input name="mfw_google_places.cedex" :value="$error ? old('mfw_google_places.cedex') : $data->cedex" label="Cedex" />
                            </div>
                            <div class="col-sm-9">
                                <x-mfw::textarea label="Complement d'adresse" height="100"
                                    name="mfw_google_places[complementary]" :value="$error ? old('mfw_google_places.complementary') : $data->complementary" />
                            </div>
                        </div>
                    </fieldset>
                    <fieldset class="mt-4">
                        <div class="fw-bold text-dark">
                            <x-mfw::checkbox name="mfw_google_places[billing]" value="1"
                                label="Il s'agit de l'adresse de facturation" :affected="collect($data->billing)" />
                        </div>
                    </fieldset>
                    <div class="mt-5">
                        <x-mfw::btn-save />
                    </div>
                </form>
            </div>
        </div>
    </div>

@stop
