function forceValidFloat(n)

{

    if(n.length == 0) return '';

    var valids = '-0123456789';

    var hasDot = false;

    var r = '';

    var c;

    for(var i = 0; i < n.length; ++i)

    {

        c = n.charAt(i);

        if((c == '.' || c == ',') && !hasDot)

        {

            r += (r.length == 0) ? '0.' : '.';

            hasDot = true;

        }

        else if(valids.indexOf(c) != -1)

        {

            r += c;

        }

    }



    return r;

}
function calculations () {
  	
	  	$('input.digit').bind('change, keyup', function () {
		  		
		  	//	  this.value = this.value.replace(/^[ 0]/g,''); // no leading 0 or space	
			//	  this.value = this.value.replace(/[^0-9]/g,''); // only numbers
	
				  reset_calculations();
			});
            
        $('.tva_select select').bind('change', function () { reset_calculations(); });
        
        }
function reset_calculations() {
  	
  	 var total = net_price = net_tva = 0;
    
  	 
				  $('#callContainer tbody tr').each(function() { 
		  	 
		  	 	    var price = forceValidFloat($('.price input', this).val());
             
                	price = isNaN(price) ? 0 : price;
		  	 		
		  	 		var quantity = parseInt($('.unit input', this).val());

                    var prix_ht = price * quantity;
                    var tva_taux = $('.tva_select select',this).val();
                    var subtotal = (Math.round(parseFloat(price*quantity)*tva_taux*100)/100);
                    
                    var tva = (Math.round((parseFloat(subtotal)-(price*quantity))*100)/100);
                   
                    subtotal = isNaN(subtotal) ? 0 : subtotal;
                    tva = isNaN(tva) ? 0 : tva;

                    $('.tva input',this).val(tva);
                    $('.tva span',this).text(tva);
                 //   $('.subtotal input',this).val(subtotal);   
            

		  	 		$('td.subtotal span.subtotal',this).text(subtotal);
		  	 		
		  	 		total += subtotal;
            net_price += prix_ht;
		  	 		net_tva += tva;
                    
                    var clean_tva = net_tva.toFixed(2);
                    var clean_total = total.toFixed(2);
                    var clean_totalHT = net_price.toFixed(2);
                    		  	 		
		  	 		$('#totalSUM').text(clean_total);
		  	 		$('input[name=amountHT]').val(clean_totalHT);
		  	 		$('input[name=amountTVA]').val(clean_tva);
				  	
				  });
				  
  }




function deleteLine() {

$('#callContainer span.glyphicon-remove').click(function() {


	$(this).parents('tr').remove();
	reset_calculations();

});

}

$(function() {

	calculations();

	$('#callContainer span.glyphicon-remove:first').addClass('hidden');

$('.date').datepicker({
    format: "dd/mm/yyyy",
    language: "fr",
    autoclose: true
});

$('.ui-buttonset label').click(function() {

	if($(this).attr('for') == 'IsPaid') {

		$('#datePaid').removeAttr('disabled');
	} else {

		$('#datePaid').attr('disabled','disabled');
	}
})



$('#addLine').click(function(e) {
  
         e.preventDefault();
         $('#callContainer tbody tr:last').clone().appendTo('#callContainer tbody');
         $('#callContainer tbody tr:last').find('input,textarea').val('');
         $('#callContainer tbody tr:last').find('span').text('').removeClass('hidden');
         $('#callContainer tbody tr:last td.unit input').val(1)

         deleteLine();
         calculations();
         
  });


var _token = $('input[name=_token]').val();
var url = $('base').attr('href');

$('#submitCall, #toInvoice').click(function() {

var toInvoice = ($(this).attr('id') == 'toInvoice' ? 1 : 0);
var data = $('#callForm').serialize();
var id = $('input[name=id]').val();

$.ajax({
            url: 'admin/crm/ajax',
            type: 'POST', 
            data: "object_type=submitCall&_token="+_token+"&toInvoice="+toInvoice+"&"+data,
      
      success: function(result) {

    //  	$('#MediaclassCRMAjaxResponse').html(result); return false;
       
        if(toInvoice == 1) {

          window.location.replace(url + '/crm/call/edit?object_id='+($('input[name=id]').val()));
        
        }

        if(id == 'new') { window.location.assign(url + '/crm/call/edit?object_id='+parseInt(result)); } else {

          window.location.assign(url + '/crm/clients/dashboard?object_id='+parseInt(result));
        }

   
      },
      error: function (xhr, ajaxOptions, thrownError) {
        var result = ' Error status : '+ xhr.status+ ", Thrown Error : "+ thrownError +", Error : "+ xhr.responseText;
        $('#MediaclassCRMAjaxResponse').html('<div class="alert alert-danger" style="margin-top:10px;padding:8px 15px">'+result+'</div>');
      }
        });

});



});