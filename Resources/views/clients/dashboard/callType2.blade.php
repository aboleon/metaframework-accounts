<div class="panel panel-success">
  <div class="panel-heading">
    <h3 class="panel-title">{!!trans_choice('mfw-accounts::ui.CallType'.$CallTypeId,2)!!}</h3>
   </div>
   <div class="panel-body">
<div class="table-responsive">

@if(!empty($offers))

<table class="table capitalize" id='invoiceList'>
<caption></caption>
<thead>
    <tr>
        <th>#</th>
        <th>{!!__('mfw-accounts::ui.InvoiceTitle')!!}</th>
        <th>{!!__('mfw-accounts::ui.Date')!!}</th>
        <th>{!!__('mfw-accounts::ui.Amount')!!}</th>
        <th>{!!__('mfw-accounts::ui.VAT')!!}</th>
        <th>{!!__('mfw-accounts::ui.DocSent')!!}</th>
        <th>{!!__('mfw-accounts::ui.PDF')!!}</th>
        <!--<th>Opérateur</th>-->
        <th></th>
        <th></th>
    </tr>

</thead>
  @if(!empty($data['documents'][$CallTypeId]))
    @foreach($data['documents'][$CallTypeId] as $key=>$virgo)

   <?php  // Add row number sequence to paginated results
  // include app_path() . '/views/admin/pageNumber.php';  ?>

   <tr>

        <td>{!!$virgo['id']!!}</td>
        <td>{!!url('admin/crm/call/edit?object_id='.$virgo['id'], (!empty($virgo['titre']) ? $virgo['titre'] : __('mfw-accounts::ui.untitled')))!!}</td>
        <td>{!!$virgo['date_sur_facture']!!}</td>
        <td>{!!$virgo['prix_ht']!!}</td>
        <td>{!!$virgo['tva']!!}</td>
        <td>{!!empty($virgo['sent']) ? null : $virgo['sent'] !!} {!!url('admin/crm/call/mail/?object_id='.$virgo['id'],'send')!!}</td>
        <td>
          {!!url('pdf/'.$virgo['hash'],'', array('target'=>'_blank','class'=>'glyphicon glyphicon-file'))!!}
          {!!url('pdf/'.$virgo['hash'].'?download','', array('class'=>'glyphicon glyphicon-download-alt'))!!}
          {!!$virgo['pdf_locale']!!}
          </td>
        <!--<td>{!!$virgo['user']!!}</td>-->
        <td>
        {!!url('#', __('mfw-accounts::ui.delete') , array('class'=>'btn btn-default btn-xs delete'))!!}</td>
   </tr>

   @endforeach
@endif
</table>
@endif
</div>
<br>
<div class="pull-right">
  {!!url('admin/crm/call/add?account_id='.$data['client']['id'].'&callType=2', __('mfw-accounts::ui.NewCallType2'), array('class'=>'btn btn-success btn-sm'))!!}</div>
</div></div>
