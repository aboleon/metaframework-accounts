@extends('mfw-accounts::layouts.backend')

@section('header')
    <h4>
        {{ $data->name ?: '-' }}
        <span class="text-secondary"> | <a
                href="{{ route('mfw-accounts.pay-mean-channels.index', ['payMean' => $data->pay_mean_id]) }}">{{ $data->master->name }}</a></span>
    </h4>
    <a class="btn btn-secondary ms-2" href="{{ route('mfw-accounts.pay-means.index') }}"><i
            class="fa fa-solid fa-bars"></i>{{ trans_choice('mfw-accounts::ui.payMeans.label', 2) }}</a>
@endsection

@section('content')

    <div class="shadow p-4 bg-body-tertiary rounded">
        <x-mfw-support::response-messages />

        <form method="post" autocomplete=off>
            @csrf

            <x-mfw-translatables :model="$data" datakey="data" :pluck="['name']" />

            <br>

            <h3>{{ __('mfw-accounts::ui.BankAccount') }}</h3>
            <hr style="margin: 0">
            @if ($BankAccounts->isNotEmpty())
                <select class="form-control" name="bank_account_id">
                    @foreach ($BankAccounts as $item)
                        <option value="{{ $item->id }}" {{ $data->bank_account_id == $item->id ? ' selected' : '' }}>
                            {{ $item->translation->bank . ' / ' . $item->IBAN }}
                        </option>
                    @endforeach
                </select>
            @else
                <x-mfw-support::alert type="warning" :message="__('ui.no_records')" />
            @endif
            <p>
                <button class="btn btn-success" style="margin-top: 14px;">{!! __('ui.save') !!}</button>
            </p>
        </form>
    </div>

@stop


