@section('content')
<?php $setting = $data['query']['setting'];?>
<div id="list">
{{--print_r($data);exit;--}}
<h2 class="up">{!! trans_choice($M.$setting,2) !!}
| {!!url('#', __('mfw-accounts::ui.Add'.$setting), array('id'=>'addPage','data-toggle'=>'modal', 'data-backdrop'=>'static', 'data-target'=>'#addLoc'))!!}
</h2>
<div id="2"></div>
<div class="table-responsive">

<table class="table stripe" id='locList'>
<thead>
    <tr>
        <th class="hidden">#</th>
        <th>{!!__('mfw-accounts::ui.Code')!!}</th>
        <th>{!!trans_choice($M.$setting,1)!!}</th>
        <th colspan="2">{!!__('mfw-accounts::ui.CurrencySign')!!}</th>


    </tr>

</thead>
<tbody>
  @foreach($data['data'] as $key=>$virgo)
    <tr>

        <td class="id hidden">{!!$virgo['id']!!}</td>
        <td class="code">{!!$virgo['code']!!}</td>
        <td class="name">{!!url('#',$virgo['name'], array('data-toggle'=>'modal', 'data-target'=>'#addLoc', 'data-backdrop'=>'static', 'class'=>'inline'))!!}</td>

        <td class="sign">{!!$virgo['sign']!!}</td>
        <td class="default"><span class="readable">{!!!empty($virgo['default']) ? __('mfw-accounts::ui.DefaultValue') : null!!}</span><span class="real hidden">{!!$virgo['default']!!}</span></td>

   </tr>
   @endforeach
</tbody>
</table>
<div id="token" class="hidden">{!!csrf_token()!!}</div>
<div id="setting" class="hidden">{!!$setting!!}</div>
<div id="DefaultValueText" class="hidden">{!!__('mfw-accounts::ui.DefaultValue')!!}</div>
</div>

</div>

<div id="modalTitle" class="hidden"><div class="edit">{!!__('mfw-accounts::ui.Edit'.$setting)!!}</div><div class="add">{!!__('mfw-accounts::ui.Add'.$setting)!!}</div></div>

<div id="addLoc" class="modal fade">

  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
        <h4 class="modal-title">{!!__('mfw-accounts::ui.Add'.$setting)!!}</h4>

      </div>
      <div class="modal-body form-inline">
      <div class="form-group">
         <div class="input-group">
	      <span class="input-group-addon">{!!trans_choice($M.$setting,1)!!}</span>
	      {!! Form::text('name', '', array('class'=>'form-control','style'=>"width:340px")) !!}
	    </div></div>

      <div class="form-group">
         <div class="input-group">
        <span class="input-group-addon">{!!__('mfw-accounts::ui.Code')!!}</span>
        {!! Form::text('code', '', array('class'=>'form-control','style'=>"width:60px")) !!}
      </div></div>
<div style="clear:both;padding-top:10px"></div>
       <div class="clear form-group">
         <div class="input-group">
        <span class="input-group-addon">{!!__('mfw-accounts::ui.CurrencySign')!!}</span>
        {!! Form::text('sign', '', array('class'=>'form-control','style'=>"width:60px")) !!}
      </div></div>

      <div class="form-group">
         <div class="input-group">
        <span class="input-group-addon up">{!!__('mfw-accounts::ui.DefaultValue')!!}</span>
        {!! Form::select('default', array(0=>__('mfw-accounts::ui.No'), 1=>__('mfw-accounts::ui.Yes')), array('class'=>'form-control','style'=>"width:120px")) !!}
      </div></div>

	    </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">{!!__('mfw-accounts::ui.BtnClose')!!}</button>
        <button id="saveNewLoc" type="button" class="btn btn-primary">{!!__('mfw-accounts::ui.save')!!}</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
@stop
