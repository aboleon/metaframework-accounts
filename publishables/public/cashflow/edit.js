if ($('input.date').length > 0) {
 	var d = new Date();
	$('.date').datepicker({
	    format: "dd/mm/yyyy",
	    language: "fr",
	    autoclose: true,
	    todayHighlight: true,
	});
}

function appendAmount() {

	$('input[name*=to_invoice_id]').click(function() {
		console.log('click to_invoice_id');
		var el = $(this);
		var amount = 0;

		$('input[name*=to_invoice_id]:checked').each(function() {
			amount += parseInt($(this).parents('tr').find('.amount').text());
		});


		console.log(amount);
		$('input[name=amount]').val(amount);
	});
}

function showInvoices(client, _token) {
	$.ajax({
				url: 'admin/ajax',
		        dataType:'html',
		        type: 'POST',
		        data: "platform=mfw-accounts&ajax_action=invoiceByClient&object_type=clients&client_id="+client+"&_token="+_token,
		        success: function(result)
		            {
		            	$('#ClientAjaxResponse').html(result);

		            	$('#ClientAjaxResponse').find(':checkbox').click(function() {

		            		var el = $(this);
		            		el.parents('tr').clone().appendTo('#set_invoices_temp');
							el.parents('tr').remove();
							appendAmount();

		            	});

		            	appendAmount();

		                //console.log(result);


		            },
		        error: function (xhr, ajaxOptions, thrownError)
		            {
		            var result = ' Error status : '+ xhr.status+ ", Thrown Error : "+ thrownError +", Error : "+ xhr.responseText;
		            $('#ClientAjaxResponse').html('<div class="alert alert-danger" style="margin-top:10px;padding:8px 15px">'+result+'</div>').delay(2222000).fadeOut(function()
		                {
		                    $('#ClientAjaxResponse').html('');
		                });
		            }


			});
}


var _token = $('form input[name=_token]').val();
var edit = $('input[name=object_id]').length;

if (edit > 0) {

	appendAmount();

	//var to_invoices = $('input[name=to_invoices]').val();


	//var client = $('select[name=clients]').find(':selected').val();
	//showInvoices(client, _token);

}


$('#ClientAjaxResponse').find('input[type=radio]').each(function() {

				var i = $(this);
				console.log(i.val());
				if (i.val() == invoice_id) {
					i.attr('checked');
				}
		});


$('select[name=clients]').change(function() {

			var client = $(this).val();

			console.log('client '+client);

			showInvoices(client, _token);

});


