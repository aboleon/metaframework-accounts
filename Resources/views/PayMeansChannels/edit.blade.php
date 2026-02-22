@extends('mfw-accounts::layouts.backend')

@section('header')
    <h4>
        {{ $data->translation->name ?? '-' }}
        <span class="text-secondary"> | <a href="{{ route('mfw-accounts.pay-mean-channels.index', ['payMean' => $data->pay_mean_id]) }}">{{ $data->master->name }}</a></span>
    </h4>
    <a class="btn btn-secondary ms-2" href="{{ route('mfw-accounts.pay-means.index') }}"><i class="fa fa-solid fa-bars"></i>{{ trans_choice('mfw-accounts::ui.payMeans.label', 2) }}</a>
@endsection

@section('content')

    <div class="shadow p-4 bg-body-tertiary rounded">
        <x-mfw-support::response-messages/>

        <form method="post" autocomplete=off>
            @csrf

            <ul class="nav nav-tabs" id="tabs">
                @foreach(Project::locales() as $locale)
                    <li{!! $locale == config('app.fallback_locale') ? ' class="active"' : null !!}><a href="#lang_{{ $locale }}">{{ __('kvasir::lang.'.$locale.'.label') }}</a>
                @endforeach
            </ul>
            <div class="tab-content">
                @foreach(Project::locales() as $locale)

                    <div id="lang_{{ $locale }}" class="tab-pane{{ $locale == config('app.fallback_locale') ? ' fade in active' : null }}">
                        <x-mfw-inputable::input
                            name="data.{{ $locale }}.name"
                            :label="__('ui.Name')"
                            :value="$data->translations ? $data->translations->where('lg', $locale)->first()->name : null"
                            :params="['placeholder' => __('ui.Name')]"
                        />
                        <x-mfw-inputable::textarea
                            name="data.{{ $locale }}.description"
                            :label="__('ui.description')"
                            :value="$data->translations ? $data->translations->where('lg', $locale)->first()->description : null"
                            :height="150"
                        />
                    </div>
                @endforeach
            </div>

            <br>

            <h3>{{ __('mfw-accounts::ui.BankAccount') }}</h3>
            <hr style="margin: 0">
            @if ($BankAccounts->isNotEmpty())
                <select class="form-control" name="bank_account_id">
                    @foreach($BankAccounts as $item)
                        <option value="{{ $item->id }}" {{ $data->bank_account_id == $item->id ? ' selected' : '' }}>
                            {{ $item->translation->bank .' / '. $item->IBAN}}
                        </option>
                    @endforeach
                </select>
            @else
                <x-mfw-support::alert type="warning" :message="__('ui.no_records')"/>
            @endif
            <p>
                <button class="btn btn-success" style="margin-top: 14px;">{!! __('ui.save') !!}</button>
            </p>
        </form>
    </div>

@stop
@push('js')
<script>
    $(function() {
        $('#tabs a').click(function (e) {
          e.preventDefault()
          $(this).tab('show')
      })
    });
</script>
@endpush
