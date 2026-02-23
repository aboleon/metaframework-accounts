@extends('layouts.panel')

@section('title_page')
{{ trans_choice('mfw-accounts::ui.PayDocTypes', count($data) )}}
@endsection

<?php $lang =config('app.fallback_locale');?>

@section('content')

<table id="datatable-responsive" data-object="CashflowDocTypes" data-unlink-confirm="{{ __('ui.confirm') }}" data-url="panel/mfw-accounts/ajax" class="form table table-striped table-bordered dt-responsive nowrap table-hover" cellspacing="0" width="100%">
    <thead>
        <tr>
            <th style="max-width: 50px">#</th>
            <th>{!! __('mfw-accounts::ui.Code') !!}</th>
            <th>{!! trans_choice('mfw-accounts::ui.Currency',1) !!}</th>
            <th>{!! __('mfw-accounts::ui.CurrencySign') !!}</th>
            <th style="max-width: 140px"></th>
        </tr>
    </thead>
    <tbody>

        @forelse($data as $key=>$virgo)
        <tr class="hover unlinkable">
            <td class="id">{!! $virgo->id !!}</td>
            <td class="code">{!! $virgo->code !!}</td>
            <td class="name">
                <a href="#" data-toggle="modal" data-target="#addLoc" data-backdrop="static" class="inline">{{ $virgo->name }}</a>
            </td>
            <td class="sign">{!! $virgo->sign !!}</td>
            <td class="default"><span class="readable">{!! !empty($virgo->default ) ? __('mfw-accounts::ui.DefaultValue') : null!!}</span><span class="real hidden">{!! $virgo->default !!}</span></td>

        </tr>
        @empty
        <tr>
          <td colspan=3>{{ __('ui.NoSearchResult') }}</td>
      </tr>
      @endforelse
  </tbody>
</table>
<div id="DefaultValueText" class="hidden">{!!__('mfw-accounts::ui.DefaultValue')!!}</div>
</div>

</div>

<div id="modalTitle" class="hidden"><div class="edit">{!!__('mfw-accounts::ui.EditCurrency')!!}</div><div class="add">{!!__('mfw-accounts::ui.AddCurrency')!!}</div></div>

<div id="addLoc" class="modal fade">

  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
        <h4 class="modal-title">{!!__('mfw-accounts::ui.AddCurrency')!!}</h4>

    </div>
    <div class="modal-body">
        <x-mfw-inputable::input
            name="name"
            :label="trans_choice('mfw-accounts::ui.Currency', 1)"
        />

        <div class="row">
            <div class="col-sm-6">
                <x-mfw-inputable::input
                    name="code"
                    :label="__('mfw-accounts::ui.Code')"
                />
            </div>
            <div class="col-sm-6">
                <x-mfw-inputable::input
                    name="sign"
                    :label="__('mfw-accounts::ui.CurrencySign')"
                />
            </div>
        </div>

        <x-mfw-inputable::select
            name="default"
            :label="__('mfw-accounts::ui.DefaultValue')"
            :values="['0' => __('mfw-accounts::ui.No'), '1' => __('mfw-accounts::ui.Yes')]"
            :nullable="false"
        />

    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">{!!__('mfw-accounts::ui.BtnClose')!!}</button>
      <button id="saveNewLoc" type="button" class="btn btn-primary">{!!__('mfw-accounts::ui.save')!!}</button>
  </div>
</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
@stop


