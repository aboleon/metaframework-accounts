@section('content')
<?php $setting = $data['query']['setting']; ?>
<div id="list">
<h2 class="up">{!! trans_choice($M.$setting,2) !!}
| {!!url('#', __('mfw-accounts::ui.Add'.$setting), array('id'=>'addPage','data-toggle'=>'modal', 'data-backdrop'=>'static', 'data-target'=>'#addLoc'))!!}
</h2>
<div id="2"></div>
<div class="table-responsive">

<table class="table stripe" id='locList'>
<thead>
    <tr>
        <th class="hidden">#</th>
        <th colspan="2">{!!trans_choice($M.$setting,1)!!}</th>

    </tr>

</thead>
<tbody>
  @foreach($data['data'] as $key=>$virgo)
  {{-- {!!url('#',empty($virgo['master']) ? ' <span class="label label-success">'.__('mfw-accounts::ui.Country').'</span> <strong>'.$virgo['name'].'</strong>' : $virgo['name'], array('data-toggle'=>'modal', 'data-target'=>'#addLoc','class'=>'inline'))!!} --}}
   <tr>

        <td class="id hidden">{!!$virgo['id']!!}</td>
        <td class="name">{!!url('#',$virgo['name'], array('data-toggle'=>'modal', 'data-target'=>'#addLoc', 'data-backdrop'=>'static', 'class'=>'inline'))!!}</td>
        <td>{!!url('#',  _('Delete'), array('class'=>'btn btn-default btn-xs delete'))!!}</td>
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
        <h4 class="modal-title">{!!__('mfw-accounts::ui.AddpayMeans')!!}</h4>

      </div>
      <div class="modal-body form-inline">
         <div class="form-group">
         <div class="input-group">
	      <span class="input-group-addon">{!!__('mfw-accounts::ui.Name')!!}</span>
	      {!! Form::text('name', '', array('class'=>'form-control','style'=>"width:240px")) !!}
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


