$(function() {
	
var _token = $('#clientNote input[name=_token]').val();


  $('#clientNote button').click(function() {

  	var data = $('#clientNote').serialize();
    var quote = $('input[name=quote]').val();

    $.ajax({
      url: 'admin/crm/ajax',
      type: 'POST',
      data: "object_type=client&"+data,
      success: function(result) {
        //$('#MediaclassCRMAjaxResponse').html('<div class="alert alert-success" style="padding: 6px 15px;">'+result+'</div>').delay(9993000).fadeOut(function() { $('#MediaclassCRMAjaxResponse').html(''); });
        if(quote) {
          window.location.assign('admin/crm/clients/dashboard?object_id='+($('input[name=object_id]').val()));
        } else {
          window.location.assign('admin/crm/clients/index');
        }
      },
      error: function (xhr, ajaxOptions, thrownError) {
        var result = ' Error status : '+ xhr.status+ ", Thrown Error : "+ thrownError +", Error : "+ xhr.responseText;
        $('#MediaclassCRMAjaxResponse').html('<div class="alert alert-danger" style="margin-top:10px;padding:8px 15px">'+result+'</div>');
      }
    });
  });


  $('#clientNote input[name=locName]').keyup(function() {
	  	
	  	var data = $(this).val();
	  	var _token = $('#clientNote input[name=_token]').val();

	  	if(data.length > 2) {

	   	
	    $.ajax({
                    url: 'admin/ajax',
                    type: 'POST',
                    data: "&object_type=locSearch&_token="+_token+"&data="+data,
                    async:true,
                    success: function(result) {
                    	
                    	$('#SearchResponse').html(result).show();
                    	setLocId();
                    },
               error: function (xhr, ajaxOptions, thrownError) {
          var result = ' Error status : '+ xhr.status+ ", Thrown Error : "+ thrownError +", Error : "+ xhr.responseText;
          console.log(result);
          $('#SearchResponse').html('<div class="alert alert-danger" style="margin-top:14px;padding:8px 15px">'+result+'</div>');
        }
        });
	      
	    } else { $('#SearchResponse').empty(); } 
	  
	  });
  

});