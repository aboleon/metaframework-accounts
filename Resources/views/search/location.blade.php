@if(!empty($result))

	@foreach($result as $key=>$virgo)

		<div>
			<span class='id'>{!!$virgo['id']!!}</span>
			<span class='code'>{!!$virgo['code']!!}</span>
			<span class='name'>{!!$virgo['name']!!}</span>

		</div>
	@endforeach
@else

	<p class="bg-danger">{!!__('admin.NoResult')!!}</p>
@endif

<button class="btn btn-primary btn-xs" data-toggle="modal" data-target="#addLoc">{!!__('admin.add')!!}</button>
