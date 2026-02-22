@section('content')
<div id="edit">

<div id="quoteId" class="hidden">{!!$data['id']!!}</div>
 {!!Form::open()!!}

<?php $convertDate = DateTime::createFromFormat('Y-m-d H:i:s', $data['created_at']); $clients = count($data['client']); ?>
<div class="panel panel-success">
  <div class="panel-heading">
   <h3 class="panel-title lowercase">{!!trans_choice('mfw-accounts::ui.QuoteDemand', 1) .' '.__('mfw-accounts::ui.QuoteReceived')!!}</h3>
   </div>
   <div class="panel-body">

    <h5><strong>{!!__('mfw-accounts::ui.QuoteSentBy')!!} :</strong></h5>

    {!!$data['prenom'] . ' '.$data['nom']!!}

    @if(!empty($data['company']))

     <br> {!!__('mfw-accounts::ui.Company') . ' : '.$data['company']!!}

    @endif

    <br>
    {!!$data['adresse']!!}

    <br>
    {!!__('mfw-accounts::ui.PhoneShort') .': '. $data['phone']!!}

    <br>
    e-mail : {!!$data['email']!!}

    <br><br>


    <strong>{!!__('mfw-accounts::ui.Date')!!} :</strong>

    {!! __('mfw-accounts::ui.DatePrefixOn'). ' '.
    $convertDate->format('d/m/Y').' '. __('mfw-accounts::ui.DatePrefixAt').' ' .$convertDate->format('H:i') !!}


    <br><br>
    <strong>{!!__('mfw-accounts::ui.QuoteType')!!} :</strong>

    {!! __('www.QuoteType'.$data['type'])!!}

    <br><br><strong>{!!__('mfw-accounts::ui.Description')!!} :</strong><br>

    {!!$data['data']!!}

    <br><br>


    <div class="clear"></div>


    <button id="toInvoice" type="button" class="btn btn-default pull-right delete">{!! __('mfw-accounts::ui.delete')!!}</button>


   </div>
</div>

@if(!empty($data['account_id']))
  @include('mfw-accounts::clients.clientPanel')
  <div class="well">{!!__('mfw-accounts::ui.QuoteAssigned')!!}
  {!!url('admin/crm/call/add?account_id='.$data['account_id'].'&callType=2&quote='.$data['id'], __('mfw-accounts::ui.MakeOffer'), array('class'=>'btn btn-success btn-sm pull-right'))!!}
  <button id="unsetClient" style="margin-right:10px" type="button" class="btn btn-primary btn-sm pull-right">{!! __('mfw-accounts::ui.QuoteUnsetClient')!!}</button>
  </div>

@else
<div class="panel panel-info">
  <div class="panel-heading">
   <h3 class="panel-title lowercase">
    @if(empty($data['client']))
      {!!__('mfw-accounts::ui.NoQuoteClient')!!}
      @else
      <strong>{!!trans_choice('mfw-accounts::ui.YesQuoteClient', $clients, array('number'=>$clients))!!}</strong>
      @endif
   </h3>
   </div>
   <div class="panel-body">

   @if(!empty($data['client']))
    <div class="table-responsive pull-left fullWidth">
      <table class="table" id='possibleClients'>
      <thead>
        <tr>
              <th></th>
              <th>{!!__('mfw-accounts::ui.Name')!!}</th>
              <th>{!!__('mfw-accounts::ui.Company')!!}</th>
              <th>{!!__('mfw-accounts::ui.Location')!!}</th>
              </tr>

      </thead>

        @foreach($data['client'] as $key=>$virgo)

         <?php
         $client = array();
         if(!empty($virgo['prenom'])) { $client['prenom'] = $virgo['prenom'];}
         if(!empty($virgo['nom'])) { $client['nom'] = $virgo['nom'];}

         $loc = empty($virgo['localisation']['master']) ? $virgo['localisation']['name'] : $virgo['localisation']['name'].' '.$virgo['localisation']['code'] . ', '.$data['masterLoc'][$virgo['localisation']['master']];

         ?>

         <tr>
              <th>{!!Form::Helpers::checkbox('id[]',$virgo['id'])!!}</th>
              <td><a target="_blank" href="{!!url('admin/crm/clients/dashboard?object_id='.$virgo['id'])!!}">{!!(!empty($client) ? implode(' ',$client)  : __('mfw-accounts::ui.untitled'))!!}</a></td>
              <td>{!!$virgo['societe']!!}</td>
              <td>{!!$loc!!}</td>
             </tr>

         @endforeach

      </table>
      </div>
      <button id="toClient" disabled="true" type="button" class="btn btn-primary pull-right">{!! __('mfw-accounts::ui.AddQuoteToClient')!!}</button>
     @endif
      <button style="margin-right:10px" id="toNewClient" type="button" class="btn btn-success pull-right">{!! __('mfw-accounts::ui.NewCientAccountBtn')!!}</button>

    </div>
  </div>

@endif
{!!Form::close()!!}
</div>
@stop
@section('scripts')
@parent
{!!asset('MediaclassCRM/js/quote.js')!!}
@stop
