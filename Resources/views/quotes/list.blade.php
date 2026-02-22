@section('content')

<?php


$sort = request()->sort;
$pageNumber = request()->page;

$currentRange =  ($pageNumber*$data['per_page']);
$currentRange =  empty($currentRange) ? $data['per_page'] : $currentRange;
//var_dump($currentRange-$data['per_page']);exit;
$paginator = Paginator::make($data, $data['total'], $data['per_page']);

?>
<div id="list">
<h2>{!! $data['total'] . ' '. trans_choice('mfw-accounts::ui.QuoteDemand',$data['total']). ($data['total'] > $data['per_page'] ? '('.(($currentRange-$data['per_page'])+1).' - '.$currentRange. ')' : null)  !!}</h2>

<div class="table-responsive">

<table class="table" id='pageList'>
<thead>
<tr><th colspan="8" class="center">{!! $paginator->appends(array('sort' => $sort))->links() !!}</th></tr>
    <tr>
        <th>#</th>
        <th>{!!__('mfw-accounts::ui.QuoteNameCompany')!!}</th>
        <th>{!!__('mfw-accounts::ui.phone')!!}</th>
        <th>e-mail</th>
        <th>{!!__('mfw-accounts::ui.QuoteType')!!}</th>
        <th>{!!__('mfw-accounts::ui.QuoteReceived')!!}</th>
    </tr>

</thead>

  @foreach($data['data'] as $key=>$virgo)

   <?php  // Add row number sequence to paginated results
  // include app_path() . '/views/admin/pageNumber.php';

  $convertDate = DateTime::createFromFormat('Y-m-d H:i:s', $virgo['created_at']);

   $client = array();
   if(!empty($virgo['prenom'])) { $client['prenom'] = $virgo['prenom'];}
   if(!empty($virgo['nom'])) { $client['nom'] = $virgo['nom'];}
   ?>

   <tr>

        <td>{!!$virgo['id']!!}</td>
        <td><a href="{!!url('admin/crm/quotes/process?object_id='.$virgo['id'])!!}">{!!(!empty($client) ? implode(' ',$client)  : null).
        (!empty($virgo['company']) ? ' <span>'.$virgo['company'].'</span>' : null)!!}</a></td>
        <td>{!!$virgo['phone']!!}</td>
        <td>{!!$virgo['email']!!}</td>
        <td>{!!__('www.QuoteType'.$virgo['type'])!!}</td>
        <td>{!!$convertDate->format('d/m/Y '.__('mfw-accounts::ui.DatePrefixAt').' H:i:s')!!}</td>
   </tr>

   @endforeach

</table>
</div>

<div class="center" style="border-top:2px solid #ccc">{!! $paginator->links() !!}</div>

</div>
@stop
