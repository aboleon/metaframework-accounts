@extends('layouts.panel')

@section('header')
    <h4>
        {{ trans_choice('mfw-accounts::ui.PayDocTypes', 1) }}
    </h4>
    <a class="btn btn-secondary ms-2" href="{{ route('mfw-accounts.cashflow-doctypes.index') }}"><i
            class="fa fa-solid fa-bars"></i>{{ __('ui.display.list') }}</a>
@endsection

@section('content')

    <div class="shadow p-4 bg-body-tertiary rounded">
        <x-mfw-support::response-messages/>

        <form class="form" method="post" action="{{ $route }}">
            @csrf
            @if ($data->id)
                @method('PUT')
            @endif

            <x-mfw::translatable-tabs :model="$data->id ? $data : new MetaFramework\Accounts\Models\CashflowDocTypes()"/>

            <x-mfw-inputable::select :values="\MetaFramework\Accounts\Enum\DocTypeIncrementationEnum::translations()"
                                     :label="__('mfw-accounts::ui.numerotation')"
                                     name="numerotation"
                                     :nullable="false"
                                     :affected="$data->numerotation"/>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">{!! __('mfw-accounts::ui.save') !!}</button>
            </div>
        </form>
    </div>

@endsection


