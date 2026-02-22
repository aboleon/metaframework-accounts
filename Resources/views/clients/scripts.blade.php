<script>
$('li.func').find('span.icon_add').click(function() {
	window.location.assign('{{ route('mfw-accounts.clients.create') }}');
});

</script>
