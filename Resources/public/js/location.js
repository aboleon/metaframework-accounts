function setLocId() {

	$('#SearchResponse button').click(function(e) {
		e.preventDefault();
	});

	$('#SearchResponse div').click(function() {

  	$('input[name=localisation]').val($(this).find('span.id').text());
  	$('input[name=locName]').val($(this).find('span.name').text());
  	$('#SearchResponse').hide();

  });
}
 
var _token = $('#token').text();
var setting = $('#setting').text();
var UseAlt = $('#UseAlt').text();

console.log(setting);

$('a.inline').click(function() {

  /* Assign values dynamically from tr>td to #addLoc */

  $(this).addClass('editable');
  $('#addLoc').find('input[name=id]').remove();
  $('#addLoc').append('<input type="hidden" name="id" value="'+($(this).parents('tr').find('td.id').text())+'">');

  $('#addLoc').find('input[name=name]').val($(this).parents('tr').find('td.name').text());
  $('#addLoc').find('input[name=name_alt]').val($(this).parents('tr').find('td.name_alt').text());

  
  $('#addLoc').find('input[name=code]').val($(this).parents('tr').find('td.cp').text());

    var masterLoc = $(this).parents('tr').find('span.masterId').text();
    if(masterLoc == '0') {

      $('.masterLocBox').hide();
      $('.masterLocBox').find('input,select').attr('disabled','disabled');

    } else {
    $('.masterLocBox').show();$('.masterLocBox').find('input,select').removeAttr('disabled');
    $('#addLoc').find('select[name=master]').find('option[value='+masterLoc+']').attr('selected','selected');
    }
  
});


$('#addMasterLoc').click(function() {

  console.log('Click Add Master Loc');

var data = $('#addLoc').find('input[name=newMaster]').val();
var data_alt = $('#addLoc').find('input[name=newMaster_alt]').val();
$.ajax({
            url: 'admin/crm/ajax',
            type: 'POST',
            data: "object_type=addMasterLoc&_token="+_token+"&name="+data+"&name_alt="+data_alt,
      
      success: function(result) {

      	$('#addLoc').find('option').removeAttr('selected');
      	$('#addLoc').find('select').append('<option selected="selected" value="'+result+'">'+data+'</option>');
      	$('#addLoc').find('input[name=newMaster]').val('');

   
      },
      error: function (xhr, ajaxOptions, thrownError) {
        var result = ' Error status : '+ xhr.status+ ", Thrown Error : "+ thrownError +", Error : "+ xhr.responseText;
        $('#MediaclassCRMAjaxResponse').html('<div class="alert alert-danger" style="margin-top:10px;padding:8px 15px">'+result+'</div>');
      }
        });

});
/*
$('.modal-dialog').draggable({
      handle: ".modal-header"
  }); */


$('#saveNewLoc').click(function() {

  console.log('Save Location clic');
        
		var data = $('#addLoc').find('input, select').serialize();

        $.ajax({
            url: 'admin/crm/ajax',
            type: 'POST', 
            data: "object_type=addLoc&_token="+_token+"&"+data,
      
      success: function(result) {
       
        console.log(result);

       
       var lastSelected = $('#addLoc').find('select').find(':selected').val();

       var editable = $('#locList').find('a.editable').parents('tr'); // get clicked link
       editable.find('td.name a').text($('#addLoc').find('input[name=name]').val()); // reset tr>td infos after change of input
          

      	$('#MediaclassCRMAjaxResponse').html('');

        if(setting == 'localisations') {

          var lastSelected = $('#addLoc').find('select').find(':selected').val();

          var editable = $('#locList').find('a.editable').parents('tr'); // get clicked link
          editable.find('td.name a').text($('#addLoc').find('input[name=name]').val()); // reset tr>td infos after change of input
          if(UseAlt)
          {
            editable.find('td.name_alt').text($('#addLoc').find('input[name=name_alt]').val()); // reset tr>td infos after change of input
          }
           editable.find('td.cp').text($('#addLoc').find('input[name=code]').val());
          
            var master = parseInt(editable.parents('tr').find('td.master').find('span.masterId').text()); // country (0) or location ?

            if(master>1) {
           
            editable.find('td.master').find('span.masterName').text($('#addLoc').find('select').find(':selected').text());
            editable.find('td.master').find('span.masterId').text(lastSelected);
            }
          }
          else
          {
            $('input[name=locName]').val($('#addLoc').find('input[name=name]').val());
            $('input[name=localisation]').val(result);

            
          	$('#addLoc input').val('');
          	$('#SearchResponse').hide();
          }

          $('#addLoc').modal('hide');
       
      },
      error: function (xhr, ajaxOptions, thrownError) {
        console.log('ERROR');

        var result = ' Error status : '+ xhr.status+ ", Thrown Error : "+ thrownError +", Error : "+ xhr.responseText;
        console.log(result);

        $('#MediaclassCRMAjaxResponse').html('<div class="alert alert-danger" style="margin-top:10px;padding:8px 15px">'+result+'</div>');
      }
        });
    });
