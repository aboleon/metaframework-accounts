
function setExpenseSubType(expense_type) {

	var form = $('select[name=expense_subtype]');
	var options = form.find('option');

	form.parent('div').addClass('hidden');
	options.removeAttr('class');
	options.each(function() {
		if ($(this).text() != expense_type) {
			$(this).addClass('hidden');
		} else {
			$(this).addClass('visible');
		}
	});

	if (form.find('option.visible').length > 0) {
		form.parent('div').removeClass('hidden');
	}
}


function card_group(container) {

	var _token = $('form input[name=_token]').val();

	$.ajax({
				url: 'admin/ajax',
		        dataType:'html',
		        type: 'POST',
		        data: "platform=mfw-accounts&ajax_action=expenseGroupCard&object_type=expenses&_token="+_token,
		        success: function(result)
		            {
		            	container.html(result);

		            	var group_id = $('input[name=expense_bank_card_groups]').val();

		            	$(container).find('input[type=radio]').each(function() {

								//console.log($(this).val());

								if ($(this).val() == group_id) {
									$(this).attr('checked', true);
								}
						});


		                //console.log(result);
		            },
		        error: function (xhr, ajaxOptions, thrownError)
		            {
		            var result = ' Error status : '+ xhr.status+ ", Thrown Error : "+ thrownError +", Error : "+ xhr.responseText;
		            $('#ProviderAjaxResponse').html('<div class="alert alert-danger" style="margin-top:10px;padding:8px 15px">'+result+'</div>').delay(2222000).fadeOut(function()
		                {
		                    $('#ProviderAjaxResponse').html('');
		                });
		            }


			});
}

var _token = $('form input[name=_token]').val();
var expense_type = $('select[name=expense_type]').find(':selected');

if (expense_type.hasClass('multiple')) {
	$('#partial').removeClass('hidden');
}

expense_type = expense_type.val();

$('#mfw_accounts_expense_amount div.amount_element:not(:first)').prepend('<span class="glyphicon glyphicon-remove-sign removeRow"></span>');
removeRow();


setExpenseSubType(expense_type);

$('select[name=expense_type]').change(function() {

	$('#partial').addClass('hidden');
	var expense_type = $(this).val();

	if ($(this).find(':selected').hasClass('multiple')) {
		$('#partial').removeClass('hidden');
	}
	setExpenseSubType(expense_type);
});

$('#mfw_accounts_expense_amount span.add').click(function() {
	var content = $('#mfw_accounts_expense_amount').find('div.amount_element:last').clone();
	$('#mfw_accounts_expense_amount').append(content);
	$('#mfw_accounts_expense_amount div.amount_element:last').prepend('<span class="glyphicon glyphicon-remove-sign removeRow"></span>');
	$('#mfw_accounts_expense_amount .row').removeClass('hidden');
	removeRow();

});

$('#mfw_accounts_provider span.add').click(function() {
	$('#new_provider').show();

});


if ($('input.date').length > 0) {
 	var d = new Date();
	$('.date').datepicker({
	    format: "dd/mm/yyyy",
	    language: "fr",
	    autoclose: true,
	    todayHighlight: true,
	});
}

$('select[name=currency]').change(function() {
	var rate = $(this).find(':selected').text();
	console.log('rate '+ rate);
	$('input[name=conversion_rate]').val('').val(rate);
});


$('input[name=partial]').click(function() {

	if ($(this).is(':checked')) {

		$('.partial').hide();
		$('select[name=expense_type]').attr('disabled','disabled');
		$('select[name=provider]').find(':selected').removeAttr('selected');
		$('select[name=provider]').find('option:first').attr('selected');


	} else {
		$('.partial').show();
		$('select[name=expense_type]').removeAttr('disabled');
		$('#ProviderAjaxResponse').html('');
	}
});


$('select[name=provider]').change(function() {

			var provider = $(this).val();
			console.log('provider '+provider);

			var partial = $('input[name=partial]').is(':checked');

			$.ajax({
				url: 'admin/ajax',
		        dataType:'html',
		        type: 'POST',
		        data: "platform=mfw-accounts&ajax_action=invoiceByProvider&object_type=providers&provider_id="+provider+"&_token="+_token+'&partial='+partial,
		        success: function(result)
		            {
		            	$('#ProviderAjaxResponse').html(result);
		                //console.log(result);
		            },
		        error: function (xhr, ajaxOptions, thrownError)
		            {
		            var result = ' Error status : '+ xhr.status+ ", Thrown Error : "+ thrownError +", Error : "+ xhr.responseText;
		            $('#ProviderAjaxResponse').html('<div class="alert alert-danger" style="margin-top:10px;padding:8px 15px">'+result+'</div>').delay(2222000).fadeOut(function()
		                {
		                    $('#ProviderAjaxResponse').html('');
		                });
		            }


			});

		});


$('input[name=multiple]').click(function() {
	if ($(this).is(':checked')) {
		$('div.installment').hide();
	} else {
		$('div.installment').show();
	}
});


var expense_bank_card = $('input[name=expense_bank_card]').val();

if (expense_bank_card == 1) {

	var container = $('.card_group');


	card_group(container);


}



$('select[name=pay_mean]').change(function() {

	if ($(this).find(':selected').hasClass('card_group')) {

	var container = $(this).parents('div.row').next('.card_group');

	card_group(container);

	}
});
