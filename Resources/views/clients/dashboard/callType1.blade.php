<div class="panel panel-primary">
  <div class="panel-heading">
    <h3 class="panel-title">{!!trans_choice('mfw-accounts::ui.CallType'.$CallTypeId,2)!!}</h3>
   </div>
   <div class="panel-body">
<div class="table-responsive">

<table class="table" id='invoiceList'>
<caption></caption>
<thead>
    <tr>
        <th>#</th>
        <th>{!!__('mfw-accounts::ui.InvoiceTitle')!!}</th>
        <th>{!!__('mfw-accounts::ui.Date')!!}</th>
        <th>{!!__('mfw-accounts::ui.Amount')!!}</th>
        <th>{!!__('mfw-accounts::ui.VAT')!!}</th>
        <th>{!!__('mfw-accounts::ui.InvoicePaymentState')!!}</th>
        <th>{!!__('mfw-accounts::ui.DocSent')!!}</th>
        <th>{!!__('mfw-accounts::ui.PDF')!!}</th>
        <th>{!!__('mfw-accounts::ui.Duplicata')!!}</th>

        <!--<th>Opérateur</th>-->
    </tr>

</thead>

   @if(!empty($data['documents'][$CallTypeId]))
    @foreach($data['documents'][$CallTypeId] as $key=>$virgo)

   <?php  // Add row number sequence to paginated results
  // include app_path() . '/views/admin/pageNumber.php';  ?>

   <tr>

        <td>{!!$virgo['invoice_id']!!}</td>
        <td>{!!url('admin/crm/call/edit?object_id='.$virgo['id'], (!empty($virgo['titre']) ? $virgo['titre'] : __('mfw-accounts::ui.untitled')))!!}</td>
        <td>{!!$virgo['date_sur_facture']!!}</td>
        <td>{!!$virgo['prix_ht']!!}</td>
        <td>{!!$virgo['tva']!!}</td>
        <td>{!!!empty($virgo['paid']) ? __('mfw-accounts::ui.unpaid') : __('mfw-accounts::ui.paid')!!}</td>
        <td>{!!empty($virgo['sent']) ? null : $virgo['sent'] !!} {!!url('admin/crm/call/mail?object_id='.$virgo['id'],__('mfw-accounts::ui.Send'))!!}</td>
        <td>
          {!!url('pdf/'.$virgo['hash'],'', array('target'=>'_blank','class'=>'glyphicon glyphicon-file'))!!}
          {!!url('pdf/'.$virgo['hash'].'?download','', array('class'=>'glyphicon glyphicon-download-alt'))!!}
        </td>
        <td>{!!url('admin/crm/call/edit?account_id='.$data['client']['id'].'&callType=4&object_id='.$virgo['id'], __('mfw-accounts::ui.create'))!!}</td>
        <!--<td>{!!$virgo['user']!!}</td>-->

   </tr>

   @endforeach
@endif
</table>
</div>
<br>
<div class="pull-right">{!!url('admin/crm/call/add?account_id='.$data['client']['id'].'&callType=1', __('mfw-accounts::ui.NewCallType1'), array('class'=>'btn btn-primary btn-sm'))!!}</div>
</div></div>


