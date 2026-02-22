<?php

if (!empty($data->client)) {

$a = $data->client->geo;

//print_r($data['client']);exit;

$loc = $a->zip .' '. $a->Names[0]->title .' / '.$a->Names[1]->title.', ' . $a->Country->title;
?>
<div class="panel panel-default">
  <div class="panel-heading">
    {!! url(route('mfw-accounts.clients.index'), __('mfw-accounts::ui.ClientList'), ['class' => 'pull-right']) !!}
    <h3 class="panel-title"><a href="{{ route('mfw-accounts.clients.dashboard', $data->client['id']) }}">{{ __('mfw-accounts::ui.ClientAccount') }}</a></h3>
   </div>
   <div class="panel-body">
   <div class="pull-left">

    {!! $data->client['nom'] .' '.$data->client['prenom']  !!}

    {!!!empty($data->client['siret']) ? $data->client['societe'] : null!!}
    </div>
    <div class="pull-left" style="margin-left:20px">{!!$data->client['adresse'].', '. $loc!!}</div>
    @if(!empty($data->client['siret']))
      <div class="pull-left" style="margin-left:20px">{!! __('mfw-accounts::ui.EIN'). ' : ' . $data->client['siret']!!}</div>
    @endif
    @if(!empty($data->client['phone']))
      <div class="pull-left" style="margin-left:20px">{!! __('mfw-accounts::ui.phone'). ' : ' . $data->client['phone']!!}</div>
    @endif
    @if(!empty($data->client['email']))
      <div class="pull-left" style="margin-left:20px">{!! 'E-mail : ' . $data->client['email']!!}</div>
    @endif

    <div class="pull-right">{!! url(route('mfw-accounts.clients.edit', $data->client['id']), __('mfw-accounts::ui.edit'), ['class' => 'label label-success label-sm']) !!}</div>
  </div>
  </div>
  <?php } else { ?>

  <div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title"><a href="{{ route('mfw-accounts.clients.dashboard', $client['id']) }}">{{ __('mfw-accounts::ui.ClientAccount') }}</a></h3>
   </div>
   <div class="panel-body">


   <div id="mfw_accounts_client">

                <div class="row">
                  <div class="col-md-12">

                          {!! Form::text('client_name', $client ? $client['nom'] .' '.$client['prenom'] : null, array('class'=>'form-control')) !!}

                    </div>
                  </div>

                  <div id="ClientAjaxResponse"></div>


                </div>



   </div>
   </div>



  <?php } ?>
