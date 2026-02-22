var contentList = $('#MfwAccountsContentForm').html();

$('span.icon_add').click(function() {

	var element = $(this);

	element.parent('div').find('select').remove();
	element.parent('div').append(contentList);

	$('select[name=MfwAccountsContent]').find('option').removeAttr('selected');

	$('select[name=MfwAccountsContent]').change(function() {

		window.location.assign('admin/mfw-accounts/add/expenses?type='+($(this).val()));
	});
});