@extends('layouts.panel')

@section('title_page')
    {{ trans_choice('mfw-accounts::ui.channels', 2) }} - {{ $payMeanChannel->name }}
@endsection

@section('content')

    <form method="post" action="{{ route('mfw-accounts.pay-mean-channels.store') }}" class="form-inline">
        @csrf
        <div class="form-group">
            <select class="form-control" name="category">
                @foreach ($payMeans as $item)
                    <option value="{{ $item->pay_mean_id }}">{{ $item->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <button class="big btn-sm btn btn-primary" style="margin: 0 0 0 5px;">
                <i class="fa fa-plus"></i> <span>{!! __('mfw-accounts::ui.payMeans.channel.make') !!}</span>
            </button>
        </div>
    </form>
    <br>
    <table class="table table-striped table-bordered dt-responsive nowrap table-hover" cellspacing="0" width="100%">

        <thead>
            <tr>
                <th>{{ __('mfw-accounts::ui.title') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $item)
                <tr class="hover">
                    <td>{{ $item->name ?: '-' }}</td>
                    <td>
                        <ul class="mfw-actions">
                            <x-mfw::edit-link :route="route('mfw-accounts.pay-mean-channels.edit', $item)" />
                            <x-mfw::delete-modal-link reference="remove_paymeanschannel_{{ $item->id }}" />
                        </ul>
                        <x-mfw::modal :route="route('mfw-accounts.pay-mean-channels.destroy', $item)" title="{{ __('mfw.delete') }}"
                            question="{!! __('mfw.should_i_delete_record') !!} - <b>{{ $item->id . ' ' . ($item->name ?: '-') }}</b>"
                            reference="remove_paymeanschannel_{{ $item->id }}" :params="['object_id' => $item->id]" />
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">{!! __('ui.no_records') !!}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

@stop


