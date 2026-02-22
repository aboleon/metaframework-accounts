<div class="suggestions">
@if (!empty($data))
<ul>
	@foreach($data as $key=>$virgo)
		<li><span class="id hidden">{{$virgo->id}}</span><span class="text">{!! $virgo->prenom .' ' . $virgo->nom .' '.$virgo->societe !!}</span></li>
	@endforeach
</ul>
<a class="btn btn-xs btn-success" href="{{ route('mfw-accounts.clients.create') }}">{{ __('ui.add') }}</a>
@endif
</div>
